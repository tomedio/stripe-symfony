<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Tomedio\StripeBundle\Contract\SubscriptionPlanInterface;

class SubscriptionPlanListEvent extends Event
{
    /**
     * @var SubscriptionPlanInterface[]
     */
    private array $plans = [];

    /**
     * @return SubscriptionPlanInterface[]
     */
    public function getPlans(): array
    {
        return $this->plans;
    }

    /**
     * @param SubscriptionPlanInterface[] $plans
     */
    public function setPlans(array $plans): self
    {
        $this->plans = $plans;
        return $this;
    }

    public function addPlan(SubscriptionPlanInterface $plan): self
    {
        $this->plans[] = $plan;
        return $this;
    }
}
