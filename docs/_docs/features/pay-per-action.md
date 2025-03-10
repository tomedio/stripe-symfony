---
layout: default
title: Pay-Per-Action
parent: Features
nav_order: 3
---

# Pay-Per-Action with Credits
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

1. TOC
{:toc}

---

## Overview

The Stripe Bundle provides a credits system that allows you to implement pay-per-action functionality in your SaaS application. This is useful for applications where users pay for specific actions or API calls rather than (or in addition to) a subscription.

## Setting Up the Credits System

### 1. Implement the Required Interfaces

First, make sure your User entity implements the `StripeUserInterface` and has a credits balance field:

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;
use Tomedio\StripeBundle\Contract\StripeUserInterface;

#[ORM\Entity]
class User implements SymfonyUserInterface, StripeUserInterface
{
    // ... other properties
    
    #[ORM\Column(type: 'integer')]
    private int $creditsBalance = 0;
    
    // ... other methods
    
    public function getCreditsBalance(): int
    {
        return $this->creditsBalance;
    }
    
    public function setCreditsBalance(int $creditsBalance): self
    {
        $this->creditsBalance = $creditsBalance;
        return $this;
    }
}
```

Then, implement the `CreditTransactionInterface` for tracking credit transactions:

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Tomedio\StripeBundle\Contract\CreditTransactionInterface;
use Tomedio\StripeBundle\Contract\StripeUserInterface;
use Tomedio\StripeBundle\Enum\CreditTransactionType;
use Tomedio\StripeBundle\Enum\Currency;

#[ORM\Entity]
class CreditTransaction implements CreditTransactionInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private StripeUserInterface $user;

    #[ORM\Column(type: 'integer')]
    private int $amount;

    #[ORM\Column(type: 'integer')]
    private int $balanceAfter;

    #[ORM\Column(type: 'string', length: 255)]
    private string $description;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $transactionDate;

    #[ORM\Column(type: 'string', length: 20, enumType: CreditTransactionType::class)]
    private CreditTransactionType $type;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $referenceId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $monetaryAmount = null;

    #[ORM\Column(type: 'string', length: 3, nullable: true, enumType: Currency::class)]
    private ?Currency $currency = null;

    // Implement getters and setters for all properties
    // ...
}
```

### 2. Create a Credit Package Entity

Create an entity to represent credit packages that users can purchase:

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Tomedio\StripeBundle\Enum\Currency;

#[ORM\Entity]
class CreditPackage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'integer')]
    private int $credits;

    #[ORM\Column(type: 'integer')]
    private int $price;

    #[ORM\Column(type: 'string', length: 3, enumType: Currency::class)]
    private Currency $currency;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stripeProductId = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stripePriceId = null;

    // Getters and setters
    // ...
}
```

## Using the Credit Service

The bundle provides a `CreditService` that you can use to manage user credits:

### 1. Adding Credits

When a user purchases credits, you can add them to their balance:

```php
<?php

namespace App\Controller;

use App\Entity\CreditPackage;
use App\Repository\CreditPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Tomedio\StripeBundle\Enum\CreditTransactionType;
use Tomedio\StripeBundle\Service\CreditService;

class CreditController extends AbstractController
{
    private Security $security;
    private CreditService $creditService;
    private CreditPackageRepository $packageRepository;

    public function __construct(
        Security $security,
        CreditService $creditService,
        CreditPackageRepository $packageRepository
    ) {
        $this->security = $security;
        $this->creditService = $creditService;
        $this->packageRepository = $packageRepository;
    }

    #[Route('/credits/add/{packageId}', name: 'credits_add')]
    public function addCredits(int $packageId): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $package = $this->packageRepository->find($packageId);
        if (!$package) {
            throw $this->createNotFoundException('Credit package not found');
        }

        // Add credits to the user's balance
        $this->creditService->addCredits(
            $user,
            $package->getCredits(),
            'Purchase of ' . $package->getName() . ' credit package',
            CreditTransactionType::PURCHASE,
            'package_' . $package->getId(),
            $package->getPrice(),
            $package->getCurrency()
        );

