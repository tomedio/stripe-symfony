<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Contract;

use Tomedio\StripeBundle\Enum\BillingInterval;
use Tomedio\StripeBundle\Enum\Currency;

interface SubscriptionPlanInterface
{
    /**
     * Get the unique identifier for this plan.
     */
    public function getId(): string;

    /**
     * Get the name of the plan.
     */
    public function getName(): string;

    /**
     * Get the description of the plan.
     */
    public function getDescription(): ?string;

    /**
     * Get the price amount in the smallest currency unit (e.g., cents for USD).
     */
    public function getAmount(): int;

    /**
     * Get the currency code.
     */
    public function getCurrency(): Currency;

    /**
     * Get the billing interval.
     */
    public function getInterval(): BillingInterval;

    /**
     * Get the number of trial period days, if applicable.
     */
    public function getTrialPeriodDays(): ?int;

    /**
     * Get the Stripe product ID associated with this plan.
     */
    public function getStripeProductId(): ?string;

    /**
     * Set the Stripe product ID for this plan.
     */
    public function setStripeProductId(string $stripeProductId): self;

    /**
     * Get the Stripe price ID associated with this plan.
     */
    public function getStripePriceId(): ?string;

    /**
     * Set the Stripe price ID for this plan.
     */
    public function setStripePriceId(string $stripePriceId): self;
}
