<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Tomedio\StripeBundle\Contract\StripeUserInterface;

class FindUserByStripeCustomerEvent extends Event
{
    private string $stripeCustomerId;
    private string $userId;
    private ?StripeUserInterface $user = null;

    public function __construct(string $stripeCustomerId, string $userId)
    {
        $this->stripeCustomerId = $stripeCustomerId;
        $this->userId = $userId;
    }

    public function getStripeCustomerId(): string
    {
        return $this->stripeCustomerId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getUser(): ?StripeUserInterface
    {
        return $this->user;
    }

    public function setUser(?StripeUserInterface $user): self
    {
        $this->user = $user;
        return $this;
    }
}
