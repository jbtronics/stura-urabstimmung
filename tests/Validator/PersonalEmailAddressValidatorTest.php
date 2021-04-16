<?php

namespace App\Tests\Validator;

use App\Entity\PostalVotingRegistration;
use App\Validator\PersonalEmailAddressValidator;
use PHPUnit\Framework\TestCase;

class PersonalEmailAddressValidatorTest extends TestCase
{
    /**
     * @dataProvider personalEmailRegexDataProvider
     * @param  bool  $expected
     * @param  string  $test_email
     */
    public function testIsPersonalEmail(bool $expected, string $test_email): void
    {
        self::assertSame($expected, PersonalEmailAddressValidator::isPersonalEmailAddress($test_email));
    }

    public function personalEmailRegexDataProvider(): iterable
    {
        return [
            [true, 'max.muster@uni-jena.de'],
            [true, 'max.m.muster@uni-jena.de'],
            [true, 'max.moritz.muster@uni-jena.de'],
            [true, 'm.muster@uni-jena.de'],
            [true, 'muster.m@uni-jena.de'],
            //Allow dashes
            [true, 'max-moritz.muster@uni-jena.de'],
            [true, 'max.muster-mueller@uni-jena.de'],
            //Old format
            [true, 'a1bcde@uni-jena.de'],
            //Username + uni-jena.de (not sure if used, just for case)
            [true, 'ab1cde@uni-jena.de'],
            //Dont match known institutional emails
            [false, 'studium@uni-jena.de'],
            [false, 'praesident@uni-jena.de'],

            //Check for blacklisted emails
            [false, 'fsr.bioinformatik@uni-jena.de'],
            [false, 'finanzen-fsr.rewi@uni-jena.de'],
        ];
    }
}
