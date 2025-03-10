<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Contract;

use DateTimeInterface;
use Tomedio\StripeBundle\Enum\Currency;
use Tomedio\StripeBundle\Enum\InvoiceStatus;

interface StripeInvoiceInterface
{
    /**
     * Get the unique identifier for this invoice.
     */
    public function getId(): string;

    /**
     * Get the user associated with this invoice.
     */
    public function getUser(): StripeUserInterface;

    /**
     * Set the user for this invoice.
     */
    public function setUser(StripeUserInterface $user): self;

    /**
     * Get the subscription associated with this invoice, if any.
     */
    public function getSubscription(): ?SubscriptionInterface;

    /**
     * Set the subscription for this invoice.
     */
    public function setSubscription(?SubscriptionInterface $subscription): self;

    /**
     * Get the Stripe invoice ID.
     */
    public function getStripeInvoiceId(): string;

    /**
     * Set the Stripe invoice ID.
     */
    public function setStripeInvoiceId(string $stripeInvoiceId): self;

    /**
     * Get the invoice amount in the smallest currency unit (e.g., cents for USD).
     */
    public function getAmount(): int;

    /**
     * Set the invoice amount.
     */
    public function setAmount(int $amount): self;

    /**
     * Get the currency code.
     */
    public function getCurrency(): Currency;

    /**
     * Set the currency code.
     */
    public function setCurrency(Currency $currency): self;

    /**
     * Get the invoice status.
     */
    public function getStatus(): InvoiceStatus;

    /**
     * Set the invoice status.
     */
    public function setStatus(InvoiceStatus $status): self;

    /**
     * Get the invoice date.
     */
    public function getInvoiceDate(): DateTimeInterface;

    /**
     * Set the invoice date.
     */
    public function setInvoiceDate(DateTimeInterface $invoiceDate): self;

    /**
     * Get the due date for the invoice.
     */
    public function getDueDate(): ?DateTimeInterface;

    /**
     * Set the due date for the invoice.
     */
    public function setDueDate(?DateTimeInterface $dueDate): self;

    /**
     * Get the paid date for the invoice.
     */
    public function getPaidDate(): ?DateTimeInterface;

    /**
     * Set the paid date for the invoice.
     */
    public function setPaidDate(?DateTimeInterface $paidDate): self;

    /**
     * Check if the invoice has been paid.
     */
    public function isPaid(): bool;
}
