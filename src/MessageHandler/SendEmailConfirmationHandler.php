<?php


namespace App\MessageHandler;


use App\Entity\PostalVotingRegistration;
use App\Message\SendEmailConfirmation;
use App\Services\EmailConfirmation\ConfirmationEmailSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SendEmailConfirmationHandler implements MessageHandlerInterface
{
    private $confirmationEmailSender;
    private $entityManager;

    public function __construct(ConfirmationEmailSender $confirmationEmailSender, EntityManagerInterface $entityManager)
    {
        $this->confirmationEmailSender = $confirmationEmailSender;
        $this->entityManager = $entityManager;
    }

    public function __invoke(SendEmailConfirmation $message)
    {
        $repo = $this->entityManager->getRepository(PostalVotingRegistration::class);
        $registration = $repo->find($message->getRegistrationID());

        $this->confirmationEmailSender->sendConfirmation($registration);
    }
}