<?php


namespace App\Controller;


use App\Entity\Embeddable\Address;
use App\Entity\PostalVotingRegistration;
use App\Form\PostalVotingRegistrationType;
use App\Message\SendEmailConfirmation;
use App\Repository\PostalVotingRegistrationRepository;
use App\Services\PDFGenerator\BallotPaperGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/postal_voting")
 */
class PostalVotingRegistrationController extends AbstractController
{

    /**
     * @Route("/register", name="postal_voting_register")
     */
    public function new(Request $request, EntityManagerInterface $entityManager, RateLimiterFactory $registrationSubmitLimiter): Response
    {
        $limiter = $registrationSubmitLimiter->create($request->getClientIp());

        $new_registration = new PostalVotingRegistration();
        $new_registration->setLanguage($request->getLocale());

        $form = $this->createForm(PostalVotingRegistrationType::class, $new_registration);

        if (!$form instanceof Form) {
            throw new InvalidArgumentException('$form must be a Form object!');
        }

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                /* Limit the amount of how many payment orders can be submitted by one user in an hour
                   This prevents automatic mass creation of payment orders and also prevents that skip token can
                   guessed by brute force */
                $limiter->consume(1)
                    ->ensureAccepted();

                $entityManager->persist($new_registration);

                $entityManager->flush();
                $this->dispatchMessage(new SendEmailConfirmation($new_registration));

                $this->addFlash('success', 'flash.saved_successfully');

                return $this->redirectToRoute('homepage');

            } else {
                $this->addFlash('error', 'flash.error.check_input');
            }
        }

        $limit = $limiter->consume(0);

        $response = $this->render('PostalVotingRegistration/registration.html.twig', [
            'form' => $form->createView(),
            'entity' => $new_registration,
            'registration_closed' => $this->getParameter('app.registration_closed')
        ]);

        $response->headers->add(
            [
                'X-RateLimit-Remaining' => $limit->getRemainingTokens(),
                'X-RateLimit-Retry-After' => $limit->getRetryAfter()
                    ->getTimestamp(),
                'X-RateLimit-Limit' => $limit->getLimit(),
            ]
        );

        return $response;

    }

    /**
     * @Route("/{id}/ballot_paper", name="postal_voting_ballot_paper")
     * @return Response
     */
    public function ballotPaper(PostalVotingRegistration $registration, BallotPaperGenerator $ballotPaperGenerator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_REGISTRATION_PRINT');

        $pdf = $ballotPaperGenerator->generateSingleBallotPaper($registration);
        //$pdf = $ballotPaperGenerator->generateMultipleBallotPapers([$registration, $registration, $registration]);

        $response = new Response($pdf);
        $response->headers->set('Content-type', 'application/pdf');
        $response->headers->set('Content-length', strlen($pdf));
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-Disposition', 'inline');

        return $response;
    }

    /**
     * @Route("/{id}/scan", name="postal_voting_scan")
     * @param  PostalVotingRegistration  $registration
     * @return Response
     */
    public function scan(?PostalVotingRegistration $registration = null, EntityManagerInterface $entityManager, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_REGISTRATION_COUNT');

        //$this->addFlash('error', 'Not implemented yet!');

        $builder = $this->createFormBuilder();
        $builder->add('submit', SubmitType::class, [
            'attr' => ['class'=> 'btn btn-primary btn-block btn-lg'],
            'label' => 'Der Wahlschein ist gültig',
        ]);
        $builder->add('invalid', SubmitType::class, [
            'attr' => ['class'=> 'btn btn-secondary btn-sm text-center'],
            'label' => 'Der Wahlschein ist ungültig',
        ]);
        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $registration !== null) {
            if ($form->get('submit')->isClicked()) {
                $this->addFlash('success', 'Wahlschein wurde als gezählt vermerkt');
                $registration->setCounted(true);
                $registration->setBallotPaperInvalid(false);
                $entityManager->flush();
                return $this->redirectToRoute('homepage');
            } elseif($form->get('invalid')->isClicked()) {
                $this->addFlash('success', 'Wahlschein wurde als ungültig vermerkt');
                $registration->setCounted(false);
                $registration->setBallotPaperInvalid(true);
                $entityManager->flush();
            }
        }

        return $this->render('scan.html.twig', [
            'registration' => $registration,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/count", name="postal_voting_count")
     * @return Response
     */
    public function count(EntityManagerInterface $entityManager, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_REGISTRATION_COUNT');

        $builder = $this->createFormBuilder();
        $builder->add('search', SearchType::class, [
            'attr' => ['placeholder' => 'Matrikelnr. oder Email']
        ]);
        $builder->add('submit', SubmitType::class, [
            'label' => 'Suchen'
        ]);
        $form = $builder->getForm();

        $registration = null;

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var PostalVotingRegistrationRepository $repo */
            $repo = $entityManager->getRepository(PostalVotingRegistration::class);
            try {
                $registration = $repo->findByMailOrStudentNumber(trim($form->get('search')->getData()));
            } catch (NonUniqueResultException $exception) {
                $this->addFlash('error', 'Matrikelnummer nicht eindeutig. Bitte Email-Addresse benutzen!');
            }
        }

        return $this->render('count.html.twig', [
            'form' => $form->createView(),
            'registration' => $registration
        ]);
    }

    /**
     * @Route("/{id}/confirm", name="postal_voting_confirm")
     * @param  PostalVotingRegistration  $registration
     * @return Response
     */
    public function confirm(PostalVotingRegistration $registration, Request $request, EntityManagerInterface $entityManager): Response
    {
        $given_token = (string) $request->query->get('token');
        if (!password_verify($given_token, $registration->getConfirmationToken())) {
            $this->addFlash('error', 'registration.confirmation.invalid_token');
            return $this->redirectToRoute('homepage');
        }

        if ($registration->getConfirmationDate() === null) {
            $registration->setConfirmationDate(new \DateTime('now'));
            $this->addFlash('success', 'registration.confirmation.success');
        } else {
            $this->addFlash('warning', 'registration.confirmation.already_confirmed');
        }

        $entityManager->flush();

        return $this->render('PostalVotingRegistration/confirmation_success.html.twig');
    }

}