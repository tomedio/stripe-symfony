---
layout: default
title: API Platform Integration
parent: Integration
nav_order: 3
---

# API Platform Integration
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

1. TOC
{:toc}

---

## Overview

The Stripe Bundle is designed to work seamlessly with API Platform, allowing you to expose your Stripe-related entities as API resources and leverage API Platform's features for building RESTful APIs.

This page covers how to integrate the Stripe Bundle with API Platform in your Symfony application.

## Exposing Entities as API Resources

### Subscription Plans

To expose your subscription plans as an API resource:

```php
<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Tomedio\StripeBundle\Contract\SubscriptionPlanInterface;
use Tomedio\StripeBundle\Enum\BillingInterval;
use Tomedio\StripeBundle\Enum\Currency;

#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['plan:read', 'plan:item:read']]
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['plan:read']]
        ),
    ],
    normalizationContext: ['groups' => ['plan:read']],
    denormalizationContext: ['groups' => ['plan:write']]
)]
#[ORM\Entity]
class SubscriptionPlan implements SubscriptionPlanInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['plan:read'])]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['plan:read'])]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['plan:read'])]
    private ?string $description = null;

    #[ORM\Column(type: 'integer')]
    #[Groups(['plan:read'])]
    private int $amount;

    #[ORM\Column(type: 'string', length: 3, enumType: Currency::class)]
    #[Groups(['plan:read'])]
    private Currency $currency;

    #[ORM\Column(type: 'string', length: 10, enumType: BillingInterval::class)]
    #[Groups(['plan:read'])]
    private BillingInterval $interval;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['plan:read'])]
    private ?int $trialPeriodDays = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stripeProductId = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stripePriceId = null;

    // Implement getters and setters
    // ...
}
```

### Subscriptions

To expose your subscriptions as an API resource:

```php
<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Controller\CreateSubscriptionController;
use App\Controller\CancelSubscriptionController;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Tomedio\StripeBundle\Contract\SubscriptionInterface;
use Tomedio\StripeBundle\Contract\SubscriptionPlanInterface;
use Tomedio\StripeBundle\Contract\StripeUserInterface;
use Tomedio\StripeBundle\Enum\SubscriptionStatus;

#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['subscription:read', 'subscription:item:read']]
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['subscription:read']]
        ),
        new Post(
            uriTemplate: '/subscriptions/checkout',
            controller: CreateSubscriptionController::class,
            name: 'subscription_checkout',
            openapiContext: [
                'summary' => 'Create a subscription checkout session',
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'planId' => [
                                        'type' => 'string',
                                        'example' => 'basic'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ),
        new Post(
            uriTemplate: '/subscriptions/{id}/cancel',
            controller: CancelSubscriptionController::class,
            name: 'subscription_cancel',
            openapiContext: [
                'summary' => 'Cancel a subscription'
            ]
        ),
    ],
    normalizationContext: ['groups' => ['subscription:read']],
    denormalizationContext: ['groups' => ['subscription:write']]
)]
#[ORM\Entity]
class Subscription implements SubscriptionInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['subscription:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['subscription:read'])]
    private StripeUserInterface $user;

    #[ORM\ManyToOne(targetEntity: SubscriptionPlan::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['subscription:read'])]
    private SubscriptionPlanInterface $plan;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stripeSubscriptionId = null;

    #[ORM\Column(type: 'string', length: 20, enumType: SubscriptionStatus::class)]
    #[Groups(['subscription:read'])]
    private SubscriptionStatus $status;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['subscription:read'])]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['subscription:read'])]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['subscription:read'])]
    private ?\DateTimeInterface $trialEndDate = null;

    // Implement getters and setters
    // ...
}
```

### Invoices

To expose your invoices as an API resource:

```php
<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Tomedio\StripeBundle\Contract\StripeInvoiceInterface;
use Tomedio\StripeBundle\Contract\StripeUserInterface;
use Tomedio\StripeBundle\Contract\SubscriptionInterface;
use Tomedio\StripeBundle\Enum\Currency;
use Tomedio\StripeBundle\Enum\InvoiceStatus;

#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['invoice:read', 'invoice:item:read']]
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['invoice:read']]
        ),
    ],
    normalizationContext: ['groups' => ['invoice:read']],
    denormalizationContext: ['groups' => ['invoice:write']]
)]
#[ORM\Entity]
class Invoice implements StripeInvoiceInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['invoice:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['invoice:read'])]
    private StripeUserInterface $user;

    #[ORM\ManyToOne(targetEntity: Subscription::class)]
    #[Groups(['invoice:read'])]
    private ?SubscriptionInterface $subscription = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $stripeInvoiceId;

    #[ORM\Column(type: 'integer')]
    #[Groups(['invoice:read'])]
    private int $amount;

    #[ORM\Column(type: 'string', length: 3, enumType: Currency::class)]
    #[Groups(['invoice:read'])]
    private Currency $currency;

    #[ORM\Column(type: 'string', length: 20, enumType: InvoiceStatus::class)]
    #[Groups(['invoice:read'])]
    private InvoiceStatus $status;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['invoice:read'])]
    private \DateTimeInterface $invoiceDate;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['invoice:read'])]
    private ?\DateTimeInterface $dueDate = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['invoice:read'])]
    private ?\DateTimeInterface $paidDate = null;

    // Implement getters and setters
    // ...
}
```

