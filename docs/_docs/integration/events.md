---
layout: default
title: Events
parent: Integration
nav_order: 3
---

# Events
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

1. TOC
{:toc}

---

## Overview

The Stripe Bundle uses Symfony's event system to communicate between different components and to allow your application to react to various events. This approach provides a clean separation of concerns and makes the bundle highly extensible.

This page provides a comprehensive reference of all events dispatched by the bundle that you can listen to in your application.

## Subscription Plan Events

These events are dispatched during the subscription plan management process.

### SubscriptionPlanListEvent

Dispatched when the bundle needs to retrieve all subscription plans from your application.

- **Event Class**: `Tomedio\StripeBundle\Event\SubscriptionPlanListEvent`
- **Event Name**: `stripe.plan.list`
- **Purpose**: Retrieve all subscription plans from your application's database
- **Methods**:
  - `getPlans()`: Get the plans that have been set
  - `setPlans(array $plans)`: Set the plans to be returned to the bundle

### SubscriptionPlanLoadEvent

Dispatched when the bundle needs to load a specific subscription plan.

- **Event Class**: `Tomedio\StripeBundle\Event\SubscriptionPlanLoadEvent`
- **Event Name**: `stripe.plan.load`
- **Purpose**: Load a specific plan from your application's database or create a new one if it doesn't exist
- **Methods**:
  - `getPlanId()`: Get the ID of the plan to load
  - `getConfig()`: Get the configuration for the plan
  - `getPlan()`: Get the plan that has been set
  - `setPlan(SubscriptionPlanInterface $plan)`: Set the plan to be returned to the bundle
  - `hasPlan()`: Check if a plan has been set

### SubscriptionPlanCreateEvent

Dispatched when a new subscription plan needs to be created.

- **Event Class**: `Tomedio\StripeBundle\Event\SubscriptionPlanCreateEvent`
- **Event Name**: `stripe.plan.create`
- **Purpose**: Create a new subscription plan in your application's database
- **Methods**:
  - `getPlan()`: Get the plan to be created

### SubscriptionPlanUpdateEvent

Dispatched when an existing subscription plan needs to be updated.

- **Event Class**: `Tomedio\StripeBundle\Event\SubscriptionPlanUpdateEvent`
- **Event Name**: `stripe.plan.update`
- **Purpose**: Update an existing subscription plan in your application's database
- **Methods**:
  - `getPlan()`: Get the plan to be updated

### SubscriptionPlanDeleteEvent

Dispatched when a subscription plan needs to be deleted or deactivated.

- **Event Class**: `Tomedio\StripeBundle\Event\SubscriptionPlanDeleteEvent`
- **Event Name**: `stripe.plan.delete`
- **Purpose**: Check if a plan can be safely deleted and mark it for deletion if possible
- **Methods**:
  - `getPlan()`: Get the plan to be deleted
  - `setCanDelete(bool $canDelete, ?string $reason = null)`: Set whether the plan can be deleted and optionally provide a reason
  - `canDelete()`: Check if the plan can be deleted
  - `getReason()`: Get the reason why the plan cannot be deleted

### SubscriptionPlanSyncEvent

Dispatched during the synchronization of subscription plans with Stripe.

- **Event Class**: `Tomedio\StripeBundle\Event\SubscriptionPlanSyncEvent`
- **Event Name**: `stripe.plan.sync`
- **Purpose**: Notify your application that a plan is being synchronized with Stripe
- **Methods**:
  - `getPlanId()`: Get the ID of the plan being synchronized
  - `getConfigData()`: Get the configuration data for the plan

## Subscription Events

These events are dispatched during subscription management.

### SubscriptionCreatedEvent

Dispatched when a new subscription is created in Stripe.

- **Event Class**: `Tomedio\StripeBundle\Event\SubscriptionCreatedEvent`
- **Event Name**: `stripe.subscription.created`
- **Purpose**: Create a new subscription entity in your application's database
- **Methods**:
  - `getUser()`: Get the user who created the subscription
  - `getStripeSubscription()`: Get the Stripe subscription object
  - `getSubscription()`: Get the subscription entity that has been set
  - `setSubscription(SubscriptionInterface $subscription)`: Set the subscription entity to be returned to the bundle
  - `hasSubscription()`: Check if a subscription entity has been set

## Invoice Events

These events are dispatched during invoice management.

### InvoiceCreatedEvent

Dispatched when a new invoice is created in Stripe.

- **Event Class**: `Tomedio\StripeBundle\Event\InvoiceCreatedEvent`
- **Event Name**: `stripe.invoice.created`
- **Purpose**: Create a new invoice entity in your application's database
- **Methods**:
  - `getUser()`: Get the user associated with the invoice
  - `getStripeInvoice()`: Get the Stripe invoice object
  - `getInvoice()`: Get the invoice entity that has been set
  - `setInvoice(StripeInvoiceInterface $invoice)`: Set the invoice entity to be returned to the bundle
  - `hasInvoice()`: Check if an invoice entity has been set

### InvoiceSubscriptionEvent

Dispatched when an invoice is associated with a subscription.

