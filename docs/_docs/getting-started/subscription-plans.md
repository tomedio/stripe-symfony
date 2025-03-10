---
layout: default
title: Subscription Plans
parent: Getting Started
nav_order: 2
---

# Subscription Plans
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

1. TOC
{:toc}

---

## Overview

Subscription plans are a core concept in the Stripe Bundle. They define the pricing structure and billing intervals for your SaaS application's subscription offerings. The bundle provides a way to define these plans in your Symfony configuration and synchronize them with both your database and Stripe.

## Defining Subscription Plans

Subscription plans are defined in your Symfony configuration file:

```yaml
# config/packages/stripe_bundle.yaml
stripe_bundle:
    # ... other configuration
    subscription_plans:
        - id: basic
          name: 'Basic Plan'
          description: 'Basic features'
          amount: 999  # Amount in cents
          currency: 'usd'
          interval: 'month'
        - id: premium
          name: 'Premium Plan'
          description: 'Premium features'
          amount: 1999  # Amount in cents
          currency: 'usd'
          interval: 'month'
          trial_period_days: 14
        - id: enterprise
          name: 'Enterprise Plan'
          description: 'All features'
          amount: 4999  # Amount in cents
          currency: 'usd'
          interval: 'month'
          trial_period_days: 30
```

### Plan Properties

| Property | Type | Description | Required |
|----------|------|-------------|----------|
| `id` | string | Unique identifier for the plan | Yes |
| `name` | string | Display name for the plan | Yes |
| `description` | string | Description of what the plan offers | No |
| `amount` | integer | Price in cents (or smallest currency unit) | Yes |
| `currency` | string | Three-letter ISO currency code | Yes |
| `interval` | string | Billing interval (`day`, `week`, `month`, or `year`) | Yes |
| `trial_period_days` | integer | Number of trial days | No |

## Synchronizing Plans

The bundle includes a command to synchronize your subscription plans with both your database and Stripe:

```bash
php bin/console stripe:sync-plans
```

This command handles all synchronization cases:

1. **New plans in configuration**: Creates them in your database and syncs them to Stripe
2. **Updated plans in configuration**: Updates them in your database and syncs changes to Stripe
3. **Removed plans from configuration**: Checks if they can be safely removed (no active subscriptions), then deactivates them in Stripe and marks them for deletion in your database

### Automatic Synchronization

You can set up a cron job to run the synchronization command periodically:

```
# Run every day at midnight
0 0 * * * /path/to/your/project/bin/console stripe:sync-plans
```

## Implementing Event Listeners

To fully integrate the subscription plan synchronization, you need to implement event listeners for the following events:

### Required Events

| Event | Purpose |
|-------|---------|
| `stripe.plan.list` | Return all subscription plans from the database |
| `stripe.plan.load` | Load a plan from the database or create a new one if it doesn't exist |
| `stripe.plan.update` | Update a plan in the database |
| `stripe.plan.delete` | Check if a plan can be safely deleted and mark it for deletion if possible |

### Example Implementation

See the [Implementing Event Listeners]({% link _docs/integration/event-listeners.md %}) page for a detailed example of how to implement these event listeners.

## Next Steps

Now that you understand how to configure subscription plans, you can:

1. [Implement the required interfaces]({% link _docs/integration/implementing-interfaces.md %})
2. [Set up webhook handling]({% link _docs/features/webhooks.md %})
3. [Create a subscription checkout flow]({% link _docs/features/subscription-checkout.md %})
