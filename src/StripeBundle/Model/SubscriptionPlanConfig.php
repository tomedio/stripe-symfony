<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Model;

use Tomedio\StripeBundle\Enum\BillingInterval;
use Tomedio\StripeBundle\Enum\Currency;

class SubscriptionPlanConfig
{
    private string $id;
    private string $name;
    private ?string $description;
    private int $amount;
    private Currency $currency;
    private BillingInterval $interval;
    private ?int $trialPeriodDays;

    public function __construct(
        string $id,
        string $name,
        int $amount,
        Currency $currency,
        BillingInterval $interval,
        ?string $description = null,
        ?int $trialPeriodDays = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->interval = $interval;
        $this->trialPeriodDays = $trialPeriodDays;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['name'],
            $data['amount'],
            Currency::from($data['currency'] ?? 'usd'),
            BillingInterval::from($data['interval'] ?? 'month'),
            $data['description'] ?? null,
            $data['trial_period_days'] ?? null
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getInterval(): BillingInterval
    {
        return $this->interval;
    }

    public function getTrialPeriodDays(): ?int
    {
        return $this->trialPeriodDays;
    }
}
