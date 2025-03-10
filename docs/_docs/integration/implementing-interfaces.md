---
layout: default
title: Implementing Interfaces
parent: Integration
nav_order: 1
---

# Implementing Interfaces
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

1. TOC
{:toc}

---

## Overview

The Stripe Bundle uses interfaces to define the contract between your application entities and the bundle's services. This approach allows you to integrate the bundle with your existing entity structure without forcing a specific implementation.

This page covers the key interfaces you need to implement in your application.

## Required Interfaces

### StripeUserInterface

This interface should be implemented by your User entity or any entity that represents a customer in your application.

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;
use Tomedio\StripeBundle\Contract\AddressInterface;
use Tomedio\StripeBundle\Contract\StripeUserInterface;
use Tomedio\StripeBundle\Contract\SubscriptionInterface;
use Tomedio\StripeBundle\Enum\Currency;

#[ORM\Entity]
class User implements SymfonyUserInterface, StripeUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stripeCustomerId = null;

    #[ORM\ManyToOne(targetEntity: Address::class, cascade: ['persist'])]
    private ?AddressInterface $billingAddress = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $stripeBalance = null;

    #[ORM\Column(type: 'string', length: 3, nullable: true, enumType: Currency::class)]
    private ?Currency $stripeBalanceCurrency = null;

    #[ORM\OneToOne(targetEntity: Subscription::class)]
    private ?SubscriptionInterface $activeSubscription = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $stripeCreatedAt = null;

    #[ORM\Column(type: 'integer')]
    private int $creditsBalance = 0;

    // Implement getters and setters for all properties

    public function getStripeCustomerId(): ?string
    {
        return $this->stripeCustomerId;
    }

    public function setStripeCustomerId(?string $stripeCustomerId): self
    {
        $this->stripeCustomerId = $stripeCustomerId;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getBillingAddress(): ?AddressInterface
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?AddressInterface $billingAddress): self
    {
        $this->billingAddress = $billingAddress;
        return $this;
    }

    public function getActiveSubscription(): ?SubscriptionInterface
    {
        return $this->activeSubscription;
    }

    public function setActiveSubscription(?SubscriptionInterface $subscription): self
    {
        $this->activeSubscription = $subscription;
        return $this;
    }

    public function getStripeBalance(): ?int
    {
        return $this->stripeBalance;
    }

    public function setStripeBalance(?int $stripeBalance): self
    {
        $this->stripeBalance = $stripeBalance;
        return $this;
    }

    public function getStripeBalanceCurrency(): ?Currency
    {
        return $this->stripeBalanceCurrency;
    }

    public function setStripeBalanceCurrency(?Currency $stripeBalanceCurrency): self
    {
        $this->stripeBalanceCurrency = $stripeBalanceCurrency;
        return $this;
    }

    public function getStripeCreatedAt(): ?\DateTimeInterface
    {
        return $this->stripeCreatedAt;
    }

    public function setStripeCreatedAt(?\DateTimeInterface $stripeCreatedAt): self
    {
        $this->stripeCreatedAt = $stripeCreatedAt;
        return $this;
    }

    public function getCreditsBalance(): int
    {
        return $this->creditsBalance;
    }

    public function setCreditsBalance(int $creditsBalance): self
    {
        $this->creditsBalance = $creditsBalance;
        return $this;
    }

    // Implement other methods required by SymfonyUserInterface
}
```

### AddressInterface

This interface represents a billing address for a customer.

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Tomedio\StripeBundle\Contract\AddressInterface;

#[ORM\Entity]
class Address implements AddressInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $line1;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $line2 = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $city;

    #[ORM\Column(type: 'string', length: 255)]
    private string $state;

    #[ORM\Column(type: 'string', length: 255)]
    private string $postalCode;

    #[ORM\Column(type: 'string', length: 2)]
    private string $country;

    // Implement getters and setters for all properties

    public function getLine1(): string
    {
        return $this->line1;
    }

    public function setLine1(string $line1): self
    {
        $this->line1 = $line1;
        return $this;
    }

    public function getLine2(): ?string
    {
        return $this->line2;
    }

    public function setLine2(?string $line2): self
    {
        $this->line2 = $line2;
        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setState(string $state): self
    {
        $this->state = $state;
        return $this;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): self
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;
        return $this;
    }
}
```

