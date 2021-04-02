<?php


namespace App\Validator;


use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class PersonalEmailAddress extends Constraint
{
    public $message = "validator.must_be_personal_address";
}