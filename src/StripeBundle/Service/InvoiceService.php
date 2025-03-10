<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Service;

use DateTimeImmutable;
use Stripe\Exception\ApiErrorException;
use Stripe\Invoice as StripeInvoice;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tomedio\StripeBundle\Contract\StripeInvoiceInterface;
use Tomedio\StripeBundle\Contract\StripeUserInterface;
use Tomedio\StripeBundle\Enum\Currency;
use Tomedio\StripeBundle\Enum\InvoiceStatus;
use Tomedio\StripeBundle\Event\InvoiceCreatedEvent;
use Tomedio\StripeBundle\Event\InvoiceSubscriptionEvent;

class InvoiceService
{
    private StripeClient $stripeClient;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        StripeClient $stripeClient,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->stripeClient = $stripeClient;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Retrieve an invoice from Stripe.
     *
     * @throws ApiErrorException
     */
    public function retrieveInvoice(string $invoiceId): StripeInvoice
    {
        return $this->stripeClient->execute(function () use ($invoiceId) {
            return $this->stripeClient->getClient()->invoices->retrieve($invoiceId);
        });
    }

    /**
     * Get all invoices for a user from Stripe.
     *
     * @throws ApiErrorException
     */
    public function getInvoicesForUser(StripeUserInterface $user): array
    {
        if (!$user->getStripeCustomerId()) {
            return [];
        }

        $stripeInvoices = $this->stripeClient->execute(function () use ($user) {
            return $this->stripeClient->getClient()->invoices->all([
                'customer' => $user->getStripeCustomerId(),
            ]);
        });

        return $stripeInvoices->data;
    }

    /**
     * Sync an invoice from Stripe to the local database.
     * 
     * This method updates an existing invoice with data from Stripe.
     * If the invoice doesn't exist, it dispatches an event to allow the application
     * to create a new invoice entity.
     *
     * @throws ApiErrorException
     */
    public function syncInvoiceFromStripe(
        StripeUserInterface $user,
        StripeInvoice $stripeInvoice,
        ?StripeInvoiceInterface $invoice = null
    ): StripeInvoiceInterface {
        // If no invoice is provided, dispatch an event to create a new one
        if ($invoice === null) {
            // Dispatch an event to create a new invoice
            $event = new InvoiceCreatedEvent($user, $stripeInvoice);
            $this->eventDispatcher->dispatch($event, 'stripe.invoice.created');
            
            // If the event didn't set an invoice, throw an exception
            if (!$event->hasInvoice()) {
                throw new \RuntimeException(
                    'Application must implement invoice creation by listening to the stripe.invoice.created event'
                );
            }
            
            $invoice = $event->getInvoice();
        }

        // Update the invoice with data from Stripe
        $invoice->setStripeInvoiceId($stripeInvoice->id);
        $invoice->setUser($user);

        // Set amount and currency
        $invoice->setAmount($stripeInvoice->total);
        $invoice->setCurrency(Currency::from($stripeInvoice->currency));

        // Set status
        $invoice->setStatus(InvoiceStatus::from($stripeInvoice->status));

        // Set dates
        if (isset($stripeInvoice->created)) {
            $invoiceDate = new DateTimeImmutable('@' . $stripeInvoice->created);
            $invoice->setInvoiceDate($invoiceDate);
        }

        if (isset($stripeInvoice->due_date) && $stripeInvoice->due_date > 0) {
            $dueDate = new DateTimeImmutable('@' . $stripeInvoice->due_date);
            $invoice->setDueDate($dueDate);
        } else {
            $invoice->setDueDate(null);
        }

        // Set paid date if the invoice is paid
        if ($stripeInvoice->status === InvoiceStatus::PAID->value && isset($stripeInvoice->status_transitions->paid_at)) {
            $paidDate = new DateTimeImmutable('@' . $stripeInvoice->status_transitions->paid_at);
            $invoice->setPaidDate($paidDate);
        } else {
            $invoice->setPaidDate(null);
        }

        // Set subscription if available
        if (isset($stripeInvoice->subscription)) {
            // Dispatch an event to find the subscription
            $event = new InvoiceSubscriptionEvent($stripeInvoice->subscription);
            $this->eventDispatcher->dispatch($event, 'stripe.invoice.subscription');
            
            if ($event->hasSubscription()) {
                $invoice->setSubscription($event->getSubscription());
            }
        }

        return $invoice;
    }

    /**
     * Create a PDF invoice and return the URL.
     *
     * @throws ApiErrorException
     */
    public function getInvoicePdfUrl(string $invoiceId): string
    {
        $invoice = $this->retrieveInvoice($invoiceId);
        return $invoice->invoice_pdf;
    }

    /**
     * Send an invoice to the customer by email.
     *
     * @throws ApiErrorException
     */
    public function sendInvoiceEmail(string $invoiceId): void
    {
        $this->stripeClient->execute(function () use ($invoiceId) {
            return $this->stripeClient->getClient()->invoices->sendInvoice($invoiceId);
        });
    }

    /**
     * Pay an invoice manually.
     *
     * @throws ApiErrorException
     */
    public function payInvoice(string $invoiceId): StripeInvoice
    {
        return $this->stripeClient->execute(function () use ($invoiceId) {
            return $this->stripeClient->getClient()->invoices->pay($invoiceId);
        });
    }
}