### Credit Packages

If you're using the credits system, you can expose your credit packages as an API resource:

```php
<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Controller\CreateCreditCheckoutController;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Tomedio\StripeBundle\Enum\Currency;

#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['credit_package:read', 'credit_package:item:read']]
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['credit_package:read']]
        ),
        new Post(
            uriTemplate: '/credit_packages/checkout',
            controller: CreateCreditCheckoutController::class,
            name: 'credit_package_checkout',
            openapiContext: [
                'summary' => 'Create a credit package checkout session',
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'packageId' => [
                                        'type' => 'integer',
                                        'example' => 1
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ),
    ],
    normalizationContext: ['groups' => ['credit_package:read']],
    denormalizationContext: ['groups' => ['credit_package:write']]
)]
#[ORM\Entity]
class CreditPackage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['credit_package:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['credit_package:read'])]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['credit_package:read'])]
    private ?string $description = null;

    #[ORM\Column(type: 'integer')]
    #[Groups(['credit_package:read'])]
    private int $credits;

    #[ORM\Column(type: 'integer')]
    #[Groups(['credit_package:read'])]
    private int $price;

    #[ORM\Column(type: 'string', length: 3, enumType: Currency::class)]
    #[Groups(['credit_package:read'])]
    private Currency $currency;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stripeProductId = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stripePriceId = null;

    // Implement getters and setters
    // ...
}
```

## Custom Controllers

### Subscription Checkout Controller

Create a controller for handling subscription checkout:

```php
<?php

namespace App\Controller;

use App\Repository\SubscriptionPlanRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Core\Security;
use Tomedio\StripeBundle\Service\SubscriptionService;

#[AsController]
class CreateSubscriptionController extends AbstractController
{
    private Security $security;
    private SubscriptionService $subscriptionService;
    private SubscriptionPlanRepository $planRepository;

    public function __construct(
        Security $security,
        SubscriptionService $subscriptionService,
        SubscriptionPlanRepository $planRepository
    ) {
        $this->security = $security;
        $this->subscriptionService = $subscriptionService;
        $this->planRepository = $planRepository;
    }

    public function __invoke(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $planId = $data['planId'] ?? null;
        
        if (!$planId) {
            return $this->json(['error' => 'Plan ID is required'], Response::HTTP_BAD_REQUEST);
        }
        
        $user = $this->security->getUser();
        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }
        
        $plan = $this->planRepository->find($planId);
        if (!$plan) {
            return $this->json(['error' => 'Plan not found'], Response::HTTP_NOT_FOUND);
        }
        
        // Create a checkout session
        $checkoutUrl = $this->subscriptionService->createCheckoutSession($user, $plan);
        
        return $this->json([
            'checkoutUrl' => $checkoutUrl
        ]);
    }
}
```

### Subscription Cancel Controller

Create a controller for handling subscription cancellation:

```php
<?php

namespace App\Controller;

use App\Repository\SubscriptionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Core\Security;
use Tomedio\StripeBundle\Service\SubscriptionService;

#[AsController]
class CancelSubscriptionController extends AbstractController
{
    private Security $security;
    private SubscriptionService $subscriptionService;
    private SubscriptionRepository $subscriptionRepository;

    public function __construct(
        Security $security,
        SubscriptionService $subscriptionService,
        SubscriptionRepository $subscriptionRepository
    ) {
        $this->security = $security;
        $this->subscriptionService = $subscriptionService;
        $this->subscriptionRepository = $subscriptionRepository;
    }

    public function __invoke(Request $request, int $id): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }
        
        $subscription = $this->subscriptionRepository->find($id);
        if (!$subscription) {
            return $this->json(['error' => 'Subscription not found'], Response::HTTP_NOT_FOUND);
        }
        
        // Check if the subscription belongs to the user
        if ($subscription->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }
        
        // Cancel the subscription
        $this->subscriptionService->cancelSubscription($subscription);
        
        return $this->json([
            'message' => 'Subscription cancelled successfully'
        ]);
    }
}
```

