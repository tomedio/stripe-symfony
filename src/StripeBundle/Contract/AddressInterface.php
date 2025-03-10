<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\Contract;

interface AddressInterface
{
    /**
     * Get the name (person or company name).
     */
    public function getName(): ?string;

    /**
     * Get the street name.
     */
    public function getStreet(): ?string;

    /**
     * Get the building number.
     */
    public function getBuildingNumber(): ?string;

    /**
     * Get additional address details (apartment number, floor, etc.).
     */
    public function getAdditionalDetails(): ?string;

    /**
     * Get the city.
     */
    public function getCity(): ?string;

    /**
     * Get the state/province/region.
     */
    public function getState(): ?string;

    /**
     * Get the postal code.
     */
    public function getPostalCode(): ?string;

    /**
     * Get the country (2-letter ISO code).
     */
    public function getCountry(): ?string;

    /**
     * Get the tax ID (VAT number, EIN, etc.).
     */
    public function getTaxId(): ?string;

    /**
     * Check if this is a legal entity (company) or a private person.
     */
    public function isLegalEntity(): bool;

    /**
     * Get the phone number.
     */
    public function getPhone(): ?string;
}
