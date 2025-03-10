---
layout: default
title: Event Listeners
parent: Integration
nav_order: 2
---

# Event Listeners
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

1. TOC
{:toc}

---

## Overview

The Stripe Bundle uses Symfony's event system to communicate between different components and to allow your application to react to various events. This approach provides a clean separation of concerns and makes the bundle highly extensible.

This page covers how to implement event listeners for the key events in your application. For a comprehensive reference of all events dispatched by the bundle, see the [Events](../events) page.

## Subscription Plan Events

These events are dispatched during the subscription plan synchronization process.

### 1. Create an Event Subscriber

```php
<?php

namespace App\EventSubscriber;

use App\Entity\SubscriptionPlan;
use App\Repository\SubscriptionPlanRepository;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tomedio\StripeBundle\Event\SubscriptionPlanCreateEvent;
use Tomedio\StripeBundle\Event\SubscriptionPlanDeleteEvent;
use Tomedio\StripeBundle\Event\SubscriptionPlanListEvent;
use Tomedio\StripeBundle\Event\SubscriptionPlanLoadEvent;
use Tomedio\StripeBundle\Event\SubscriptionPlanUpdateEvent;
use Tomedio\StripeBundle\Model\SubscriptionPlanConfig;

class SubscriptionPlanEventSubscriber implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;
    private SubscriptionPlanRepository $planRepository;
    private SubscriptionRepository $subscriptionRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        SubscriptionPlanRepository $planRepository,
        SubscriptionRepository $subscriptionRepository
    ) {
        $this->entityManager = $entityManager;
        $this->planRepository = $planRepository;
        $this->subscriptionRepository = $subscriptionRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'stripe.plan.list' => 'onPlanList',
            'stripe.plan.load' => 'onPlanLoad',
            'stripe.plan.create' => 'onPlanCreate',
            'stripe.plan.update' => 'onPlanUpdate',
            'stripe.plan.delete' => 'onPlanDelete',
        ];
    }

    /**
     * Return all subscription plans from the database.
     */
    public function onPlanList(SubscriptionPlanListEvent $event): void
    {
        $plans = $this->planRepository->findAll();
        $event->setPlans($plans);
    }

    /**
     * Load a plan from the database or create a new one if it doesn't exist.
     */
    public function onPlanLoad(SubscriptionPlanLoadEvent $event): void
    {
        $planId = $event->getPlanId();
        $config = $event->getConfig();
        
        // Try to find the plan in the database
        $plan = $this->planRepository->find($planId);
        
        // If the plan doesn't exist, create a new one
        if (!$plan) {
            $plan = $this->createPlanFromConfig($config);
            $this->entityManager->persist($plan);
            $this->entityManager->flush();
        }
        
        $event->setPlan($plan);
    }

    /**
     * Create a new plan in the database.
     */
    public function onPlanCreate(SubscriptionPlanCreateEvent $event): void
    {
        $plan = $event->getPlan();
        
        // Save the plan to the database
        $this->entityManager->persist($plan);
        $this->entityManager->flush();
    }

    /**
     * Update a plan in the database.
     */
    public function onPlanUpdate(SubscriptionPlanUpdateEvent $event): void
    {
        $plan = $event->getPlan();
        
        // Update the plan in the database
        $this->entityManager->persist($plan);
        $this->entityManager->flush();
    }

    /**
     * Check if a plan can be safely deleted and mark it for deletion if possible.
     */
    public function onPlanDelete(SubscriptionPlanDeleteEvent $event): void
    {
        $plan = $event->getPlan();
        
        // Check if the plan has any active subscriptions
        $activeSubscriptions = $this->subscriptionRepository->findActiveByPlan($plan);
        
        if (count($activeSubscriptions) > 0) {
            // The plan has active subscriptions, so it can't be deleted
            $event->setCanDelete(false, 'Plan has active subscriptions');
            return;
        }
        
        // The plan can be deleted
        $event->setCanDelete(true);
        
        // Mark the plan as deleted in the database
        // You might want to add a 'deleted' field to your SubscriptionPlan entity
        // $plan->setDeleted(true);
        // $this->entityManager->persist($plan);
        // $this->entityManager->flush();
    }

    /**
     * Create a new plan from a configuration.
     */
    private function createPlanFromConfig(SubscriptionPlanConfig $config): SubscriptionPlan
    {
        $plan = new SubscriptionPlan();
        $plan->setId($config->getId());
        $plan->setName($config->getName());
        $plan->setDescription($config->getDescription());
        $plan->setAmount($config->getAmount());
        $plan->setCurrency($config->getCurrency());
        $plan->setInterval($config->getInterval());
        $plan->setTrialPeriodDays($config->getTrialPeriodDays());
        
        return $plan;
    }
}
```

