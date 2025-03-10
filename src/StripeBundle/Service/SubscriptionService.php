<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Service;

use DateTimeImmutable;
use Stripe\Exception\ApiErrorException;
use Stripe\Subscription as StripeSubscription;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tomedio\StripeBundle\Contract\SubscriptionInterface;
use Tomedio\StripeBundle\Contract\SubscriptionPlanInterface;
use Tomedio\StripeBundle\Contract\StripeUserInterface;
use Tomedio\StripeBundle\Enum\SubscriptionStatus;
use Tomedio\StripeBundle\Event\SubscriptionCreatedEvent;

class SubscriptionService
{
    private StripeClient $stripeClient;
    private string $successUrl;
    private string $cancelUrl;
    private PlanService $planService;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        StripeClient $stripeClient,
        string $successUrl,
        string $cancelUrl,
        PlanService $planService,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->stripeClient = $stripeClient;
        $this->successUrl = $successUrl;
        $this->cancelUrl = $cancelUrl;
        $this->planService = $planService;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Create a checkout session for a subscription.
     *
     * @throws ApiErrorException
     */
    public function createCheckoutSession(
        StripeUserInterface $user,
        SubscriptionPlanInterface $plan,
        ?int $trialPeriodDays = null
    ): string {
        // Ensure the user has a Stripe customer ID
        if (!$user->getStripeCustomerId()) {
            throw new \InvalidArgumentException('User must have a Stripe customer ID');
        }

        // Ensure the plan has a Stripe price ID
        if (!$plan->getStripePriceId()) {
            // Create the price in Stripe if it doesn't exist
            $this->planService->syncPlanToStripe($plan);
        }

        // Create the checkout session
        $sessionData = [
            'customer' => $user->getStripeCustomerId(),
            'line_items' => [
                [
                    'price' => $plan->getStripePriceId(),
                    'quantity' => 1,
                ],
            ],
            'mode' => 'subscription',
            'success_url' => $this->successUrl,
            'cancel_url' => $this->cancelUrl,
        ];

        // Add trial period if specified
        if ($trialPeriodDays !== null || $plan->getTrialPeriodDays() !== null) {
            $days = $trialPeriodDays ?? $plan->getTrialPeriodDays();
            $sessionData['subscription_data'] = [
                'trial_period_days' => $days,
            ];
        }

        $session = $this->stripeClient->execute(function () use ($sessionData) {
            return $this->stripeClient->getClient()->checkout->sessions->create($sessionData);
        });

        return $session->url;
    }

    /**
     * Retrieve a subscription from Stripe.
     *
     * @throws ApiErrorException
     */
    public function retrieveSubscription(string $subscriptionId): StripeSubscription
    {
        return $this->stripeClient->execute(function () use ($subscriptionId) {
            return $this->stripeClient->getClient()->subscriptions->retrieve($subscriptionId);
        });
    }

    /**
     * Cancel a subscription in Stripe.
     *
     * @throws ApiErrorException
     */
    public function cancelSubscription(SubscriptionInterface $subscription, bool $cancelAtPeriodEnd = true): SubscriptionInterface
    {
        if (!$subscription->getStripeSubscriptionId()) {
            throw new \InvalidArgumentException('Subscription must have a Stripe subscription ID');
        }

        if ($cancelAtPeriodEnd) {
            // Cancel at the end of the billing period
            $stripeSubscription = $this->stripeClient->execute(function () use ($subscription) {
                return $this->stripeClient->getClient()->subscriptions->update(
                    $subscription->getStripeSubscriptionId(),
                    ['cancel_at_period_end' => true]
                );
            });
        } else {
            // Cancel immediately
            $stripeSubscription = $this->stripeClient->execute(function () use ($subscription) {
                return $this->stripeClient->getClient()->subscriptions->cancel(
                    $subscription->getStripeSubscriptionId()
                );
            });
        }

        return $this->syncSubscriptionFromStripe($subscription->getUser(), $stripeSubscription);
    }

    /**
     * Sync a subscription from Stripe to the local database.
     * 
     * This method updates an existing subscription with data from Stripe.
     * If the subscription doesn't exist, it dispatches an event to allow the application
     * to create a new subscription entity.
     *
     * @throws ApiErrorException
     */
    public function syncSubscriptionFromStripe(
        StripeUserInterface $user,
        StripeSubscription $stripeSubscription
    ): SubscriptionInterface {
        // Find the subscription entity
        $subscription = $user->getActiveSubscription();
        
        // If no active subscription or different subscription ID, dispatch an event to create a new one
        if ($subscription === null || $subscription->getStripeSubscriptionId() !== $stripeSubscription->id) {
            // Dispatch an event to create a new subscription
            $event = new SubscriptionCreatedEvent($user, $stripeSubscription);
            $this->eventDispatcher->dispatch($event, 'stripe.subscription.created');
            
            // If the event didn't set a subscription, throw an exception
            if (!$event->hasSubscription()) {
                throw new \RuntimeException(
                    'Application must implement subscription creation by listening to the stripe.subscription.created event'
                );
            }
            
            $subscription = $event->getSubscription();
        }

        // Update the subscription with data from Stripe
        $subscription->setStripeSubscriptionId($stripeSubscription->id);
        $subscription->setStatus(SubscriptionStatus::from($stripeSubscription->status));

        // Set dates
        if (isset($stripeSubscription->current_period_start)) {
            $startDate = new DateTimeImmutable('@' . $stripeSubscription->current_period_start);
            $subscription->setStartDate($startDate);
        }

        if (isset($stripeSubscription->current_period_end)) {
            $endDate = new DateTimeImmutable('@' . $stripeSubscription->current_period_end);
            $subscription->setEndDate($endDate);
        }

        if (isset($stripeSubscription->trial_end) && $stripeSubscription->trial_end > 0) {
            $trialEndDate = new DateTimeImmutable('@' . $stripeSubscription->trial_end);
            $subscription->setTrialEndDate($trialEndDate);
        } else {
            $subscription->setTrialEndDate(null);
        }

        return $subscription;
    }

    /**
     * Get all subscriptions for a user from Stripe.
     *
     * @throws ApiErrorException
     */
    public function getSubscriptionsForUser(StripeUserInterface $user): array
    {
        if (!$user->getStripeCustomerId()) {
            return [];
        }

        $stripeSubscriptions = $this->stripeClient->execute(function () use ($user) {
            return $this->stripeClient->getClient()->subscriptions->all([
                'customer' => $user->getStripeCustomerId(),
                'status' => 'all',
            ]);
        });

        return $stripeSubscriptions->data;
    }
}
