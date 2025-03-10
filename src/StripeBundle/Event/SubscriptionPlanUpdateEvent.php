<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Tomedio\StripeBundle\Contract\SubscriptionPlanInterface;

class SubscriptionPlanUpdateEvent extends Event
{
    private SubscriptionPlanInterface $plan;

    public function __construct(SubscriptionPlanInterface $plan)
    {
        $this->plan = $plan;
    }

    public function getPlan(): SubscriptionPlanInterface
    {
        return $this->plan;
    }
}
