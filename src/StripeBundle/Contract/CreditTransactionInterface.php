<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Contract;

use DateTimeInterface;
use Tomedio\StripeBundle\Enum\Currency;
use Tomedio\StripeBundle\Enum\CreditTransactionType;

interface CreditTransactionInterface
{
    /**
     * Get the unique identifier for this transaction.
     */
    public function getId(): string;

    /**
     * Get the user associated with this transaction.
     */
    public function getUser(): StripeUserInterface;

    /**
     * Set the user for this transaction.
     */
    public function setUser(StripeUserInterface $user): self;

    /**
     * Get the amount of credits in this transaction.
     * Positive for credits added, negative for credits used.
     */
    public function getAmount(): int;

    /**
     * Set the amount of credits in this transaction.
     */
    public function setAmount(int $amount): self;

    /**
     * Get the balance after this transaction.
     */
    public function getBalanceAfter(): int;

    /**
     * Set the balance after this transaction.
     */
    public function setBalanceAfter(int $balanceAfter): self;

    /**
     * Get the description of this transaction.
     */
    public function getDescription(): string;

    /**
     * Set the description of this transaction.
     */
    public function setDescription(string $description): self;

    /**
     * Get the date of this transaction.
     */
    public function getTransactionDate(): DateTimeInterface;

    /**
     * Set the date of this transaction.
     */
    public function setTransactionDate(DateTimeInterface $transactionDate): self;

    /**
     * Get the type of this transaction.
     */
    public function getType(): CreditTransactionType;

    /**
     * Set the type of this transaction.
     */
    public function setType(CreditTransactionType $type): self;

    /**
     * Get the reference ID for this transaction (e.g., Stripe payment intent ID).
     */
    public function getReferenceId(): ?string;

    /**
     * Set the reference ID for this transaction.
     */
    public function setReferenceId(?string $referenceId): self;

    /**
     * Get the monetary amount associated with this transaction, if applicable.
     */
    public function getMonetaryAmount(): ?int;

    /**
     * Set the monetary amount associated with this transaction.
     */
    public function setMonetaryAmount(?int $monetaryAmount): self;

    /**
     * Get the currency of the monetary amount, if applicable.
     */
    public function getCurrency(): ?Currency;

    /**
     * Set the currency of the monetary amount.
     */
    public function setCurrency(?Currency $currency): self;
}
