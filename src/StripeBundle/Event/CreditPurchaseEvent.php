<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Tomedio\StripeBundle\Contract\StripeUserInterface;
use Tomedio\StripeBundle\Enum\Currency;

class CreditPurchaseEvent extends Event
{
    private StripeUserInterface $user;
    private int $credits;
    private string $description;
    private string $paymentIntentId;
    private int $amount;
    private Currency $currency;

    public function __construct(
        StripeUserInterface $user,
        int $credits,
        string $description,
        string $paymentIntentId,
        int $amount,
        Currency $currency
    ) {
        $this->user = $user;
        $this->credits = $credits;
        $this->description = $description;
        $this->paymentIntentId = $paymentIntentId;
        $this->amount = $amount;
        $this->currency = $currency;
    }

    public function getUser(): StripeUserInterface
    {
        return $this->user;
    }

    public function getCredits(): int
    {
        return $this->credits;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPaymentIntentId(): string
    {
        return $this->paymentIntentId;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }
}