### 2. Register the Event Subscriber

Make sure your event subscriber is registered as a service:

```yaml
# config/services.yaml
services:
    # ...
    App\EventSubscriber\SubscriptionPlanEventSubscriber:
        tags:
            - { name: kernel.event_subscriber }
```

## Webhook Events

The Stripe Bundle converts Stripe webhook events into Symfony events with the prefix `stripe.` followed by the Stripe event name with underscores instead of dots. For example, the Stripe event `customer.subscription.created` becomes the Symfony event `stripe.customer_subscription_created`.

### 1. Create a Webhook Event Subscriber

```php
<?php

namespace App\EventSubscriber;

use App\Entity\Invoice;
use App\Entity\Subscription;
use App\Repository\SubscriptionPlanRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tomedio\StripeBundle\Enum\InvoiceStatus;
use Tomedio\StripeBundle\Enum\SubscriptionStatus;
use Tomedio\StripeBundle\Event\StripeWebhookEvent;

class StripeWebhookSubscriber implements EventSubscriberInterface
{
    private UserRepository $userRepository;
    private SubscriptionPlanRepository $planRepository;
    private EntityManagerInterface $entityManager;
    
    public function __construct(
        UserRepository $userRepository,
        SubscriptionPlanRepository $planRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->userRepository = $userRepository;
        $this->planRepository = $planRepository;
        $this->entityManager = $entityManager;
    }
    
    public static function getSubscribedEvents(): array
    {
        return [
            'stripe.customer_subscription_created' => 'onSubscriptionCreated',
            'stripe.customer_subscription_updated' => 'onSubscriptionUpdated',
            'stripe.customer_subscription_deleted' => 'onSubscriptionDeleted',
            'stripe.invoice_paid' => 'onInvoicePaid',
            'stripe.invoice_payment_failed' => 'onInvoicePaymentFailed',
            'stripe.checkout_session_completed' => 'onCheckoutSessionCompleted',
        ];
    }
    
    public function onSubscriptionCreated(StripeWebhookEvent $event): void
    {
        $stripeEvent = $event->getStripeEvent();
        $stripeSubscription = $stripeEvent->data->object;
        
        // Get the customer ID from the subscription
        $customerId = $stripeSubscription->customer;
        
        // Find the user by Stripe customer ID
        $user = $this->userRepository->findOneByStripeCustomerId($customerId);
        if (!$user) {
            return;
        }
        
        // Get the plan ID from the subscription
        $stripePriceId = $stripeSubscription->items->data[0]->price->id;
        
        // Find the plan by Stripe price ID
        $plan = $this->planRepository->findOneByStripePriceId($stripePriceId);
        if (!$plan) {
            return;
        }
        
        // Create a new subscription
        $subscription = new Subscription();
        $subscription->setUser($user);
        $subscription->setPlan($plan);
        $subscription->setStripeSubscriptionId($stripeSubscription->id);
        $subscription->setStatus(SubscriptionStatus::from($stripeSubscription->status));
        
        // Set dates
        $startDate = new \DateTime('@' . $stripeSubscription->current_period_start);
        $endDate = new \DateTime('@' . $stripeSubscription->current_period_end);
        $subscription->setStartDate($startDate);
        $subscription->setEndDate($endDate);
        
        // Set trial end date if applicable
        if ($stripeSubscription->trial_end) {
            $trialEndDate = new \DateTime('@' . $stripeSubscription->trial_end);
            $subscription->setTrialEndDate($trialEndDate);
        }
        
        // Save the subscription
        $this->entityManager->persist($subscription);
        
        // Set the subscription as the user's active subscription
        $user->setActiveSubscription($subscription);
        $this->entityManager->persist($user);
        
        $this->entityManager->flush();
    }
    
    public function onSubscriptionUpdated(StripeWebhookEvent $event): void
    {
        $stripeEvent = $event->getStripeEvent();
        $stripeSubscription = $stripeEvent->data->object;
        
        // Find the subscription by Stripe subscription ID
        $subscription = $this->subscriptionRepository->findOneByStripeSubscriptionId($stripeSubscription->id);
        if (!$subscription) {
            return;
        }
        
        // Update the subscription status
        $subscription->setStatus(SubscriptionStatus::from($stripeSubscription->status));
        
        // Update dates
        $startDate = new \DateTime('@' . $stripeSubscription->current_period_start);
        $endDate = new \DateTime('@' . $stripeSubscription->current_period_end);
        $subscription->setStartDate($startDate);
        $subscription->setEndDate($endDate);
        
        // Update trial end date if applicable
        if ($stripeSubscription->trial_end) {
            $trialEndDate = new \DateTime('@' . $stripeSubscription->trial_end);
            $subscription->setTrialEndDate($trialEndDate);
        } else {
            $subscription->setTrialEndDate(null);
        }
        
        // Save the subscription
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();
    }
    
    public function onSubscriptionDeleted(StripeWebhookEvent $event): void
    {
        $stripeEvent = $event->getStripeEvent();
        $stripeSubscription = $stripeEvent->data->object;
        
        // Find the subscription by Stripe subscription ID
        $subscription = $this->subscriptionRepository->findOneByStripeSubscriptionId($stripeSubscription->id);
        if (!$subscription) {
            return;
        }
        
        // Update the subscription status
        $subscription->setStatus(SubscriptionStatus::CANCELED);
        
        // Save the subscription
        $this->entityManager->persist($subscription);
        
        // If this is the user's active subscription, remove it
        $user = $subscription->getUser();
        if ($user->getActiveSubscription() && $user->getActiveSubscription()->getId() === $subscription->getId()) {
            $user->setActiveSubscription(null);
            $this->entityManager->persist($user);
        }
        
        $this->entityManager->flush();
    }
    
    public function onInvoicePaid(StripeWebhookEvent $event): void
    {
        $stripeEvent = $event->getStripeEvent();
        $stripeInvoice = $stripeEvent->data->object;
        
        // Get the customer ID from the invoice
        $customerId = $stripeInvoice->customer;
        
        // Find the user by Stripe customer ID
        $user = $this->userRepository->findOneByStripeCustomerId($customerId);
        if (!$user) {
            return;
        }
        
        // Create a new invoice
        $invoice = new Invoice();
        $invoice->setUser($user);
        $invoice->setStripeInvoiceId($stripeInvoice->id);
        $invoice->setAmount($stripeInvoice->amount_paid);
        $invoice->setCurrency(Currency::from(strtoupper($stripeInvoice->currency)));
        $invoice->setStatus(InvoiceStatus::PAID);
        
        // Set dates
        $invoiceDate = new \DateTime('@' . $stripeInvoice->created);
        $paidDate = new \DateTime('@' . $stripeInvoice->status_transitions->paid_at);
        $invoice->setInvoiceDate($invoiceDate);
        $invoice->setPaidDate($paidDate);
        
        // If the invoice is for a subscription, link it
        if ($stripeInvoice->subscription) {
            $subscription = $this->subscriptionRepository->findOneByStripeSubscriptionId($stripeInvoice->subscription);
            if ($subscription) {
                $invoice->setSubscription($subscription);
            }
        }
        
        // Save the invoice
        $this->entityManager->persist($invoice);
        $this->entityManager->flush();
    }
    
    public function onInvoicePaymentFailed(StripeWebhookEvent $event): void
    {
        $stripeEvent = $event->getStripeEvent();
        $stripeInvoice = $stripeEvent->data->object;
        
        // Get the customer ID from the invoice
        $customerId = $stripeInvoice->customer;
        
        // Find the user by Stripe customer ID
        $user = $this->userRepository->findOneByStripeCustomerId($customerId);
        if (!$user) {
            return;
        }
        
        // Create a new invoice or update an existing one
        $invoice = $this->invoiceRepository->findOneByStripeInvoiceId($stripeInvoice->id);
        if (!$invoice) {
            $invoice = new Invoice();
            $invoice->setUser($user);
            $invoice->setStripeInvoiceId($stripeInvoice->id);
        }
        
        $invoice->setAmount($stripeInvoice->amount_due);
        $invoice->setCurrency(Currency::from(strtoupper($stripeInvoice->currency)));
        $invoice->setStatus(InvoiceStatus::FAILED);
        
        // Set dates
        $invoiceDate = new \DateTime('@' . $stripeInvoice->created);
        $invoice->setInvoiceDate($invoiceDate);
        
        // If the invoice is for a subscription, link it
        if ($stripeInvoice->subscription) {
            $subscription = $this->subscriptionRepository->findOneByStripeSubscriptionId($stripeInvoice->subscription);
            if ($subscription) {
                $invoice->setSubscription($subscription);
            }
        }
        
        // Save the invoice
        $this->entityManager->persist($invoice);
        $this->entityManager->flush();
    }
    
    public function onCheckoutSessionCompleted(StripeWebhookEvent $event): void
    {
        $stripeEvent = $event->getStripeEvent();
        $session = $stripeEvent->data->object;
        
        // Get the customer ID from the session
        $customerId = $session->customer;
        
        // Find the user by Stripe customer ID
        $user = $this->userRepository->findOneByStripeCustomerId($customerId);
        if (!$user) {
            return;
        }
        
        // Handle different checkout modes
        switch ($session->mode) {
            case 'subscription':
                // Subscription checkout is handled by the onSubscriptionCreated event
                break;
                
            case 'payment':
                // Handle one-time payment (e.g., credit purchase)
                if (isset($session->metadata->credits)) {
                    $credits = (int) $session->metadata->credits;
                    
                    // Add credits to the user's balance
                    $this->creditService->addCredits(
                        $user,
                        $credits,
                        'Credit purchase',
                        CreditTransactionType::PURCHASE,
                        'session_' . $session->id
                    );
                }
                break;
        }
    }
}
```

