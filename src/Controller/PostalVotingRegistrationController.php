<?php


namespace App\Controller;


use App\Entity\Embeddable\Address;
use App\Entity\PostalVotingRegistration;
use App\Form\PostalVotingRegistrationType;
use App\Services\PDFGenerator\BallotPaperGenerator;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
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
    public function scan(PostalVotingRegistration $registration): Response
    {
        $this->denyAccessUnlessGranted('ROLE_REGISTRATION_COUNT');

        $this->addFlash('error', 'Not implemented yet!');

        return $this->render('homepage.html.twig');
    }

}