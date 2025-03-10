<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Event;

use Stripe\Subscription as StripeSubscription;
use Symfony\Contracts\EventDispatcher\Event;
use Tomedio\StripeBundle\Contract\SubscriptionInterface;
use Tomedio\StripeBundle\Contract\StripeUserInterface;

class SubscriptionCreatedEvent extends Event
{
    private StripeUserInterface $user;
    private StripeSubscription $stripeSubscription;
    private ?SubscriptionInterface $subscription = null;

    public function __construct(StripeUserInterface $user, StripeSubscription $stripeSubscription)
    {
        $this->user = $user;
        $this->stripeSubscription = $stripeSubscription;
    }

    public function getUser(): StripeUserInterface
    {
        return $this->user;
    }

    public function getStripeSubscription(): StripeSubscription
    {
        return $this->stripeSubscription;
    }

    public function getSubscription(): ?SubscriptionInterface
    {
        return $this->subscription;
    }

    public function setSubscription(SubscriptionInterface $subscription): self
    {
        $this->subscription = $subscription;
        return $this;
    }

    public function hasSubscription(): bool
    {
        return $this->subscription !== null;
    }
}
