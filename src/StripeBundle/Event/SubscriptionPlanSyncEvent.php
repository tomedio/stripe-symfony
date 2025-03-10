<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class SubscriptionPlanSyncEvent extends Event
{
    private string $planId;
    private array $configData;

    public function __construct(string $planId, array $configData)
    {
        $this->planId = $planId;
        $this->configData = $configData;
    }

    public function getPlanId(): string
    {
        return $this->planId;
    }

    public function getConfigData(): array
    {
        return $this->configData;
    }
}
