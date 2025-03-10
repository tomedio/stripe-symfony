<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Service;

use DateTimeImmutable;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Tomedio\StripeBundle\Contract\AddressInterface;
use Tomedio\StripeBundle\Contract\StripeUserInterface;
use Tomedio\StripeBundle\Enum\Currency;

class CustomerService
{
    private StripeClient $stripeClient;
    private ?SubscriptionService $subscriptionService;

    public function __construct(
        StripeClient $stripeClient,
        ?SubscriptionService $subscriptionService = null
    ) {
        $this->stripeClient = $stripeClient;
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Create a new customer in Stripe or retrieve an existing one.
     * Updates the user with information from Stripe.
     *
     * @throws ApiErrorException
     */
    public function getOrCreateCustomer(StripeUserInterface $user): StripeUserInterface
    {
        $customer = null;

        // If the user already has a Stripe customer ID, retrieve it
        if ($user->getStripeCustomerId()) {
            $customer = $this->retrieveCustomer($user->getStripeCustomerId());
        } else {
            // Otherwise, create a new customer
            $customerData = [
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'metadata' => [
                    'user_id' => $user->getUserIdentifier(),
                ],
            ];

            // Add address if available
            if ($address = $user->getBillingAddress()) {
                $customerData = $this->addAddressToCustomerData($customerData, $address);
            }

            $customer = $this->stripeClient->execute(function () use ($customerData) {
                return $this->stripeClient->getClient()->customers->create($customerData);
            });

            // Update the user with the new customer ID
            $user->setStripeCustomerId($customer->id);
        }

        // Update user with information from Stripe
        $this->updateUserFromStripeCustomer($user, $customer);

        return $user;
    }

    /**
     * Update an existing customer in Stripe.
     *
     * @throws ApiErrorException
     */
    public function updateCustomer(StripeUserInterface $user): StripeUserInterface
    {
        if (!$user->getStripeCustomerId()) {
            return $this->getOrCreateCustomer($user);
        }

        $customerData = [
            'email' => $user->getEmail(),
            'name' => $user->getName(),
        ];

        // Add address if available
        if ($address = $user->getBillingAddress()) {
            $customerData = $this->addAddressToCustomerData($customerData, $address);
        }

        $customer = $this->stripeClient->execute(function () use ($user, $customerData) {
            return $this->stripeClient->getClient()->customers->update(
                $user->getStripeCustomerId(),
                $customerData
            );
        });

        // Update user with information from Stripe
        $this->updateUserFromStripeCustomer($user, $customer);

        return $user;
    }

    /**
     * Delete a customer from Stripe.
     *
     * @throws ApiErrorException
     */
    public function deleteCustomer(StripeUserInterface $user): void
    {
        if (!$user->getStripeCustomerId()) {
            return;
        }

        $this->stripeClient->execute(function () use ($user) {
            $this->stripeClient->getClient()->customers->delete($user->getStripeCustomerId());
        });

        $user->setStripeCustomerId('');
        $user->setStripeBalance(null);
        $user->setStripeBalanceCurrency(null);
        $user->setActiveSubscription(null);
        $user->setStripeCreatedAt(null);
    }

    /**
     * Retrieve a customer from Stripe.
     *
     * @throws ApiErrorException
     */
    public function retrieveCustomer(string $customerId): ?Customer
    {
        return $this->stripeClient->execute(function () use ($customerId) {
            return $this->stripeClient->getClient()->customers->retrieve(
                $customerId,
                ['expand' => ['subscriptions']]
            );
        });
    }

    /**
     * Update user entity with information from Stripe customer.
     */
    private function updateUserFromStripeCustomer(StripeUserInterface $user, Customer $customer): void
    {
        // Set balance information
        if (isset($customer->balance)) {
            $user->setStripeBalance($customer->balance);
            
            // Default currency is USD if not specified
            $currency = isset($customer->currency) ? 
                Currency::from($customer->currency) : 
                Currency::USD;
            
            $user->setStripeBalanceCurrency($currency);
        }

        // Set created date
        if (isset($customer->created)) {
            $createdAt = new DateTimeImmutable('@' . $customer->created);
            $user->setStripeCreatedAt($createdAt);
        }

        // Set active subscription if available and subscription service is injected
        if ($this->subscriptionService !== null && isset($customer->subscriptions) && !empty($customer->subscriptions->data)) {
            // Find the first active subscription
            foreach ($customer->subscriptions->data as $stripeSubscription) {
                if ($stripeSubscription->status === 'active' || $stripeSubscription->status === 'trialing') {
                    $subscription = $this->subscriptionService->syncSubscriptionFromStripe($user, $stripeSubscription);
                    $user->setActiveSubscription($subscription);
                    break;
                }
            }
        }
    }

    /**
     * Add address information to customer data.
     */
    private function addAddressToCustomerData(array $customerData, AddressInterface $address): array
    {
        // Format the address line
        $line1 = trim(sprintf('%s %s', $address->getStreet(), $address->getBuildingNumber()));
        $line2 = $address->getAdditionalDetails();

        $customerData['address'] = [
            'line1' => $line1 ?: null,
            'line2' => $line2 ?: null,
            'city' => $address->getCity(),
            'state' => $address->getState(),
            'postal_code' => $address->getPostalCode(),
            'country' => $address->getCountry(),
        ];

        // Add phone if available
        if ($address->getPhone()) {
            $customerData['phone'] = $address->getPhone();
        }

        // Add tax ID if available
        if ($address->getTaxId()) {
            $taxIdType = $address->isLegalEntity() ? 'company' : 'individual';
            $customerData['tax_id_data'] = [
                [
                    'type' => $taxIdType,
                    'value' => $address->getTaxId(),
                ]
            ];
        }

        return $customerData;
    }
}
