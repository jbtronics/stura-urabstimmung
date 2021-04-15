<?php


namespace App\EntityListener;


use App\Entity\PostalVotingRegistration;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use GenPhrase\Password;
use Symfony\Component\HttpKernel\KernelInterface;

class FillSecretEntityListener
{
    /**
     * @ORM\PrePersist()
     */
    public function prePersistHandler(PostalVotingRegistration $registration, LifecycleEventArgs $event)
    {
        //Fill in secret if registration does not have one yet
        if(empty($registration->getSecret())) {
            $registration->setSecret($this->getSecret());
        }
    }

    public static function getSecret(): string
    {
        $project_path = __DIR__ . '/../../';

        $gen = new Password();
        $gen->removeWordlist('default');
        $gen->removeWordlist('diceware');

        $gen->addWordlist($project_path . 'assets/wordlists/diceware.lst', 'diceware');
        $gen->addWordlist($project_path . 'assets/wordlists/english.lst', 'english');

        $gen->disableSeparators(true);
        $gen->disableWordModifier(true);
        //$gen->alwaysUseSeparators(true);
        //$gen->

        //Use at least 30 bit of entropy
        return $gen->generate(30);
    }
}