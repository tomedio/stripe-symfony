<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Service;

use Stripe\StripeClient as BaseStripeClient;
use Stripe\Exception\ApiErrorException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class StripeClient
{
    private BaseStripeClient $client;
    private LoggerInterface $logger;

    public function __construct(
        string $apiKey,
        ?LoggerInterface $logger = null
    ) {
        $this->client = new BaseStripeClient($apiKey);
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Get the underlying Stripe client.
     */
    public function getClient(): BaseStripeClient
    {
        return $this->client;
    }

    /**
     * Execute a Stripe API call with error handling.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     * @throws ApiErrorException
     */
    public function execute(callable $callback)
    {
        try {
            return $callback();
        } catch (ApiErrorException $e) {
            $this->logger->error('Stripe API error: ' . $e->getMessage(), [
                'http_status' => $e->getHttpStatus(),
                'type' => $e->getError()->type ?? null,
                'code' => $e->getError()->code ?? null,
            ]);
            throw $e;
        }
    }
}