### SubscriptionPlanInterface

This interface represents a subscription plan in your application.

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Tomedio\StripeBundle\Contract\SubscriptionPlanInterface;
use Tomedio\StripeBundle\Enum\BillingInterval;
use Tomedio\StripeBundle\Enum\Currency;

#[ORM\Entity]
class SubscriptionPlan implements SubscriptionPlanInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 255)]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'integer')]
    private int $amount;

    #[ORM\Column(type: 'string', length: 3, enumType: Currency::class)]
    private Currency $currency;

    #[ORM\Column(type: 'string', length: 10, enumType: BillingInterval::class)]
    private BillingInterval $interval;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $trialPeriodDays = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stripeProductId = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stripePriceId = null;

    // Implement getters and setters for all properties

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function setCurrency(Currency $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function getInterval(): BillingInterval
    {
        return $this->interval;
    }

    public function setInterval(BillingInterval $interval): self
    {
        $this->interval = $interval;
        return $this;
    }

    public function getTrialPeriodDays(): ?int
    {
        return $this->trialPeriodDays;
    }

    public function setTrialPeriodDays(?int $trialPeriodDays): self
    {
        $this->trialPeriodDays = $trialPeriodDays;
        return $this;
    }

    public function getStripeProductId(): ?string
    {
        return $this->stripeProductId;
    }

    public function setStripeProductId(?string $stripeProductId): self
    {
        $this->stripeProductId = $stripeProductId;
        return $this;
    }

    public function getStripePriceId(): ?string
    {
        return $this->stripePriceId;
    }

    public function setStripePriceId(?string $stripePriceId): self
    {
        $this->stripePriceId = $stripePriceId;
        return $this;
    }
}
```

### SubscriptionInterface

This interface represents a subscription in your application.

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Tomedio\StripeBundle\Contract\SubscriptionInterface;
use Tomedio\StripeBundle\Contract\SubscriptionPlanInterface;
use Tomedio\StripeBundle\Contract\StripeUserInterface;
use Tomedio\StripeBundle\Enum\SubscriptionStatus;

#[ORM\Entity]
class Subscription implements SubscriptionInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private StripeUserInterface $user;

    #[ORM\ManyToOne(targetEntity: SubscriptionPlan::class)]
    #[ORM\JoinColumn(nullable: false)]
    private SubscriptionPlanInterface $plan;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stripeSubscriptionId = null;

    #[ORM\Column(type: 'string', length: 20, enumType: SubscriptionStatus::class)]
    private SubscriptionStatus $status;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $trialEndDate = null;

    // Implement getters and setters for all properties

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): StripeUserInterface
    {
        return $this->user;
    }

    public function setUser(StripeUserInterface $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getPlan(): SubscriptionPlanInterface
    {
        return $this->plan;
    }

    public function setPlan(SubscriptionPlanInterface $plan): self
    {
        $this->plan = $plan;
        return $this;
    }

    public function getStripeSubscriptionId(): ?string
    {
        return $this->stripeSubscriptionId;
    }

    public function setStripeSubscriptionId(?string $stripeSubscriptionId): self
    {
        $this->stripeSubscriptionId = $stripeSubscriptionId;
        return $this;
    }

    public function getStatus(): SubscriptionStatus
    {
        return $this->status;
    }

    public function setStatus(SubscriptionStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getTrialEndDate(): ?\DateTimeInterface
    {
        return $this->trialEndDate;
    }

    public function setTrialEndDate(?\DateTimeInterface $trialEndDate): self
    {
        $this->trialEndDate = $trialEndDate;
        return $this;
    }
}
```

### StripeInvoiceInterface

This interface represents an invoice in your application.

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Tomedio\StripeBundle\Contract\StripeInvoiceInterface;
use Tomedio\StripeBundle\Contract\StripeUserInterface;
use Tomedio\StripeBundle\Contract\SubscriptionInterface;
use Tomedio\StripeBundle\Enum\Currency;
use Tomedio\StripeBundle\Enum\InvoiceStatus;

