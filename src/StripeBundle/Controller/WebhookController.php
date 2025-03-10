<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Tomedio\StripeBundle\Service\WebhookHandler;

class WebhookController
{
    private WebhookHandler $webhookHandler;

    public function __construct(WebhookHandler $webhookHandler)
    {
        $this->webhookHandler = $webhookHandler;
    }

    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function handleWebhook(Request $request): Response
    {
        return $this->webhookHandler->handleWebhook($request);
    }
}