- **Event Class**: `Tomedio\StripeBundle\Event\InvoiceSubscriptionEvent`
- **Event Name**: `stripe.invoice.subscription`
- **Purpose**: Find the subscription entity associated with a Stripe subscription ID
- **Methods**:
  - `getStripeSubscriptionId()`: Get the Stripe subscription ID
  - `getSubscription()`: Get the subscription entity that has been set
  - `setSubscription(SubscriptionInterface $subscription)`: Set the subscription entity to be returned to the bundle
  - `hasSubscription()`: Check if a subscription entity has been set

## Credit Events

These events are dispatched during credit management.

### CreditPurchaseEvent

Dispatched when a user purchases credits.

- **Event Class**: `Tomedio\StripeBundle\Event\CreditPurchaseEvent`
- **Event Name**: `stripe.credits.purchase`
- **Purpose**: Notify your application that a user has purchased credits
- **Methods**:
  - `getUser()`: Get the user who purchased the credits
  - `getCredits()`: Get the number of credits purchased
  - `getDescription()`: Get the description of the purchase
  - `getPaymentIntentId()`: Get the Stripe payment intent ID
  - `getAmount()`: Get the amount paid in the smallest currency unit (e.g., cents)
  - `getCurrency()`: Get the currency used for the payment

### CreditUsageEvent

Dispatched when a user uses credits.

- **Event Class**: `Tomedio\StripeBundle\Event\CreditUsageEvent`
- **Event Name**: `stripe.credits.usage`
- **Purpose**: Notify your application that a user has used credits
- **Methods**:
  - `getUser()`: Get the user who used the credits
  - `getCredits()`: Get the number of credits used
  - `getDescription()`: Get the description of the usage
  - `getReferenceId()`: Get the reference ID for the transaction

## User Events

These events are dispatched during user management.

### StripeUserLoadEvent

Dispatched when the bundle needs to find a user by their Stripe customer ID.

- **Event Class**: `Tomedio\StripeBundle\Event\StripeUserLoadEvent`
- **Event Name**: `stripe.user.load`
- **Purpose**: Find a user entity by their Stripe customer ID
- **Methods**:
  - `getStripeCustomerId()`: Get the Stripe customer ID
  - `getUserId()`: Get the user ID from Stripe metadata
  - `getUser()`: Get the user entity that has been set
  - `setUser(?StripeUserInterface $user)`: Set the user entity to be returned to the bundle

### FindUserByStripeCustomerEvent

Dispatched when the bundle needs to find a user by both their Stripe customer ID and user ID.

- **Event Class**: `Tomedio\StripeBundle\Event\FindUserByStripeCustomerEvent`
- **Event Name**: `stripe.user.find_by_customer`
- **Purpose**: Find a user entity by their Stripe customer ID and user ID
- **Methods**:
  - `getStripeCustomerId()`: Get the Stripe customer ID
  - `getUserId()`: Get the user ID
  - `getUser()`: Get the user entity that has been set
  - `setUser(?StripeUserInterface $user)`: Set the user entity to be returned to the bundle

## Webhook Events

These events are dispatched when Stripe webhook events are received.

### StripeWebhookEvent

Dispatched for all Stripe webhook events.

- **Event Class**: `Tomedio\StripeBundle\Event\StripeWebhookEvent`
- **Event Name**: `stripe.webhook` (generic) and `stripe.[event_type]` (specific)
- **Purpose**: Process Stripe webhook events
- **Methods**:
  - `getStripeEvent()`: Get the Stripe event object
  - `getType()`: Get the type of the Stripe event
  - `getData()`: Get the data object from the Stripe event

### Specific Webhook Events

The bundle converts Stripe webhook events into Symfony events with the prefix `stripe.` followed by the Stripe event name with underscores instead of dots. For example, the Stripe event `customer.subscription.created` becomes the Symfony event `stripe.customer_subscription_created`.

Here are some common webhook events:

- `stripe.customer_subscription_created`: Dispatched when a subscription is created
- `stripe.customer_subscription_updated`: Dispatched when a subscription is updated
- `stripe.customer_subscription_deleted`: Dispatched when a subscription is deleted
- `stripe.invoice_paid`: Dispatched when an invoice is paid
- `stripe.invoice_payment_failed`: Dispatched when an invoice payment fails
- `stripe.checkout_session_completed`: Dispatched when a checkout session is completed

## Implementing Event Listeners

### 1. Create an Event Subscriber

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
        $credits = $event->getCredits();
        
        // You can perform additional actions when credits are purchased
        // For example, send a notification to the user
    }
    
    public function onCreditUsage(CreditUsageEvent $event): void
    {
        $user = $event->getUser();
        $credits = $event->getCredits();
        
        // You can perform additional actions when credits are used
        // For example, log usage statistics or send a notification when the balance is low
        if ($user->getCreditsBalance() < 10) {
            // Send a low balance notification
        }
    }
}
```

### 2. Register the Event Subscriber

```yaml
# config/services.yaml
services:
    # ...
    App\EventSubscriber\CreditEventSubscriber:
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