#[ORM\Entity]
class Invoice implements StripeInvoiceInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private StripeUserInterface $user;

    #[ORM\ManyToOne(targetEntity: Subscription::class)]
    private ?SubscriptionInterface $subscription = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $stripeInvoiceId;

    #[ORM\Column(type: 'integer')]
    private int $amount;

    #[ORM\Column(type: 'string', length: 3, enumType: Currency::class)]
    private Currency $currency;

    #[ORM\Column(type: 'string', length: 20, enumType: InvoiceStatus::class)]
    private InvoiceStatus $status;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $invoiceDate;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dueDate = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $paidDate = null;

    // Implement getters and setters for all properties

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): StripeUserInterface
    {
        return $this->user;
    }

    public function setUser(StripeUserInterface $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getSubscription(): ?SubscriptionInterface
    {
        return $this->subscription;
    }

    public function setSubscription(?SubscriptionInterface $subscription): self
    {
        $this->subscription = $subscription;
        return $this;
    }

    public function getStripeInvoiceId(): string
    {
        return $this->stripeInvoiceId;
    }

    public function setStripeInvoiceId(string $stripeInvoiceId): self
    {
        $this->stripeInvoiceId = $stripeInvoiceId;
        return $this;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function setCurrency(Currency $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function getStatus(): InvoiceStatus
    {
        return $this->status;
    }

    public function setStatus(InvoiceStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getInvoiceDate(): \DateTimeInterface
    {
        return $this->invoiceDate;
    }

    public function setInvoiceDate(\DateTimeInterface $invoiceDate): self
    {
        $this->invoiceDate = $invoiceDate;
        return $this;
    }

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeInterface $dueDate): self
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    public function getPaidDate(): ?\DateTimeInterface
    {
        return $this->paidDate;
    }

    public function setPaidDate(?\DateTimeInterface $paidDate): self
    {
        $this->paidDate = $paidDate;
        return $this;
    }
}
```

### CreditTransactionInterface

This interface represents a credit transaction in your application.

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): StripeUserInterface
    {
        return $this->user;
    }

    public function setUser(StripeUserInterface $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getBalanceAfter(): int
    {
        return $this->balanceAfter;
    }

    public function setBalanceAfter(int $balanceAfter): self
    {
        $this->balanceAfter = $balanceAfter;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getTransactionDate(): \DateTimeInterface
    {
        return $this->transactionDate;
    }

    public function setTransactionDate(\DateTimeInterface $transactionDate): self
    {
        $this->transactionDate = $transactionDate;
        return $this;
    }

    public function getType(): CreditTransactionType
    {
        return $this->type;
    }

    public function setType(CreditTransactionType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getReferenceId(): ?string
    {
        return $this->referenceId;
    }

    public function setReferenceId(?string $referenceId): self
    {
        $this->referenceId = $referenceId;
        return $this;
    }

    public function getMonetaryAmount(): ?int
    {
        return $this->monetaryAmount;
    }

    public function setMonetaryAmount(?int $monetaryAmount): self
    {
        $this->monetaryAmount = $monetaryAmount;
        return $this;
    }

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function setCurrency(?Currency $currency): self
    {
        $this->currency = $currency;
        return $this;
    }
}
```

## Repository Interfaces

In addition to the entity interfaces, you'll need to implement repository classes for your entities. These repositories should extend Doctrine's `ServiceEntityRepository` class.

Here's an example for the `SubscriptionPlanRepository`:

```php
<?php

namespace App\Repository;

use App\Entity\SubscriptionPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SubscriptionPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubscriptionPlan::class);
    }
    
    // Add any custom repository methods you need
}
```

## Next Steps

After implementing these interfaces, you can:

1. [Set up event listeners]({% link _docs/integration/event-listeners.md %}) to handle Stripe events
2. [Create a subscription checkout flow]({% link _docs/features/subscription-checkout.md %})
3. [Implement API Platform integration]({% link _docs/integration/api-platform.md %})
