<?php


namespace App\Controller;


use App\Entity\Embeddable\Address;
use App\Entity\PostalVotingRegistration;
use App\Form\PostalVotingRegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/postal_voting")
 */
class PostalVotingRegistrationController extends AbstractController
{

    /**
     * @Route("/register")
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $new_registration = new PostalVotingRegistration();

        $form = $this->createForm(PostalVotingRegistrationType::class, $new_registration);

        if (!$form instanceof Form) {
            throw new InvalidArgumentException('$form must be a Form object!');
        }

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {

                $entityManager->persist($new_registration);

                $entityManager->flush();
                $this->addFlash('success', 'flash.saved_successfully');

                return $this->redirectToRoute('homepage');

            } else {
                $this->addFlash('error', 'flash.error.check_input');
            }
        }

        return $this->render('PostalVotingRegistration/registration.html.twig', [
            'form' => $form->createView(),
            'entity' => $new_registration,
        ]);

    }


}