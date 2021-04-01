<?php


namespace App\Entity\Embeddable;

use Doctrine\ORM\Mapping\Embeddable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Validator\Constraints as Assert;
use ZipCodeValidator\Constraints\ZipCode;

/**
 * An object representing an Address object
 * @Embeddable
 */
class Address
{
    /**
     * @var string street and housenumber
     * @ORM\Column(type="string")
     * @Assert\Length(max=64)
     * @Assert\NotBlank()
     */
    private $street_and_number = "";

    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\Length(max=64)
     */
    private $address_addition = "";

    /**
     * @var string
     * @ORM\Column(type="string")
     * @ZipCode(getter="getCountry")
     * @Assert\NotBlank()
     */
    private $postal_code = "";

    /**
     * @var string The name of the city
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     */
    private $city = "";

    /**
     * @var string The two-letter ISO code of the country
     * @ORM\Column(type="string")
     * @Assert\Country()
     * @Assert\NotBlank()
     */
    private $country = "DE";

    /**
     * @return string
     */
    public function getStreetAndNumber(): string
    {
        return $this->street_and_number;
    }

    /**
     * @param  string  $street_and_number
     * @return Address
     */
    public function setStreetAndNumber(string $street_and_number): Address
    {
        $this->street_and_number = $street_and_number;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddressAddition(): string
    {
        return $this->address_addition;
    }

    /**
     * @param  string  $address_addition
     * @return Address
     */
    public function setAddressAddition(string $address_addition): Address
    {
        $this->address_addition = $address_addition;
        return $this;
    }

    /**
     * @return string
     */
    public function getPostalCode(): string
    {
        return $this->postal_code;
    }

    /**
     * @param  string  $postal_code
     * @return Address
     */
    public function setPostalCode(string $postal_code): Address
    {
        $this->postal_code = $postal_code;
        return $this;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @param  string  $city
     * @return Address
     */
    public function setCity(string $city): Address
    {
        $this->city = $city;
        return $this;
    }

    /**
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * @param  string  $country
     * @return Address
     */
    public function setCountry(string $country): Address
    {
        $this->country = $country;
        return $this;
    }

    /**
     * Formats an address as multiline string
     * @param  string|null  $full_name The full name that should be added. Leave empty to just output the address
     * @return string
     */
    public function formatMultiline(?string $full_name = null): string
    {
        $tmp = '';
        if ($full_name) {
            $tmp .= $full_name."\n";
        }
        if ($this->address_addition) {
            $tmp .= $this->address_addition . "\n";
        }

        $tmp .= $this->street_and_number . "\n";
        $tmp .= $this->postal_code . ' ' . $this->city;

        if ($this->country !== "DE") {
            $tmp .= "\n" . strtoupper(Countries::getName($this->country, 'en_US'));
        }

        return $tmp;
    }

    public function formatSingleLine(?string $full_name = null): string
    {
        $tmp = '';
        if ($full_name) {
            $tmp = $full_name . ', ';
        }
        $tmp .= $this->street_and_number . ', ' . $this->postal_code . ' ' . $this->city;

        return $tmp;
    }

}