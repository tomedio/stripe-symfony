---
layout: default
title: Installation
parent: Getting Started
nav_order: 1
---

# Installation
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

1. TOC
{:toc}

---

## Requirements

Before installing the Stripe Bundle, make sure your system meets the following requirements:

- PHP 8.1 or higher
- Symfony 6.0 or higher
- Doctrine ORM 2.10 or higher
- API Platform 3.0 or higher
- Stripe PHP SDK 10.0 or higher

## Installation Steps

### Step 1: Configure Symfony Flex (Optional)

This bundle provides custom Symfony Flex recipes to simplify the installation process. To use them, add the following to your application's `composer.json` file:

```json
{
    "extra": {
        "symfony": {
            "endpoint": [
                "https://raw.githubusercontent.com/tomedio/flex-recipes/main/index.json",
                "flex://defaults"
            ]
        }
    }
}
```

### Step 2: Install the bundle

Use Composer to install the bundle:

```bash
composer require tomedio/stripe-symfony
```

### Step 3: Register the bundle

If you're using Symfony Flex, the bundle should be automatically registered. If not, add it to your `config/bundles.php`:

```php
// config/bundles.php
return [
    // ...
    Tomedio\StripeBundle\StripeBundle::class => ['all' => true],
];
```

### Step 4: Add environment variables

The bundle will automatically create the necessary configuration files during installation. You just need to add the required environment variables to your `.env` file:

```
# .env
STRIPE_API_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_SUCCESS_URL=https://your-domain.com/payment/success
STRIPE_CANCEL_URL=https://your-domain.com/payment/cancel
```

{: .warning }
Never commit your Stripe API key to version control. Always use environment variables.

### Step 5: Customize the configuration (optional)

The bundle creates a default configuration file at `config/packages/stripe_bundle.yaml`. You can customize it to add subscription plans or change other settings:

```yaml
# config/packages/stripe_bundle.yaml
stripe_bundle:
    api_key: '%env(STRIPE_API_KEY)%'
    webhook_secret: '%env(STRIPE_WEBHOOK_SECRET)%'
    success_url: '%env(STRIPE_SUCCESS_URL)%'
    cancel_url: '%env(STRIPE_CANCEL_URL)%'
    subscription_plans:
        - id: basic
          name: 'Basic Plan'
          description: 'Basic features'
          amount: 999
          currency: 'usd'
          interval: 'month'
        - id: premium
          name: 'Premium Plan'
          description: 'Premium features'
          amount: 1999
          currency: 'usd'
          interval: 'month'
          trial_period_days: 14
```

## Verifying the Installation

To verify that the bundle is installed correctly, run the following command:

```bash
php bin/console debug:container stripe
```

You should see several services related to the Stripe Bundle.

## Next Steps

Now that you have installed the Stripe Bundle, you can:

1. [Configure your subscription plans]({{ site.baseurl }}{% link _docs/getting-started/subscription-plans.md %})
2. [Implement the required interfaces]({{ site.baseurl }}{% link _docs/integration/implementing-interfaces.md %})
3. [Set up webhook handling]({{ site.baseurl }}{% link _docs/features/webhooks.md %})
