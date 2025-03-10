<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Service;

use Stripe\Exception\ApiErrorException;
use Stripe\Price;
use Stripe\Product;
use Tomedio\StripeBundle\Contract\SubscriptionPlanInterface;

class PlanService
{
    private StripeClient $stripeClient;

    public function __construct(StripeClient $stripeClient)
    {
        $this->stripeClient = $stripeClient;
    }

    /**
     * Sync a subscription plan to Stripe.
     * Creates or updates the product and price in Stripe.
     *
     * @throws ApiErrorException
     */
    public function syncPlanToStripe(SubscriptionPlanInterface $plan): SubscriptionPlanInterface
    {
        // Create or update the product
        $product = $this->createOrUpdateProduct($plan);
        $plan->setStripeProductId($product->id);

        // Create the price
        $price = $this->createPrice($plan, $product->id);
        $plan->setStripePriceId($price->id);

        return $plan;
    }

    /**
     * Create or update a product in Stripe.
     *
     * @throws ApiErrorException
     */
    private function createOrUpdateProduct(SubscriptionPlanInterface $plan): Product
    {
        $productData = [
            'name' => $plan->getName(),
            'description' => $plan->getDescription(),
            'metadata' => [
                'plan_id' => $plan->getId(),
            ],
        ];

        // If the plan already has a Stripe product ID, update it
        if ($plan->getStripeProductId()) {
            return $this->stripeClient->execute(function () use ($plan, $productData) {
                return $this->stripeClient->getClient()->products->update(
                    $plan->getStripeProductId(),
                    $productData
                );
            });
        }

        // Otherwise, create a new product
        return $this->stripeClient->execute(function () use ($productData) {
            return $this->stripeClient->getClient()->products->create($productData);
        });
    }

    /**
     * Create a price in Stripe.
     *
     * @throws ApiErrorException
     */
    private function createPrice(SubscriptionPlanInterface $plan, string $productId): Price
    {
        $priceData = [
            'product' => $productId,
            'unit_amount' => $plan->getAmount(),
            'currency' => $plan->getCurrency()->value,
            'recurring' => [
                'interval' => $plan->getInterval()->value,
            ],
            'metadata' => [
                'plan_id' => $plan->getId(),
            ],
        ];

        return $this->stripeClient->execute(function () use ($priceData) {
            return $this->stripeClient->getClient()->prices->create($priceData);
        });
    }

    /**
     * Retrieve all subscription plans from Stripe.
     *
     * @throws ApiErrorException
     */
    public function getPlansFromStripe(): array
    {
        $products = $this->stripeClient->execute(function () {
            return $this->stripeClient->getClient()->products->all(['active' => true]);
        });

        $plans = [];
        foreach ($products->data as $product) {
            $prices = $this->stripeClient->execute(function () use ($product) {
                return $this->stripeClient->getClient()->prices->all([
                    'product' => $product->id,
                    'active' => true,
                ]);
            });

            foreach ($prices->data as $price) {
                $plans[] = [
                    'product' => $product,
                    'price' => $price,
                ];
            }
        }

        return $plans;
    }

    /**
     * Deactivate a plan in Stripe.
     *
     * @throws ApiErrorException
     */
    public function deactivatePlan(SubscriptionPlanInterface $plan): void
    {
        if (!$plan->getStripeProductId()) {
            return;
        }

        // Deactivate the product
        $this->stripeClient->execute(function () use ($plan) {
            return $this->stripeClient->getClient()->products->update(
                $plan->getStripeProductId(),
                ['active' => false]
            );
        });

        // Deactivate the price if it exists
        if ($plan->getStripePriceId()) {
            $this->stripeClient->execute(function () use ($plan) {
                return $this->stripeClient->getClient()->prices->update(
                    $plan->getStripePriceId(),
                    ['active' => false]
                );
            });
        }
    }
}
