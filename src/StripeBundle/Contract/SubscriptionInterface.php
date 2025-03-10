<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Contract;

use DateTimeInterface;
use Tomedio\StripeBundle\Enum\SubscriptionStatus;

interface SubscriptionInterface
{
    /**
     * Get the unique identifier for this subscription.
     */
    public function getId(): string;

    /**
     * Get the user associated with this subscription.
     */
    public function getUser(): StripeUserInterface;

    /**
     * Set the user for this subscription.
     */
    public function setUser(StripeUserInterface $user): self;

    /**
     * Get the subscription plan.
     */
    public function getPlan(): SubscriptionPlanInterface;

    /**
     * Set the subscription plan.
     */
    public function setPlan(SubscriptionPlanInterface $plan): self;

    /**
     * Get the Stripe subscription ID.
     */
    public function getStripeSubscriptionId(): ?string;

    /**
     * Set the Stripe subscription ID.
     */
    public function setStripeSubscriptionId(string $stripeSubscriptionId): self;

    /**
     * Get the subscription status.
     */
    public function getStatus(): SubscriptionStatus;

    /**
     * Set the subscription status.
     */
    public function setStatus(SubscriptionStatus $status): self;

    /**
     * Get the subscription start date.
     */
    public function getStartDate(): ?DateTimeInterface;

    /**
     * Set the subscription start date.
     */
    public function setStartDate(DateTimeInterface $startDate): self;

    /**
     * Get the subscription end date.
     */
    public function getEndDate(): ?DateTimeInterface;

    /**
     * Set the subscription end date.
     */
    public function setEndDate(?DateTimeInterface $endDate): self;

    /**
     * Get the trial end date, if applicable.
     */
    public function getTrialEndDate(): ?DateTimeInterface;

    /**
     * Set the trial end date.
     */
    public function setTrialEndDate(?DateTimeInterface $trialEndDate): self;

    /**
     * Check if the subscription is active.
     */
    public function isActive(): bool;

    /**
     * Check if the subscription is in trial period.
     */
    public function isOnTrial(): bool;

    /**
     * Check if the subscription has ended.
     */
    public function hasEnded(): bool;
}