        $this->addFlash('success', $package->getCredits() . ' credits have been added to your account.');
        return $this->redirectToRoute('credits_balance');
    }
}
```

### 2. Using Credits

When a user performs an action that requires credits, you can deduct them from their balance:

```php
<?php

namespace App\Service;

use Symfony\Component\Security\Core\Security;
use Tomedio\StripeBundle\Enum\CreditTransactionType;
use Tomedio\StripeBundle\Service\CreditService;

class ApiService
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

    public function performApiCall(string $endpoint, array $params): array
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw new \Exception('User not authenticated');
        }

        // Check if the user has enough credits
        $requiredCredits = $this->getRequiredCredits($endpoint);
        if ($user->getCreditsBalance() < $requiredCredits) {
            throw new \Exception('Not enough credits. Required: ' . $requiredCredits . ', Available: ' . $user->getCreditsBalance());
        }

        // Use credits
        $creditsUsed = $this->creditService->useCredits(
            $user,
            $requiredCredits,
            'API call to ' . $endpoint,
            CreditTransactionType::USAGE,
            'api_' . $endpoint
        );

        if ($creditsUsed < $requiredCredits) {
            throw new \Exception('Failed to use credits');
        }

        // Perform the actual API call
        // ...

        return $result;
    }

    private function getRequiredCredits(string $endpoint): int
    {
        // Define credit costs for different endpoints
        $creditCosts = [
            'search' => 1,
            'analyze' => 5,
            'generate' => 10,
        ];

        return $creditCosts[$endpoint] ?? 1;
    }
}
```

### 3. Checking Credit Balance

You can check a user's credit balance:

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Tomedio\StripeBundle\Service\CreditService;

class CreditController extends AbstractController
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

    #[Route('/credits/balance', name: 'credits_balance')]
    public function balance(): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Get the user's credit transactions
        $transactions = $this->creditService->getTransactions($user);

        return $this->render('credit/balance.html.twig', [
            'balance' => $user->getCreditsBalance(),
            'transactions' => $transactions,
        ]);
    }
}
```

## Creating a Credit Purchase Checkout

You can use the CreditService to create a checkout session for purchasing credits:

```php
<?php

namespace App\Controller;

use App\Repository\CreditPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Tomedio\StripeBundle\Service\CreditService;
use Tomedio\StripeBundle\Service\CustomerService;

class CreditController extends AbstractController
{
    private Security $security;
    private CreditService $creditService;
    private CustomerService $customerService;
    private CreditPackageRepository $packageRepository;

    public function __construct(
        Security $security,
        CreditService $creditService,
        CustomerService $customerService,
        CreditPackageRepository $packageRepository
    ) {
        $this->security = $security;
        $this->creditService = $creditService;
        $this->customerService = $customerService;
        $this->packageRepository = $packageRepository;
    }

    #[Route('/credits/checkout/{packageId}', name: 'credits_checkout')]
    public function checkout(int $packageId): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $package = $this->packageRepository->find($packageId);
        if (!$package) {
            throw $this->createNotFoundException('Credit package not found');
        }

        // Ensure the user has a Stripe customer ID
        if (!$user->getStripeCustomerId()) {
            $this->customerService->getOrCreateCustomer($user);
        }

        // Create a checkout session for purchasing credits
        $checkoutUrl = $this->creditService->createCreditsCheckoutSession(
            $user,
            $package->getPrice(),
            $package->getCurrency(),
            $package->getCredits(),
            $package->getName() . ' - ' . $package->getCredits() . ' credits'
        );

        return $this->redirect($checkoutUrl);
    }
}
```

## Handling Credit Purchase Webhooks

When a user completes a credit purchase, you'll receive a webhook event that you should handle:

