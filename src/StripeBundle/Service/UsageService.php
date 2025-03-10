<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Service;

use Stripe\Exception\ApiErrorException;
use Stripe\InvoiceItem;
use Stripe\UsageRecord;
use Tomedio\StripeBundle\Contract\StripeUserInterface;
use Tomedio\StripeBundle\Enum\Currency;

class UsageService
{
    private StripeClient $stripeClient;

    public function __construct(StripeClient $stripeClient)
    {
        $this->stripeClient = $stripeClient;
    }

    /**
     * Add a usage record for a metered subscription.
     * This is used for pay-as-you-go billing where the customer is charged based on usage.
     *
     * @param string $subscriptionItemId The ID of the subscription item to add usage to
     * @param int $quantity The quantity of usage to add
     * @param \DateTimeInterface|null $timestamp The timestamp when the usage occurred (defaults to now)
     * @param string|null $action The action that generated the usage (for metadata)
     * @return UsageRecord The created usage record
     * @throws ApiErrorException
     */
    public function addUsageRecord(
        string $subscriptionItemId,
        int $quantity,
        ?\DateTimeInterface $timestamp = null,
        ?string $action = null
    ): UsageRecord {
        $timestamp = $timestamp ?? new \DateTimeImmutable();
        
        $usageData = [
            'quantity' => $quantity,
            'timestamp' => $timestamp->getTimestamp(),
        ];
        
        if ($action !== null) {
            $usageData['action'] = $action;
        }

        return $this->stripeClient->execute(function () use ($subscriptionItemId, $usageData) {
            return $this->stripeClient->getClient()->subscriptionItems->createUsageRecord(
                $subscriptionItemId,
                $usageData
            );
        });
    }

    /**
     * Add a line item to the customer's next invoice.
     * This is used for adding one-time charges to the monthly invoice.
     *
     * @param StripeUserInterface $user The user to add the charge to
     * @param int $amount The amount to charge in the smallest currency unit (e.g., cents for USD)
     * @param Currency $currency The currency to use
     * @param string $description A description of what the charge is for
     * @param array $metadata Additional metadata to store with the charge
     * @return InvoiceItem The created invoice item
     * @throws ApiErrorException
     */
    public function addInvoiceItem(
        StripeUserInterface $user,
        int $amount,
        Currency $currency,
        string $description,
        array $metadata = []
    ): InvoiceItem {
        // Ensure the user has a Stripe customer ID
        if (!$user->getStripeCustomerId()) {
            throw new \InvalidArgumentException('User must have a Stripe customer ID');
        }

        $invoiceItemData = [
            'customer' => $user->getStripeCustomerId(),
            'amount' => $amount,
            'currency' => $currency->value,
            'description' => $description,
            'metadata' => $metadata,
        ];

        return $this->stripeClient->execute(function () use ($invoiceItemData) {
            return $this->stripeClient->getClient()->invoiceItems->create($invoiceItemData);
        });
    }


    /**
     * Get usage records for a subscription item.
     *
     * @param string $subscriptionItemId The ID of the subscription item to get usage for
     * @return array The usage records
     * @throws ApiErrorException
     */
    public function getUsageRecords(string $subscriptionItemId): array
    {
        $usageRecords = $this->stripeClient->execute(function () use ($subscriptionItemId) {
            return $this->stripeClient->getClient()->subscriptionItems->allUsageRecordSummaries(
                $subscriptionItemId,
                ['limit' => 100]
            );
        });

        return $usageRecords->data;
    }
}
