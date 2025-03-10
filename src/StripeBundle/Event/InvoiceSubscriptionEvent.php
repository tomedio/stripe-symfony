<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Tomedio\StripeBundle\Contract\SubscriptionInterface;

class InvoiceSubscriptionEvent extends Event
{
    private string $stripeSubscriptionId;
    private ?SubscriptionInterface $subscription = null;

    public function __construct(string $stripeSubscriptionId)
    {
        $this->stripeSubscriptionId = $stripeSubscriptionId;
    }

    public function getStripeSubscriptionId(): string
    {
        return $this->stripeSubscriptionId;
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