### Credit Checkout Controller

Create a controller for handling credit package checkout:

```php
<?php

namespace App\Controller;

use App\Repository\CreditPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Core\Security;
use Tomedio\StripeBundle\Service\StripeClient;

#[AsController]
class CreateCreditCheckoutController extends AbstractController
{
    private Security $security;
    private StripeClient $stripeClient;
    private CreditPackageRepository $packageRepository;
    private string $successUrl;
    private string $cancelUrl;

    public function __construct(
        Security $security,
        StripeClient $stripeClient,
        CreditPackageRepository $packageRepository,
        string $successUrl,
        string $cancelUrl
    ) {
        $this->security = $security;
        $this->stripeClient = $stripeClient;
        $this->packageRepository = $packageRepository;
        $this->successUrl = $successUrl;
        $this->cancelUrl = $cancelUrl;
    }

    public function __invoke(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $packageId = $data['packageId'] ?? null;
        
        if (!$packageId) {
            return $this->json(['error' => 'Package ID is required'], Response::HTTP_BAD_REQUEST);
        }
        
        $user = $this->security->getUser();
        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }
        
        $package = $this->packageRepository->find($packageId);
        if (!$package) {
            return $this->json(['error' => 'Credit package not found'], Response::HTTP_NOT_FOUND);
        }
        
        // Ensure the user has a Stripe customer ID
        if (!$user->getStripeCustomerId()) {
            $customer = $this->stripeClient->createCustomer([
                'email' => $user->getEmail(),
                'name' => $user->getName(),
            ]);
            $user->setStripeCustomerId($customer->id);
            // Save the user
        }
        
        // Create a Stripe checkout session
        $session = $this->stripeClient->createCheckoutSession([
            'customer' => $user->getStripeCustomerId(),
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($package->getCurrency()->value),
                    'product_data' => [
                        'name' => $package->getName(),
                        'description' => $package->getDescription(),
                    ],
                    'unit_amount' => $package->getPrice(),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->successUrl . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->cancelUrl,
            'metadata' => [
                'package_id' => $package->getId(),
                'credits' => $package->getCredits(),
            ],
        ]);
        
        return $this->json([
            'checkoutUrl' => $session->url
        ]);
    }
}
```

## API Platform Extensions

### Credit Check Extension

Create an extension to check if a user has enough credits to access a resource:

```php
<?php

namespace App\ApiPlatform\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\PremiumResource;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\Security\Core\Security;
use Tomedio\StripeBundle\Enum\CreditTransactionType;
use Tomedio\StripeBundle\Service\CreditService;

class CreditCheckExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    private Security $security;
    private CreditService $creditService;
    
    public function __construct(
        Security $security,
        CreditService $creditService
    ) {
        $this->security = $security;
        $this->creditService = $creditService;
    }
    
    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        Operation $operation = null,
        array $context = []
    ): void {
        $this->checkCredits($resourceClass, 'collection');
    }
    
    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        Operation $operation = null,
        array $context = []
    ): void {
        $this->checkCredits($resourceClass, 'item', $identifiers['id'] ?? null);
    }
    
    private function checkCredits(string $resourceClass, string $operationType, ?string $itemId = null): void
    {
        // Only check credits for premium resources
        if ($resourceClass !== PremiumResource::class) {
            return;
        }
        
        $user = $this->security->getUser();
        if (!$user) {
            throw new BadRequestException('Authentication required');
        }
        
        // Define credit costs for different operations
        $creditCosts = [
            'collection' => 1,
            'item' => 2,
        ];
        
        $creditsNeeded = $creditCosts[$operationType] ?? 1;
        
        // Use credits
        $description = 'API access to ' . $resourceClass;
        if ($itemId) {
            $description .= ' (ID: ' . $itemId . ')';
        }
        
        $creditsUsed = $this->creditService->useCredits(
            $user,
            $creditsNeeded,
            $description,
            CreditTransactionType::USAGE,
            'api_' . $resourceClass . '_' . $operationType . ($itemId ? '_' . $itemId : '')
        );
        
        if ($creditsUsed < $creditsNeeded) {
            throw new BadRequestException(
                'Not enough credits. Needed: ' . $creditsNeeded . ', Available: ' . $user->getCreditsBalance()
            );
        }
    }
}
```

Register the extension in your services configuration:

