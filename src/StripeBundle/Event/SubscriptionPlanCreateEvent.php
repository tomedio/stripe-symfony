<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Tomedio\StripeBundle\Contract\SubscriptionPlanInterface;

class SubscriptionPlanCreateEvent extends Event
{
    public function __construct(
        private readonly SubscriptionPlanInterface $plan
    ) {}

    public function getSubscriptionPlan(): SubscriptionPlanInterface
    {
        return $this->plan;
    }
}
