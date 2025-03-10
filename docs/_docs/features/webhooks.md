---
layout: default
title: Webhooks
parent: Features
nav_order: 1
---

# Webhook Handling
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

1. TOC
{:toc}

---

## Overview

Webhooks are a crucial part of integrating with Stripe. They allow Stripe to notify your application when events happen in your account, such as successful payments, failed charges, or subscription updates.

The Stripe Bundle provides a comprehensive webhook handling system that:

1. Validates incoming webhook requests from Stripe
2. Converts Stripe events into Symfony events
3. Allows you to handle these events in your application

## Configuration

To use webhooks, you need to configure your webhook secret in your Stripe Bundle configuration:

```yaml
# config/packages/stripe_bundle.yaml
stripe_bundle:
    # ... other configuration
    webhook_secret: '%env(STRIPE_WEBHOOK_SECRET)%'
```

You can get your webhook secret from the Stripe Dashboard:

1. Go to the [Stripe Dashboard](https://dashboard.stripe.com/)
2. Navigate to Developers > Webhooks
3. Add a new endpoint with your application's URL (e.g., `https://your-domain.com/stripe/webhook`)
4. Copy the signing secret and add it to your `.env` file:

```
STRIPE_WEBHOOK_SECRET=whsec_...
```

## Webhook Endpoint

The bundle automatically registers a webhook endpoint at `/stripe/webhook`. You can customize this path in your routes configuration:

```yaml
# config/routes.yaml
stripe_bundle:
    resource: '@StripeBundle/Controller/'
    type: annotation
    prefix: /custom-path  # Optional: customize the path prefix
```

## Handling Webhook Events

The bundle converts Stripe webhook events into Symfony events with the prefix `stripe.` followed by the Stripe event name. For example, the Stripe event `customer.subscription.created` becomes the Symfony event `stripe.customer_subscription_created`.

To handle these events, create an event subscriber:

```php
<?php

namespace App\EventSubscriber;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tomedio\StripeBundle\Event\StripeWebhookEvent;

class StripeWebhookSubscriber implements EventSubscriberInterface
{
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    
    public function __construct(
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }
    
    public static function getSubscribedEvents(): array
    {
        return [
            'stripe.customer_subscription_created' => 'onSubscriptionCreated',
            'stripe.customer_subscription_updated' => 'onSubscriptionUpdated',
            'stripe.customer_subscription_deleted' => 'onSubscriptionDeleted',
            'stripe.invoice_paid' => 'onInvoicePaid',
            'stripe.checkout_session_completed' => 'onCheckoutSessionCompleted',
        ];
    }
    
    public function onSubscriptionCreated(StripeWebhookEvent $event): void
    {
        $stripeEvent = $event->getStripeEvent();
        $subscription = $stripeEvent->data->object;
        $customerId = $subscription->customer;
        
        $user = $this->userRepository->findOneByStripeCustomerId($customerId);
        if (!$user) {
            return;
        }
        
        // Handle subscription creation
        // ...
        
        $this->entityManager->flush();
    }
    
    // Implement other event handlers...
}
```

## Important Webhook Events

Here are some of the most important webhook events you might want to handle:

| Stripe Event | Symfony Event | Description |
|--------------|---------------|-------------|
| `checkout.session.completed` | `stripe.checkout_session_completed` | A checkout session has been completed |
| `customer.subscription.created` | `stripe.customer_subscription_created` | A customer subscription has been created |
| `customer.subscription.updated` | `stripe.customer_subscription_updated` | A customer subscription has been updated |
| `customer.subscription.deleted` | `stripe.customer_subscription_deleted` | A customer subscription has been deleted |
| `invoice.paid` | `stripe.invoice_paid` | An invoice has been paid |
| `invoice.payment_failed` | `stripe.invoice_payment_failed` | An invoice payment has failed |

## Testing Webhooks

During development, you can use the Stripe CLI to test webhooks locally:

1. Install the [Stripe CLI](https://stripe.com/docs/stripe-cli)
2. Login to your Stripe account:

```bash
stripe login
```

3. Forward webhook events to your local server:

```bash
stripe listen --forward-to http://localhost:8000/stripe/webhook
```

4. Trigger test webhook events:

```bash
stripe trigger checkout.session.completed
```

## Best Practices

1. **Idempotency**: Ensure your webhook handlers are idempotent, as Stripe may send the same event multiple times
2. **Error Handling**: Implement proper error handling in your webhook handlers
3. **Logging**: Log webhook events for debugging and auditing purposes
4. **Verification**: Always verify the webhook signature to ensure the request is coming from Stripe
5. **Async Processing**: For time-consuming operations, consider using a message queue to process webhook events asynchronously