### 2. Register the Webhook Event Subscriber

```yaml
# config/services.yaml
services:
    # ...
    App\EventSubscriber\StripeWebhookSubscriber:
        tags:
            - { name: kernel.event_subscriber }
```

## Credit Events

The Stripe Bundle dispatches events when credits are added or used.

### 1. Create a Credit Event Subscriber

```php
<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tomedio\StripeBundle\Event\CreditPurchaseEvent;
use Tomedio\StripeBundle\Event\CreditUsageEvent;

class CreditEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CreditPurchaseEvent::class => 'onCreditPurchase',
            CreditUsageEvent::class => 'onCreditUsage',
        ];
    }
    
    public function onCreditPurchase(CreditPurchaseEvent $event): void
    {
        $user = $event->getUser();
        $amount = $event->getAmount();
        $transaction = $event->getTransaction();
        
        // You can perform additional actions when credits are purchased
        // For example, send a notification to the user
    }
    
    public function onCreditUsage(CreditUsageEvent $event): void
    {
        $user = $event->getUser();
        $amount = $event->getAmount();
        $transaction = $event->getTransaction();
        
        // You can perform additional actions when credits are used
        // For example, log usage statistics or send a notification when the balance is low
        if ($user->getCreditsBalance() < 10) {
            // Send a low balance notification
        }
    }
}
```

