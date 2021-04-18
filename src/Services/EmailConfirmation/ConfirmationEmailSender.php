<?php
/*
 * Copyright (C) 2020  Jan Böhmer
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Services\EmailConfirmation;

use App\Entity\PaymentOrder;
use App\Entity\PostalVotingRegistration;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This service is responsible for sending the confirmation emails for a payment_order.
 */
class ConfirmationEmailSender
{
    private $mailer;
    private $tokenGenerator;
    private $entityManager;
    private $translator;


    public function __construct(MailerInterface $mailer, ConfirmationTokenGenerator $tokenGenerator,
        EntityManagerInterface $entityManager, TranslatorInterface $translator)
    {
        $this->mailer = $mailer;
        $this->tokenGenerator = $tokenGenerator;
        $this->entityManager = $entityManager;
        $this->translator = $translator;

    }

    /**
     * Send the confirmation email to the second verification person for the given payment_order.
     * Email addresses are taken from department (and are added as BCC)
     * A token is generated, send via email and saved in hashed form in the payment order.
     * Calling this function will flush database.
     * If no applicable emails are found (or email notifications are disabled) the payment order will be confirmed and
     * no email is sent.
     */
    public function sendConfirmation(PostalVotingRegistration $registration): void
    {
        $token = $this->tokenGenerator->getToken();
        $registration->setConfirmationToken($this->hash_token($token));
        $this->entityManager->flush();

        $this->sendConfirmationEmail($registration, $token);
    }

    /**
     * Sents a confirmation email for the given payment order for a plaintext token.
     *
     * @param PostalVotingRegistration $registration       The paymentOrder for which the email should be generated
     * @param string       $token               The plaintext token to access confirmation page.
     * @throws TransportExceptionInterface
     */
    private function sendConfirmationEmail(PostalVotingRegistration $registration, string $token): void
    {
        //We can not continue if the payment order is not serialized / has an ID (as we cannot generate an URL for it)
        if (null === $registration->getId()) {
            throw new InvalidArgumentException('$registration must be persisted / have an ID so than an confirmation URL can be generated!');
        }

        if ($registration->isConfirmed()) {
            throw new InvalidArgumentException('Given $registration was already confirmed! Can not resend email.');
        }

        $email = new TemplatedEmail();

        $email->priority(Email::PRIORITY_HIGH);
        $email->replyTo('urabstimmung@stura.uni-jena.de');

        $email->subject(
            $this->translator->trans(
                'registration.confirmation_email.subject',
                [],
                null,
                $registration->getLanguage(),
            )
        );

        $email->htmlTemplate('mails/confirmation.html.twig');
        $email->context([
            'registration' => $registration,
            'token' => $token,
        ]);

        $toAddress = new Address($registration->getEmail(), $registration->getFullName());
        $email->to($toAddress);

        $this->mailer->send($email);
    }

    private function hash_token(string $token): string
    {
        return password_hash($token, PASSWORD_DEFAULT);
    }
}
