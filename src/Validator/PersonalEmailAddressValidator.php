<?php


namespace App\Validator;


use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class PersonalEmailAddressValidator extends ConstraintValidator
{

    public const PERSONAL_EMAIL_REGEX = "/(^\w+(\.\w+)+|\w{1,2}\d\w{3,4})@.*/";

    public const EMAIL_BLACKLIST = [
        'fsr.wiwi@uni-jena.de',
        'anmeldung-fsr.wiwi@uni-jena.de',
        'geschaeftsleitung-fsr.wiwi@uni-jena.de',
        'fsr.bioinformatik@uni-jena.de',
        'fsr.pharmazie@uni-jena.de',
        'fsr.bio@uni-jena.de',
        'fsr.soziologie@uni-jena.de',
        'fsr.ernaerhung@uni-jena.de',
        'fsr.pyschologie@uni-jena.de',
        'fsr.romanistik@uni-jena.de',
        'buecher-fsr.wiwi@uni-jena.de',
        'vorstand-fsr.wiwi@uni-jena.de',
        'klausuren-fsr.wiwi@uni-jena.de',
        'info.ersties-fsr.wiwi@uni-jena.de',
        'studium-fsr.wiwi@uni-jena.de',
        'repraesentation-fsr.wiwi@uni-jena.de',
        'homepage-fsr.wiwi@uni-jena.de',
        'fsr.theologie@uni-jena.de',
        'vorstand-fsr.rewi@uni-jena.de',
        'events-fsr.rewi@uni-jena.de',
        'pr-fsr.rewi@uni-jena.de',
        'info-fsr.rewi@uni-jena.de',
        'finanzen-fsr.rewi@uni-jena.de',
        'buero.ref-fsr.wiwi@uni-jena.de',
        'service.fv-fsr.wiwi@uni-jena.de',
        'rezension-fsr.rewi@uni-jena.de',
        'cooperation-fsr.wiwi@uni-jena.de',
        'fsr.anglistik-amerikanistik@uni-jena.de',
        'fsr.orientkaukindo@uni-jena.de',
        'fsr.paf@uni-jena.de',
        'fsr.kunst.film@uni-jena.de',
    ];

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof PersonalEmailAddress) {
            throw new UnexpectedTypeException($constraint, PersonalEmailAddress::class);
        }

        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) to take care of that
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            // throw this exception if your validator cannot handle the passed type so that it can be marked as invalid
            throw new UnexpectedValueException($value, 'string');
        }

        if(!self::isPersonalEmailAddress($value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $value)
                ->addViolation();
        }

    }

    public static function isPersonalEmailAddress(string $email): bool
    {
        if (preg_match(self::PERSONAL_EMAIL_REGEX, $email) !== 1) {
            return false;
        }
        if (in_array($email, self::EMAIL_BLACKLIST, false)) {
            return false;
        }

        return true;
    }
}