```yaml
# config/services.yaml
services:
    # ...
    App\ApiPlatform\Extension\CreditCheckExtension:
        tags:
            - { name: 'api_platform.doctrine.orm.query_extension.collection' }
            - { name: 'api_platform.doctrine.orm.query_extension.item' }
```

### Subscription Check Extension

Create an extension to check if a user has an active subscription to access a resource:

```php
<?php

namespace App\ApiPlatform\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\SubscriptionProtectedResource;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\Security\Core\Security;
use Tomedio\StripeBundle\Enum\SubscriptionStatus;

class SubscriptionCheckExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    private Security $security;
    
    public function __construct(Security $security)
    {
        $this->security = $security;
    }
    
    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        Operation $operation = null,
        array $context = []
    ): void {
        $this->checkSubscription($resourceClass);
    }
    
    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        Operation $operation = null,
        array $context = []
    ): void {
        $this->checkSubscription($resourceClass);
    }
    
    private function checkSubscription(string $resourceClass): void
    {
        // Only check subscription for protected resources
        if ($resourceClass !== SubscriptionProtectedResource::class) {
            return;
        }
        
        $user = $this->security->getUser();
        if (!$user) {
            throw new BadRequestException('Authentication required');
        }
        
        // Check if the user has an active subscription
        $subscription = $user->getActiveSubscription();
        if (!$subscription) {
            throw new BadRequestException('Active subscription required');
        }
        
        // Check if the subscription is active or in trial
        $status = $subscription->getStatus();
        if ($status !== SubscriptionStatus::ACTIVE && $status !== SubscriptionStatus::TRIALING) {
            throw new BadRequestException('Active subscription required');
        }
    }
}
```

Register the extension in your services configuration:

```yaml
# config/services.yaml
services:
    # ...
    App\ApiPlatform\Extension\SubscriptionCheckExtension:
        tags:
            - { name: 'api_platform.doctrine.orm.query_extension.collection' }
            - { name: 'api_platform.doctrine.orm.query_extension.item' }
```

## API Platform Event Subscribers

### User Creation Subscriber

Create a subscriber to automatically create a Stripe customer when a user is created:

```php
<?php

namespace App\EventSubscriber;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Tomedio\StripeBundle\Service\CustomerService;

class ApiUserCreationSubscriber implements EventSubscriberInterface
{
    private CustomerService $customerService;
    
    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }
    
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['onUserCreated', EventPriorities::POST_WRITE],
        ];
    }
    
    public function onUserCreated(ViewEvent $event): void
    {
        $user = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();
        
        // Only process POST requests for User entities
        if (!$user instanceof User || $method !== 'POST') {
            return;
        }
        
        // Create a Stripe customer for the new user
        if (!$user->getStripeCustomerId()) {
            $this->customerService->getOrCreateCustomer($user);
        }
    }
}
```

## OpenAPI Documentation

API Platform automatically generates OpenAPI documentation for your API. You can customize the documentation for your Stripe-related endpoints:

```php
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/subscriptions/checkout',
            controller: CreateSubscriptionController::class,
            name: 'subscription_checkout',
            openapiContext: [
                'summary' => 'Create a subscription checkout session',
                'description' => 'Creates a Stripe Checkout session for subscribing to a plan',
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'planId' => [
                                        'type' => 'string',
                                        'example' => 'basic',
                                        'description' => 'The ID of the subscription plan'
                                    ]
                                ],
                                'required' => ['planId']
                            ]
                        ]
                    ]
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Checkout session created',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'checkoutUrl' => [
                                            'type' => 'string',
                                            'format' => 'uri',
                                            'description' => 'URL to redirect the user to for checkout'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    '400' => [
                        'description' => 'Invalid input'
                    ],
                    '401' => [
                        'description' => 'Unauthorized'
                    ],
                    '404' => [
                        'description' => 'Plan not found'
                    ]
                ]
            ]
        ),
    ],
)]
```

## Frontend Integration

Here's an example of how to integrate the API with a React frontend:

```jsx
import React, { useState, useEffect } from 'react';
import axios from 'axios';

const SubscriptionPlans = () => {
  const [plans, setPlans] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchPlans = async () => {
      try {
        const response = await axios.get('/api/subscription_plans');
        setPlans(response.data['hydra:member']);
        setLoading(false);
      } catch (err) {
        setError('Failed to load subscription plans');
        setLoading(false);
      }
    };

    fetchPlans();
  }, []);

  const handleSubscribe = async (planId) => {
    try {
      const response = await axios.post('/api/subscriptions/checkout', {
