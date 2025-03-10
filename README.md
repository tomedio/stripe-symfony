# Stripe Bundle for Symfony

A Symfony bundle for seamless integration of Stripe payments into your Symfony applications with Doctrine ORM and API Platform support.

[![Deploy Documentation](https://github.com/tomedio/stripe-symfony/actions/workflows/deploy-docs.yml/badge.svg)](https://github.com/tomedio/stripe-symfony/actions/workflows/deploy-docs.yml)

## Features

- **Symfony Integration**: Seamlessly integrates with Symfony, Doctrine ORM, and API Platform
- **Customer Management**: Easily manage customers with billing address support
- **Subscription Handling**: Complete subscription lifecycle with trial period support
- **Invoice Tracking**: Track and manage invoices with automatic status updates
- **Webhook Processing**: Built-in webhook handling with Symfony events
- **Pay-per-action Support**: Implement usage-based billing for your API
- **Prepaid Credits System**: Allow users to purchase and use credits
- **Secure Checkout**: Process payments securely via Stripe Checkout

## Quick Installation

### 1. Install via Composer

```bash
composer require tomedio/stripe-symfony
```

### 2. Register the bundle

```php
// config/bundles.php
return [
    // ...
    Tomedio\StripeBundle\StripeBundle::class => ['all' => true],
];
```

### 3. Add environment variables

The bundle will automatically create the necessary configuration files during installation. You just need to add the required environment variables to your `.env` file:

```
# .env
STRIPE_API_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_SUCCESS_URL=https://your-domain.com/payment/success
STRIPE_CANCEL_URL=https://your-domain.com/payment/cancel
```

## Documentation

For detailed implementation guides, interface references, and examples, please visit our comprehensive documentation:

**[https://tomedio.github.io/stripe-symfony/](https://tomedio.github.io/stripe-symfony/)**

The documentation includes:

- Complete installation and configuration instructions
- Interface implementation examples
- Subscription plan management
- Webhook handling
- API Platform integration
- Pay-per-action implementation
- Credit system setup
- And much more!

## License

This bundle is released under the MIT License.
