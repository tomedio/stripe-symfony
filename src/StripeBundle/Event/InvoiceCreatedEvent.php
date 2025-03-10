<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Event;

use Stripe\Invoice as StripeInvoice;
use Symfony\Contracts\EventDispatcher\Event;
use Tomedio\StripeBundle\Contract\StripeInvoiceInterface;
use Tomedio\StripeBundle\Contract\StripeUserInterface;

class InvoiceCreatedEvent extends Event
{
    private StripeUserInterface $user;
    private StripeInvoice $stripeInvoice;
    private ?StripeInvoiceInterface $invoice = null;

    public function __construct(StripeUserInterface $user, StripeInvoice $stripeInvoice)
    {
        $this->user = $user;
        $this->stripeInvoice = $stripeInvoice;
    }

    public function getUser(): StripeUserInterface
    {
        return $this->user;
    }

    public function getStripeInvoice(): StripeInvoice
    {
        return $this->stripeInvoice;
    }

    public function getInvoice(): ?StripeInvoiceInterface
    {
        return $this->invoice;
    }

    public function setInvoice(StripeInvoiceInterface $invoice): self
    {
        $this->invoice = $invoice;
        return $this;
    }

    public function hasInvoice(): bool
    {
        return $this->invoice !== null;
    }
}
