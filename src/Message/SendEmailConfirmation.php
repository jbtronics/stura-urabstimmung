<?php


namespace App\Message;


use App\Entity\PostalVotingRegistration;
use Symfony\Component\Uid\Uuid;

class SendEmailConfirmation
{
    /**
     * @var Uuid
     */
    private $registration_id;

    public function __construct(PostalVotingRegistration $registration)
    {
        $this->registration_id = $registration->getId();
        if ($this->registration_id === null) {
            throw new \InvalidArgumentException('The PostalVotingRegistration must be persisted before you can send this message!');
        }
    }

    public function getRegistrationID(): Uuid
    {
        return $this->registration_id;
    }
}