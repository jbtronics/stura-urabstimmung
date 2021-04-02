<?php


namespace App\EntityListener;


use App\Entity\PostalVotingRegistration;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use GenPhrase\Password;

class FillSecretEntityListener
{

    /**
     * @ORM\PrePersist()
     */
    public function prePersistHandler(PostalVotingRegistration $registration, LifecycleEventArgs $event)
    {
        //Fill in secret if registration does not have one yet
        if(empty($registration->getSecret())) {
            $registration->setSecret(self::getSecret());
        }
    }

    public static function getSecret(): string
    {
        $gen = new Password();

        $gen->disableSeparators(true);
        $gen->disableWordModifier(true);
        //$gen->alwaysUseSeparators(true);
        //$gen->

        //Use at least 30 bit of entropy
        return $gen->generate(30);
    }
}