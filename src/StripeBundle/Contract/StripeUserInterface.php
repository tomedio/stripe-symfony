<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Contract;

use Symfony\Component\Security\Core\User\UserInterface;
use Tomedio\StripeBundle\Enum\Currency;

interface StripeUserInterface extends UserInterface
{
    /**
     * Get the Stripe customer ID associated with this entity.
     */
    public function getStripeCustomerId(): ?string;

    /**
     * Set the Stripe customer ID for this entity.
     */
    public function setStripeCustomerId(string $stripeCustomerId): self;

    /**
     * Get a unique identifier for this customer.
     */
    public function getUserIdentifier(): string;

    /**
     * Get the customer's email address for Stripe communications.
     */
    public function getEmail(): string;

    /**
     * Get the customer's name (optional).
     */
    public function getName(): ?string;

    /**
     * Get the billing address for this customer.
     */
    public function getBillingAddress(): ?AddressInterface;

    /**
     * Set the billing address for this customer.
     */
    public function setBillingAddress(?AddressInterface $address): self;

    /**
     * Get the customer's balance in the smallest currency unit (e.g., cents for USD).
     */
    public function getStripeBalance(): ?int;

    /**
     * Set the customer's balance.
     */
    public function setStripeBalance(?int $balance): self;

    /**
     * Get the currency of the customer's balance.
     */
    public function getStripeBalanceCurrency(): ?Currency;

    /**
     * Set the currency of the customer's balance.
     */
    public function setStripeBalanceCurrency(?Currency $currency): self;

    /**
     * Get the active subscription for this customer.
     */
    public function getActiveSubscription(): ?SubscriptionInterface;

    /**
     * Set the active subscription for this customer.
     */
    public function setActiveSubscription(?SubscriptionInterface $subscription): self;

    /**
     * Get the date when the customer was created in Stripe.
     */
    public function getStripeCreatedAt(): ?\DateTimeInterface;

    /**
     * Set the date when the customer was created in Stripe.
     */
    public function setStripeCreatedAt(?\DateTimeInterface $createdAt): self;

    /**
     * Get the user's credits balance.
     */
    public function getCreditsBalance(): int;

    /**
     * Set the user's credits balance.
     */
    public function setCreditsBalance(int $creditsBalance): self;
}