### 2. Register the Credit Event Subscriber

```yaml
# config/services.yaml
services:
    # ...
    App\EventSubscriber\CreditEventSubscriber:
        tags:
            - { name: kernel.event_subscriber }
```

## API Platform Events

If you're using API Platform, you can use its event system to integrate with the Stripe Bundle.

### 1. Create an API Platform Event Subscriber

```php
<?php

namespace App\EventSubscriber;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Tomedio\StripeBundle\Service\CustomerService;

class ApiUserCreationSubscriber implements EventSubscriberInterface
{
    private CustomerService $customerService;
    
    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }
    
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['onUserCreated', EventPriorities::POST_WRITE],
        ];
    }
    
    public function onUserCreated(ViewEvent $event): void
    {
        $user = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();
        
        // Only process POST requests for User entities
        if (!$user instanceof User || $method !== 'POST') {
            return;
        }
        
        // Create a Stripe customer for the new user
        if (!$user->getStripeCustomerId()) {
            $this->customerService->getOrCreateCustomer($user);
        }
    }
}
```

### 2. Register the API Platform Event Subscriber

```yaml
# config/services.yaml
services:
    # ...
    App\EventSubscriber\ApiUserCreationSubscriber:
        tags:
            - { name: kernel.event_subscriber }
```

## Best Practices

1. **Error Handling**: Implement proper error handling in your event subscribers
2. **Logging**: Log important events for debugging and auditing purposes
3. **Idempotency**: Ensure your event handlers are idempotent, as events may be dispatched multiple times
4. **Transaction Management**: Use database transactions to ensure data consistency
5. **Event Propagation**: Be mindful of event propagation and stopping it when necessary
6. **Performance**: Keep event handlers lightweight and consider using message queues for time-consuming operations