```php
<?php

namespace App\EventSubscriber;

use App\Repository\CreditPackageRepository;
use App\Repository\UserRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tomedio\StripeBundle\Enum\CreditTransactionType;
use Tomedio\StripeBundle\Event\StripeWebhookEvent;
use Tomedio\StripeBundle\Service\CreditService;

class StripeWebhookSubscriber implements EventSubscriberInterface
{
    private UserRepository $userRepository;
    private CreditPackageRepository $packageRepository;
    private CreditService $creditService;
    
    public function __construct(
        UserRepository $userRepository,
        CreditPackageRepository $packageRepository,
        CreditService $creditService
    ) {
        $this->userRepository = $userRepository;
        $this->packageRepository = $packageRepository;
        $this->creditService = $creditService;
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
        
        // Check if this is a credit purchase
        if ($session->mode !== 'payment' || !isset($session->metadata->package_id)) {
            return;
        }
        
        // Get the customer ID from the session
        $customerId = $session->customer;
        
        // Find the user by Stripe customer ID
        $user = $this->userRepository->findOneByStripeCustomerId($customerId);
        if (!$user) {
            return;
        }
        
        // Get the package ID and credits from the session metadata
        $packageId = $session->metadata->package_id;
        $credits = (int) $session->metadata->credits;
        
        // Find the package
        $package = $this->packageRepository->find($packageId);
        if (!$package) {
            return;
        }
        
        // Add credits to the user's balance
        $this->creditService->addCredits(
            $user,
            $credits,
            'Purchase of ' . $package->getName() . ' credit package',
            CreditTransactionType::PURCHASE,
            'session_' . $session->id,
            $package->getPrice(),
            $package->getCurrency()
        );
    }
}
```

## API Platform Integration

If you're using API Platform, you can create a custom extension to check if a user has enough credits to access a resource:

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

## Frontend Integration

Here's an example of how to integrate the credits system with a React frontend:

```jsx
import React, { useState, useEffect } from 'react';
import axios from 'axios';

const CreditPackages = () => {
  const [packages, setPackages] = useState([]);
  const [balance, setBalance] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const [packagesResponse, balanceResponse] = await Promise.all([
          axios.get('/api/credit_packages'),
          axios.get('/api/credits/balance')
        ]);
        
        setPackages(packagesResponse.data['hydra:member']);
        setBalance(balanceResponse.data.balance);
        setLoading(false);
      } catch (err) {
        setError('Failed to load data');
        setLoading(false);
      }
    };

    fetchData();
  }, []);

  const handlePurchase = async (packageId) => {
    try {
      const response = await axios.post('/api/credits/checkout', {
        packageId: packageId
      });
      
      // Redirect to Stripe Checkout
      window.location.href = response.data.checkoutUrl;
    } catch (err) {
      setError('Failed to create checkout session');
    }
  };

  if (loading) return <div>Loading...</div>;
  if (error) return <div>{error}</div>;

  return (
    <div className="credit-packages">
      <h2>Your Credit Balance: {balance}</h2>
      <h3>Purchase Credits</h3>
      <div className="packages-container">
        {packages.map(pkg => (
          <div key={pkg.id} className="package-card">
            <h4>{pkg.name}</h4>
            <p>{pkg.description}</p>
            <div className="credits">
              {pkg.credits} credits
            </div>
            <div className="price">
              ${(pkg.price / 100).toFixed(2)}
            </div>
            <button 
              onClick={() => handlePurchase(pkg.id)}
              className="purchase-button"
            >
              Purchase
            </button>
          </div>
        ))}
      </div>
    </div>
  );
};

export default CreditPackages;
```

## Best Practices

1. **Transaction Logging**: Always log credit transactions for auditing and debugging purposes
2. **Error Handling**: Implement proper error handling for credit operations
3. **Idempotency**: Ensure your webhook handlers are idempotent to prevent duplicate credit additions
4. **Security**: Validate that the user has permission to perform credit operations
5. **Monitoring**: Monitor credit usage to detect unusual patterns or potential abuse
6. **Notifications**: Notify users when their credit balance is low
7. **Refunds**: Implement a system for handling refunds if necessary
