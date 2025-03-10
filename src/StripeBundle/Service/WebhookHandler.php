<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Service;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tomedio\StripeBundle\Event\StripeWebhookEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class WebhookHandler
{
    private string $webhookSecret;
    private EventDispatcherInterface $eventDispatcher;
    private LoggerInterface $logger;

    public function __construct(
        string $webhookSecret,
        EventDispatcherInterface $eventDispatcher,
        ?LoggerInterface $logger = null
    ) {
        $this->webhookSecret = $webhookSecret;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Handle a Stripe webhook request.
     */
    public function handleWebhook(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');

        if (empty($payload) || empty($sigHeader)) {
            $this->logger->warning('Missing payload or signature');
            return new Response('Missing payload or signature', Response::HTTP_BAD_REQUEST);
        }

        try {
            // Verify the webhook signature
            $stripeEvent = Webhook::constructEvent($payload, $sigHeader, $this->webhookSecret);

            // Create a Symfony event from the Stripe event
            $event = new StripeWebhookEvent($stripeEvent);

            // Dispatch a generic stripe event
            $this->eventDispatcher->dispatch($event, 'stripe.webhook');

            // Dispatch a specific event based on the Stripe event type
            $eventName = 'stripe.' . str_replace('.', '_', $stripeEvent->type);
            $this->eventDispatcher->dispatch($event, $eventName);

            $this->logger->info('Dispatched Stripe webhook event: ' . $eventName);

            return new Response('Webhook processed successfully', Response::HTTP_OK);
        } catch (SignatureVerificationException $e) {
            $this->logger->error('Invalid signature: ' . $e->getMessage());
            return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $this->logger->error('Error processing webhook: ' . $e->getMessage());
            return new Response('Error processing webhook', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
