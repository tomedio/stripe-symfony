<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Tomedio\StripeBundle\Contract\StripeUserInterface;

class CreditUsageEvent extends Event
{
    private StripeUserInterface $user;
    private int $credits;
    private string $description;
    private ?string $referenceId;

    public function __construct(
        StripeUserInterface $user,
        int $credits,
        string $description,
        ?string $referenceId = null
    ) {
        $this->user = $user;
        $this->credits = $credits;
        $this->description = $description;
        $this->referenceId = $referenceId;
    }

    public function getUser(): StripeUserInterface
    {
        return $this->user;
    }

    public function getCredits(): int
    {
        return $this->credits;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getReferenceId(): ?string
    {
        return $this->referenceId;
    }
}
