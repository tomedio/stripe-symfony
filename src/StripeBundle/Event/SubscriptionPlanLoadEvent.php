<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Tomedio\StripeBundle\Contract\SubscriptionPlanInterface;
use Tomedio\StripeBundle\Model\SubscriptionPlanConfig;

class SubscriptionPlanLoadEvent extends Event
{
    private string $planId;
    private SubscriptionPlanConfig $config;
    private ?SubscriptionPlanInterface $plan = null;

    public function __construct(string $planId, SubscriptionPlanConfig $config)
    {
        $this->planId = $planId;
        $this->config = $config;
    }

    public function getPlanId(): string
    {
        return $this->planId;
    }

    public function getConfig(): SubscriptionPlanConfig
    {
        return $this->config;
    }

    public function getPlan(): ?SubscriptionPlanInterface
    {
        return $this->plan;
    }

    public function setPlan(?SubscriptionPlanInterface $plan): self
    {
        $this->plan = $plan;
        return $this;
    }

    public function hasPlan(): bool
    {
        return $this->plan !== null;
    }
}
