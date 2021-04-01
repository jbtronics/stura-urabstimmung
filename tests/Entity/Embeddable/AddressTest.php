<?php

namespace App\Tests\Entity\Embeddable;

use App\Entity\Embeddable\Address;
use PHPUnit\Framework\TestCase;

class AddressTest extends TestCase
{

    public function testFormatMultiline()
    {
        $address = new Address();
        $address->setStreetAndNumber("Teststraße 3");
        $address->setPostalCode("12345");
        $address->setCity("Musterstadt");
        self::assertSame("Teststraße 3\n12345 Musterstadt", $address->formatMultiline());
        self::assertSame("Max Muster\nTeststraße 3\n12345 Musterstadt", $address->formatMultiline("Max Muster"));

        $address->setAddressAddition("c/o Test");
        self::assertSame("Max Muster\nc/o Test\nTeststraße 3\n12345 Musterstadt", $address->formatMultiline("Max Muster"));

        $address->setAddressAddition("");
        $address->setCountry("FR");
        self::assertSame("Teststraße 3\n12345 Musterstadt\nFRANCE", $address->formatMultiline());
    }

    public function testFormatSingleLine()
    {
        $address = new Address();
        $address->setStreetAndNumber("Teststraße 3");
        $address->setPostalCode("12345");
        $address->setCity("Musterstadt");
        $address->setCountry("FR");
        $address->setAddressAddition("c/o Test");
        self::assertSame("Teststraße 3, 12345 Musterstadt", $address->formatSingleLine());
        self::assertSame("Max Muster, Teststraße 3, 12345 Musterstadt", $address->formatSingleLine("Max Muster"));
    }
}
