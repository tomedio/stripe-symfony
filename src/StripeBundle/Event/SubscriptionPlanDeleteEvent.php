<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Tomedio\StripeBundle\Contract\SubscriptionPlanInterface;

class SubscriptionPlanDeleteEvent extends Event
{
    private SubscriptionPlanInterface $plan;
    private bool $canDelete = true;
    private ?string $reason = null;

    public function __construct(SubscriptionPlanInterface $plan)
    {
        $this->plan = $plan;
    }

    public function getPlan(): SubscriptionPlanInterface
    {
        return $this->plan;
    }

    public function canDelete(): bool
    {
        return $this->canDelete;
    }

    public function setCanDelete(bool $canDelete, ?string $reason = null): self
    {
        $this->canDelete = $canDelete;
        $this->reason = $reason;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }
}
