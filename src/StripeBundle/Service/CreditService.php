<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Service;

use Stripe\Exception\ApiErrorException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tomedio\StripeBundle\Contract\StripeUserInterface;
use Tomedio\StripeBundle\Enum\Currency;
use Tomedio\StripeBundle\Event\CreditPurchaseEvent;
use Tomedio\StripeBundle\Event\CreditUsageEvent;
use Tomedio\StripeBundle\Event\StripeUserLoadEvent;

class CreditService
{
    private StripeClient $stripeClient;
    private string $successUrl;
    private string $cancelUrl;
    private EventDispatcherInterface $eventDispatcher;
    private CustomerService $customerService;

    public function __construct(
        StripeClient $stripeClient,
        string $successUrl,
        string $cancelUrl,
        EventDispatcherInterface $eventDispatcher,
        CustomerService $customerService
    ) {
        $this->stripeClient = $stripeClient;
        $this->successUrl = $successUrl;
        $this->cancelUrl = $cancelUrl;
        $this->eventDispatcher = $eventDispatcher;
        $this->customerService = $customerService;
    }

    /**
     * Create a checkout session for purchasing credits.
     *
     * @param StripeUserInterface $user The user purchasing the credits
     * @param int $amount The amount to charge in the smallest currency unit (e.g., cents for USD)
     * @param Currency $currency The currency to use
     * @param int $credits The number of credits to add
     * @param string $description A description of what the credits are for
     * @return string The URL to redirect the user to for payment
     * @throws ApiErrorException
     */
    public function createCreditsCheckoutSession(
        StripeUserInterface $user,
        int $amount,
        Currency $currency,
        int $credits,
        string $description
    ): string {
        // Ensure the user has a Stripe customer ID
        if (!$user->getStripeCustomerId()) {
            throw new \InvalidArgumentException('User must have a Stripe customer ID');
        }

        // Create the checkout session
        $sessionData = [
            'customer' => $user->getStripeCustomerId(),
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => $currency->value,
                        'product_data' => [
                            'name' => $description,
                            'metadata' => [
                                'credits' => $credits,
                            ],
                        ],
                        'unit_amount' => $amount,
                    ],
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
            'success_url' => $this->successUrl,
            'cancel_url' => $this->cancelUrl,
            'metadata' => [
                'credits' => $credits,
                'type' => 'credits_purchase',
                'user_id' => $user->getUserIdentifier(),
            ],
        ];

        $session = $this->stripeClient->execute(function () use ($sessionData) {
            return $this->stripeClient->getClient()->checkout->sessions->create($sessionData);
        });

        return $session->url;
    }

    /**
     * Use credits from a user's balance.
     *
     * @param StripeUserInterface $user The user to deduct credits from
     * @param int $credits The number of credits to use
     * @param string $description A description of what the credits were used for
     * @param string|null $referenceId A reference ID for the transaction
     * @return int The number of credits actually used (may be less than requested if not enough are available)
     */
    public function useCredits(
        StripeUserInterface $user,
        int $credits,
        string $description,
        ?string $referenceId = null
    ): int {
        $currentBalance = $user->getCreditsBalance();
        $creditsToUse = min($credits, $currentBalance);
        
        if ($creditsToUse <= 0) {
            return 0;
        }
        
        $newBalance = $currentBalance - $creditsToUse;
        $user->setCreditsBalance($newBalance);
        
        // Dispatch an event for the credit usage
        $event = new CreditUsageEvent($user, $creditsToUse, $description, $referenceId);
        $this->eventDispatcher->dispatch($event, 'stripe.credits.usage');
        
        return $creditsToUse;
    }

    /**
     * Handle a successful payment for credits.
     *
     * @param string $sessionId The Stripe checkout session ID
     * @throws ApiErrorException
     */
    public function handleSuccessfulCreditsPayment(string $sessionId): void
    {
        $session = $this->stripeClient->execute(function () use ($sessionId) {
            return $this->stripeClient->getClient()->checkout->sessions->retrieve($sessionId, [
                'expand' => ['payment_intent', 'customer'],
            ]);
        });
        
        if (!$session || $session->payment_status !== 'paid' || !isset($session->metadata->credits)) {
            return;
        }
        
        // Find the user by Stripe customer ID
        $user = $this->findUserByStripeCustomerId($session->customer->id);
        if (!$user) {
            return;
        }
        
        $credits = (int) $session->metadata->credits;
        $paymentIntentId = $session->payment_intent->id;
        $amount = $session->amount_total;
        $currency = Currency::from($session->currency);
        $description = 'Purchase of ' . $credits . ' credits';
        
        // Update the user's credit balance
        $currentBalance = $user->getCreditsBalance();
        $user->setCreditsBalance($currentBalance + $credits);
        
        // Dispatch an event for the credit purchase
        $event = new CreditPurchaseEvent($user, $credits, $description, $paymentIntentId, $amount, $currency);
        $this->eventDispatcher->dispatch($event, 'stripe.credits.purchase');
    }

    /**
     * Find a user by Stripe customer ID.
     * This method dispatches an event to find the user, as the repository is not available in this service.
     */
    private function findUserByStripeCustomerId(string $stripeCustomerId): ?StripeUserInterface
    {
        try {
            $customer = $this->stripeClient->execute(function () use ($stripeCustomerId) {
                return $this->stripeClient->getClient()->customers->retrieve($stripeCustomerId);
            });
            
            if (!$customer || !isset($customer->metadata->user_id)) {
                return null;
            }
            
            // Dispatch an event to find the user
            $event = new StripeUserLoadEvent($stripeCustomerId, $customer->metadata->user_id);
            $this->eventDispatcher->dispatch($event, 'stripe.user.load');
            
            return $event->getUser();
        } catch (ApiErrorException $e) {
            return null;
        }
    }
}
