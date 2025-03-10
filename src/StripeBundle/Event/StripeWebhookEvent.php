<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Event;

use Stripe\Event as StripeEvent;
use Symfony\Contracts\EventDispatcher\Event;

class StripeWebhookEvent extends Event
{
    private StripeEvent $stripeEvent;

    public function __construct(StripeEvent $stripeEvent)
    {
        $this->stripeEvent = $stripeEvent;
    }

    public function getStripeEvent(): StripeEvent
    {
        return $this->stripeEvent;
    }

    public function getType(): string
    {
        return $this->stripeEvent->type;
    }

    public function getData(): object
    {
        return $this->stripeEvent->data->object;
    }
}
