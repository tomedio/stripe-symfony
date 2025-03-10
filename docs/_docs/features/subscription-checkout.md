---
layout: default
title: Subscription Checkout
parent: Features
nav_order: 2
---

# Subscription Checkout
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

1. TOC
{:toc}

---

## Overview

The Stripe Bundle provides a streamlined way to create subscription checkout sessions for your users. This allows them to select a subscription plan and complete the payment process securely through Stripe Checkout.

## Creating a Checkout Session

To create a checkout session for a subscription, you'll use the `SubscriptionService`:

```php
<?php

namespace App\Controller;

use App\Repository\SubscriptionPlanRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Tomedio\StripeBundle\Service\SubscriptionService;

class SubscriptionController extends AbstractController
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

    #[Route('/subscription/checkout/{planId}', name: 'subscription_checkout')]
    public function checkout(string $planId): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $plan = $this->planRepository->find($planId);
        if (!$plan) {
            throw $this->createNotFoundException('Subscription plan not found');
        }

        // Create a checkout session
        $checkoutUrl = $this->subscriptionService->createCheckoutSession($user, $plan);

        // Redirect to Stripe Checkout
        return $this->redirect($checkoutUrl);
    }
}
```

## API Platform Integration

If you're using API Platform, you can create a custom operation for subscription checkout:

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

Then, in your API resource:

```php
<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Controller\CreateSubscriptionController;
use Doctrine\ORM\Mapping as ORM;
use Tomedio\StripeBundle\Contract\SubscriptionInterface;

#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
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
    ],
    normalizationContext: ['groups' => ['subscription:read']],
    denormalizationContext: ['groups' => ['subscription:write']]
)]
#[ORM\Entity]
class Subscription implements SubscriptionInterface
{
    // ... properties and methods
}
```

## Frontend Integration

Here's an example of how to integrate the subscription checkout with a React frontend:

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
        planId: planId
      });
      
      // Redirect to Stripe Checkout
      window.location.href = response.data.checkoutUrl;
    } catch (err) {
      setError('Failed to create checkout session');
    }
  };

  if (loading) return <div>Loading plans...</div>;
  if (error) return <div>{error}</div>;

  return (
    <div className="subscription-plans">
      <h2>Choose a Subscription Plan</h2>
      <div className="plans-container">
        {plans.map(plan => (
          <div key={plan.id} className="plan-card">
            <h3>{plan.name}</h3>
            <p>{plan.description}</p>
            <div className="price">
              ${(plan.amount / 100).toFixed(2)} / {plan.interval}
            </div>
            {plan.trialPeriodDays && (
              <div className="trial">
                {plan.trialPeriodDays} days free trial
              </div>
            )}
            <button 
              onClick={() => handleSubscribe(plan.id)}
              className="subscribe-button"
            >
              Subscribe
            </button>
          </div>
        ))}
      </div>
    </div>
  );
};

export default SubscriptionPlans;
```

## Handling Checkout Completion

After the user completes the checkout process, Stripe will redirect them to the success URL you configured. You'll also receive a webhook event (`checkout.session.completed`) that you should handle to update the subscription status in your database.

Here's an example of handling the webhook event:

```php
<?php

namespace App\EventSubscriber;

use App\Entity\Subscription;
use App\Repository\SubscriptionPlanRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tomedio\StripeBundle\Enum\SubscriptionStatus;
use Tomedio\StripeBundle\Event\StripeWebhookEvent;

class StripeWebhookSubscriber implements EventSubscriberInterface
{
    private UserRepository $userRepository;
    private SubscriptionPlanRepository $planRepository;
    private EntityManagerInterface $entityManager;
    
    public function __construct(
        UserRepository $userRepository,
        SubscriptionPlanRepository $planRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->userRepository = $userRepository;
        $this->planRepository = $planRepository;
        $this->entityManager = $entityManager;
    }
    
    public static function getSubscribedEvents(): array
    {
        return [
            'stripe.checkout_session_completed' => 'onCheckoutSessionCompleted',
        ];
    }
    
    public function onCheckoutSessionCompleted(StripeWebhookEvent $event): void
    {
        $stripeEvent = $event->getStripeEvent();
        $session = $stripeEvent->data->object;
        
        // Get the customer ID from the session
        $customerId = $session->customer;
        
        // Find the user by Stripe customer ID
        $user = $this->userRepository->findOneByStripeCustomerId($customerId);
        if (!$user) {
            return;
        }
        
        // Get the subscription ID from the session
        $subscriptionId = $session->subscription;
        if (!$subscriptionId) {
            return;
        }
        
        // Get the plan ID from the session metadata
        $planId = $session->metadata->plan_id ?? null;
        if (!$planId) {
            return;
        }
        
        // Find the plan
        $plan = $this->planRepository->find($planId);
        if (!$plan) {
            return;
        }
        
        // Create a new subscription or update an existing one
        $subscription = new Subscription();
        $subscription->setUser($user);
        $subscription->setPlan($plan);
        $subscription->setStripeSubscriptionId($subscriptionId);
        $subscription->setStatus(SubscriptionStatus::ACTIVE);
        $subscription->setStartDate(new \DateTime());
        
        // If the plan has a trial period, set the trial end date
        if ($plan->getTrialPeriodDays()) {
            $trialEndDate = new \DateTime();
            $trialEndDate->modify('+' . $plan->getTrialPeriodDays() . ' days');
            $subscription->setTrialEndDate($trialEndDate);
            $subscription->setStatus(SubscriptionStatus::TRIALING);
        }
        
        // Save the subscription
        $this->entityManager->persist($subscription);
        
        // Set the subscription as the user's active subscription
        $user->setActiveSubscription($subscription);
        $this->entityManager->persist($user);
        
        $this->entityManager->flush();
    }
}
```

## Success and Cancel Pages

You'll need to create success and cancel pages for users to be redirected to after the checkout process:

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SubscriptionController extends AbstractController
{
    // ... other methods
    
    #[Route('/subscription/success', name: 'subscription_success')]
    public function success(): Response
    {
        return $this->render('subscription/success.html.twig');
    }
    
    #[Route('/subscription/cancel', name: 'subscription_cancel')]
    public function cancel(): Response
    {
        return $this->render('subscription/cancel.html.twig');
    }
}
```

## Testing the Checkout Flow

To test the checkout flow, you can use Stripe's test cards:

- For successful payments: `4242 4242 4242 4242`
- For failed payments: `4000 0000 0000 0002`

Make sure to use a future expiration date, any 3-digit CVC, and any 5-digit ZIP code.

## Best Practices

1. **Metadata**: Always include relevant metadata in the checkout session (like the plan ID) to help with webhook processing
2. **Error Handling**: Implement proper error handling for API requests and webhook processing
3. **Idempotency**: Ensure your webhook handlers are idempotent to prevent duplicate subscriptions
4. **Security**: Validate that the user has permission to subscribe to the plan
5. **Logging**: Log checkout events for debugging and auditing purposes
