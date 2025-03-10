<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tomedio\StripeBundle\Contract\SubscriptionPlanInterface;
use Tomedio\StripeBundle\Enum\BillingInterval;
use Tomedio\StripeBundle\Enum\Currency;
use Tomedio\StripeBundle\Event\SubscriptionPlanCreateEvent;
use Tomedio\StripeBundle\Event\SubscriptionPlanDeleteEvent;
use Tomedio\StripeBundle\Event\SubscriptionPlanListEvent;
use Tomedio\StripeBundle\Event\SubscriptionPlanLoadEvent;
use Tomedio\StripeBundle\Event\SubscriptionPlanSyncEvent;
use Tomedio\StripeBundle\Event\SubscriptionPlanUpdateEvent;
use Tomedio\StripeBundle\Model\SubscriptionPlanConfig;
use Tomedio\StripeBundle\Service\PlanService;

#[AsCommand(
    name: 'stripe:sync-plans',
    description: 'Synchronize subscription plans from configuration to database and Stripe',
)]
class SyncSubscriptionPlansCommand extends Command
{
    private ParameterBagInterface $parameterBag;
    private EventDispatcherInterface $eventDispatcher;
    private PlanService $planService;

    public function __construct(
        ParameterBagInterface $parameterBag,
        EventDispatcherInterface $eventDispatcher,
        PlanService $planService
    ) {
        parent::__construct();
        $this->parameterBag = $parameterBag;
        $this->eventDispatcher = $eventDispatcher;
        $this->planService = $planService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Synchronizing subscription plans');

        // Get subscription plans from configuration
        $configPlans = $this->parameterBag->get('stripe_bundle.subscription_plans');
        if (empty($configPlans)) {
            $io->warning('No subscription plans found in configuration');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Found %d subscription plans in configuration', count($configPlans)));

        // Get all existing plans from the database
        $listEvent = new SubscriptionPlanListEvent();
        $this->eventDispatcher->dispatch($listEvent, 'stripe.plan.list');
        $existingPlans = $listEvent->getPlans();
        
        // Create a map of existing plans by ID for easy lookup
        $existingPlanMap = [];
        foreach ($existingPlans as $existingPlan) {
            $existingPlanMap[$existingPlan->getId()] = $existingPlan;
        }
        
        // Track which plans are in the configuration
        $configPlanIds = [];
        
        // Process plans from configuration
        foreach ($configPlans as $configPlan) {
            $io->section(sprintf('Processing plan: %s (%s)', $configPlan['name'], $configPlan['id']));

            // Create a config model from the array
            $config = SubscriptionPlanConfig::fromArray($configPlan);
            $configPlanIds[] = $config->getId();

            // Check if the plan already exists in the database
            $planExists = isset($existingPlanMap[$config->getId()]);
            
            if ($planExists) {
                $io->text('Plan exists in database, updating it');
                $plan = $existingPlanMap[$config->getId()];
                
                // Dispatch update event to update the plan in the database
                $updateEvent = new SubscriptionPlanUpdateEvent($plan);
                $this->eventDispatcher->dispatch($updateEvent, 'stripe.plan.update');
            } else {
                $io->text('Plan does not exist in database, creating it');
                
                // Try to load the plan from the database or create a new entity if doesn't exist in db yet
                $loadEvent = new SubscriptionPlanLoadEvent($config->getId(), $config);
                $this->eventDispatcher->dispatch($loadEvent, 'stripe.plan.load');

                if (!$loadEvent->hasPlan()) {
                    $io->warning(sprintf(
                        'Failed to create plan "%s" (ID: %s). Make sure you have a listener for the stripe.plan.load event.',
                        $config->getName(),
                        $config->getId()
                    ));
                    continue;
                }

                $plan = $loadEvent->getPlan();
            }
            
            // Sync the plan to Stripe
            $io->text('Syncing plan to Stripe');
            try {
                $syncedPlan = $this->planService->syncPlanToStripe($plan);
                
                // If the plan was updated in Stripe, dispatch an update event
                if ($syncedPlan->getStripeProductId() !== $plan->getStripeProductId() ||
                    $syncedPlan->getStripePriceId() !== $plan->getStripePriceId()) {
                    
                    $io->text('Updating Stripe IDs in database');
                    
                    // Update the plan with the new Stripe IDs
                    $plan->setStripeProductId($syncedPlan->getStripeProductId());
                    $plan->setStripePriceId($syncedPlan->getStripePriceId());
                    
                    // Dispatch update event to save the changes
                    $updateEvent = new SubscriptionPlanUpdateEvent($plan);
                    $this->eventDispatcher->dispatch($updateEvent, 'stripe.plan.update');
                }
                
                $io->success(sprintf(
                    'Successfully synced plan "%s" (ID: %s) to Stripe (Product ID: %s, Price ID: %s)',
                    $plan->getName(),
                    $plan->getId(),
                    $plan->getStripeProductId(),
                    $plan->getStripePriceId()
                ));
            } catch (\Exception $e) {
                $io->error(sprintf(
                    'Failed to sync plan "%s" (ID: %s) to Stripe: %s',
                    $plan->getName(),
                    $plan->getId(),
                    $e->getMessage()
                ));
            }
        }
        
        // Handle plans that are in the database but not in the configuration
        $io->section('Checking for plans to remove');
        foreach ($existingPlans as $existingPlan) {
            if (!in_array($existingPlan->getId(), $configPlanIds)) {
                $io->text(sprintf('Plan "%s" (ID: %s) is not in configuration, checking if it can be removed', $existingPlan->getName(), $existingPlan->getId()));
                
                // Check if the plan can be deleted
                $deleteEvent = new SubscriptionPlanDeleteEvent($existingPlan);
                $this->eventDispatcher->dispatch($deleteEvent, 'stripe.plan.delete');
                
                if ($deleteEvent->canDelete()) {
                    $io->text('Deactivating plan in Stripe');
                    try {
                        $this->planService->deactivatePlan($existingPlan);
                        $io->success(sprintf('Successfully deactivated plan "%s" (ID: %s) in Stripe', $existingPlan->getName(), $existingPlan->getId()));
                    } catch (\Exception $e) {
                        $io->error(sprintf('Failed to deactivate plan "%s" (ID: %s) in Stripe: %s', $existingPlan->getName(), $existingPlan->getId(), $e->getMessage()));
                    }
                } else {
                    $io->warning(sprintf('Cannot delete plan "%s" (ID: %s): %s', $existingPlan->getName(), $existingPlan->getId(), $deleteEvent->getReason() ?? 'Unknown reason'));
                }
            }
        }

        $io->success('Subscription plans synchronization completed');

        return Command::SUCCESS;
    }
}
