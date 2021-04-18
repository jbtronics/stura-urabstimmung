<?php


namespace App\Controller\Admin;


use App\Admin\Filter\ConfirmedFilter;
use App\Entity\PostalVotingRegistration;
use App\Message\SendEmailConfirmation;
use App\Services\PDFGenerator\BallotPaperGenerator;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\LanguageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Intl\Languages;


class PostalVotingCrudController extends AbstractCrudController
{
    private $ballotPaperGenerator;

    public function __construct(BallotPaperGenerator $ballotPaperGenerator)
    {
        $this->ballotPaperGenerator = $ballotPaperGenerator;
    }

    public static function getEntityFqcn(): string
    {
        return PostalVotingRegistration::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('registration.label')
            ->setEntityLabelInPlural('registration.labelp')
            ->setSearchFields(['id','email', 'student_number', 'first_name', 'last_name', 'address.city', 'address.postal_code', 'address.street_and_number']);
    }

    public function configureActions(Actions $actions): Actions
    {
        //$actions->disable(Crud::PAGE_NEW);

        $actions->setPermissions([
            Action::EDIT => 'ROLE_REGISTRATION_EDIT',
            Action::DELETE => 'ROLE_REGISTRATION_DELETE',
            Action::NEW => 'ROLE_REGISTRATION_EDIT',
            Action::INDEX => 'ROLE_REGISTRATION_VIEW',
            Action::DETAIL => 'ROLE_REGISTRATION_VIEW',
        ]);

        if ($this->isGranted('ROLE_REGISTRATION_PRINT')) {
            $ballotPaper = Action::new('ballotPaper', 'registration.generate_ballot_paper')
                ->addCssClass('btn btn-secondary')
                ->displayIf(function (PostalVotingRegistration $registration) {
                    return $registration->isConfirmed();
                })
                ->linkToRoute('postal_voting_ballot_paper', function (PostalVotingRegistration $registration): array {
                    return [
                        'id' => $registration->getId()->toRfc4122()
                    ];
                });

            $actions->add('detail', $ballotPaper);
        }

        if ($this->isGranted('ROLE_REGISTRATION_EDIT')) {
            $sendConfirmationEmail = Action::new('sendConfirmationEmail', 'registration.send_confirmation_email')
                ->addCssClass('btn btn-secondary')
                ->displayIf(function (PostalVotingRegistration $registration) {
                    return !$registration->isConfirmed();
                })
                ->linkToCrudAction('sendConfirmationEmail');

            $actions->add('detail', $sendConfirmationEmail);
            $actions->add('edit', $sendConfirmationEmail);

            $sendConfirmationEmailMass = Action::new('sendConfirmationEmailMass', 'registration.send_confirmation_email')
                ->addCssClass('btn btn-secondary')
                ->linkToCrudAction('sendConfirmationEmailMass');

            $actions->addBatchAction($sendConfirmationEmailMass);
        }

        if ($this->isGranted('ROLE_REGISTRATION_VERIFY')) {
            $verifyRegistration = Action::new('verify', 'registration.verify')
                ->addCssClass('btn btn-primary')
                ->linkToCrudAction('verifyRegistration');

            $actions->addBatchAction($verifyRegistration);
        }

        if ($this->isGranted('ROLE_REGISTRATION_PRINT')) {
            $printBallot = Action::new('ballotPaperMass', 'registration.generate_ballot_paper')
                ->addCssClass('btn btn-primary')
                ->linkToCrudAction('ballotPaperMass');

            $actions->addBatchAction($printBallot);
        }

        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function ballotPaperMass(BatchActionDto $batchActionDto): Response
    {
        $entityManager = $this->getDoctrine()->getManagerForClass($batchActionDto->getEntityFqcn());
        if ($entityManager === null) {
            throw new \RuntimeException('entityManager must not be null!');
        }

        $registrations_to_print = [];

        foreach ($batchActionDto->getEntityIds() as $id) {
            /** @var PostalVotingRegistration $registration */
            $registration = $entityManager->find($batchActionDto->getEntityFqcn(), $id);
            //Only allow to verify the registration if the postal voting is confirmed
            if ($registration->isConfirmed() && $registration->isVerified() && !$registration->isPrinted()) {
                $registration->setPrinted(true);
                $registrations_to_print[] = $registration;
            }
        }

        $pdf = $this->ballotPaperGenerator->generateMultipleBallotPapers($registrations_to_print);

        $response = new Response($pdf);
        $response->headers->set('Content-type', 'application/pdf');
        $response->headers->set('Content-length', strlen($pdf));
        $response->headers->set('Cache-Control', 'private');
        // Create the disposition of the file
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'Wahlscheine_' . date('Y-m-d_H-i-s') . '.pdf'
        );
        $response->headers->set('Content-Disposition', $disposition);

        $entityManager->flush();

        return $response;
    }

    public function sendConfirmationEmailMass(BatchActionDto $batchActionDto): Response
    {
        $entityManager = $this->getDoctrine()->getManagerForClass($batchActionDto->getEntityFqcn());
        if ($entityManager === null) {
            throw new \RuntimeException('entityManager must not be null!');
        }

        foreach ($batchActionDto->getEntityIds() as $id) {
            /** @var PostalVotingRegistration $registration */
            $registration = $entityManager->find($batchActionDto->getEntityFqcn(), $id);
            //Only allow to verify the registration if the postal voting is confirmed
            if (!$registration->isConfirmed()) {
                $this->dispatchMessage(new SendEmailConfirmation($registration));
            }


        }

        $this->addFlash('success', 'registration.send_confirmation_email.success');

        return $this->redirect($batchActionDto->getReferrerUrl());
    }

    public function sendConfirmationEmail(AdminContext $context): Response
    {
        $registration = $context->getEntity()->getInstance();
        $this->dispatchMessage(new SendEmailConfirmation($registration));
        $this->addFlash('success', 'registration.send_confirmation_email.success');

        return $this->redirect($context->getReferrer());
    }

    public function verifyRegistration(BatchActionDto $batchActionDto): Response
    {
        $entityManager = $this->getDoctrine()->getManagerForClass($batchActionDto->getEntityFqcn());
        if ($entityManager === null) {
            throw new \RuntimeException('entityManager must not be null!');
        }
        foreach ($batchActionDto->getEntityIds() as $id) {
            /** @var PostalVotingRegistration $registration */
            $registration = $entityManager->find($batchActionDto->getEntityFqcn(), $id);
            //Only allow to verify the registration if the postal voting is confirmed
            if ($registration->isConfirmed()) {
                $registration->setVerified(true);
            }
        }

        $entityManager->flush();

        return $this->redirect($batchActionDto->getReferrerUrl());
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addPanel('registration.new.general'),
            IdField::new('id', 'registration.id')->hideOnForm(),
            TextField::new('first_name', 'registration.first_name'),
            TextField::new('last_name', 'registration.last_name'),
            TextField::new('email', 'registration.email'),
            TextField::new('student_number', 'registration.student_number'),
            TextField::new('secret', 'registration.secret')
                ->setFormTypeOption('disabled', true)
                ->hideOnIndex()
                ->setPermission('ROLE_REGISTRATION_SECRET'),
            ChoiceField::new('language', 'registration.language')
                ->setChoices([
                    Languages::getName('de') => 'de',
                    Languages::getName('en') => 'en'
                ])->hideOnIndex(),
            //BooleanField::new('voting_kit_requested', 'registration.voting_kit_requested')->hideOnIndex(),

            FormField::addPanel('registration.new.shipping'),
            TextField::new('address.street_and_number', 'address.street_and_number'),
            TextField::new('address.address_addition', 'address.address_addition')
                ->setRequired(false)
                ->setFormTypeOption('empty_data', '')
                ->hideOnIndex(),
            TextField::new('address.postal_code', 'address.postal_code'),
            TextField::new('address.city', 'address.city'),
            CountryField::new('address.country', 'address.country')->hideOnIndex(),

            FormField::addPanel('registration.status'),
            DateTimeField::new('creation_date', 'creation_date')->hideOnForm(),
            DateTimeField::new('last_modified', 'last_modified')->onlyOnDetail(),
            DateTimeField::new('confirmation_date', 'registration.confirmation_date')->onlyOnDetail(),
            BooleanField::new('verified', 'registration.verified')->hideOnIndex(),
            BooleanField::new('printed', 'registration.printed')->hideOnIndex(),
            BooleanField::new('counted', 'registration.counted')->hideOnIndex(),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('id', 'registration.id'))
            ->add(ConfirmedFilter::new('confirmed', 'registration.confirmed'))
            ->add(BooleanFilter::new('verified', 'registration.verified'))
            ->add(BooleanFilter::new('printed', 'registration.printed'))
            ->add(BooleanFilter::new('counted', 'registration.counted'))
            ->add(ChoiceFilter::new('language', 'registration.language')->setChoices(['Deutsch' => 'de', 'Englisch' => 'en']))
            ->add(DateTimeFilter::new('creation_date', 'creation_date'))
            ->add(DateTimeFilter::new('last_modified', 'last_modified'))
            ;
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var PostalVotingRegistration $entityInstance */
        //Forbit delete process if PaymentOrder was already exported or booked
        if ($entityInstance->isPrinted()
                || $entityInstance->isCounted()) {
            $this->addFlash('warning', 'payment_order.flash.can_not_delete_checked_payment_order');

            return;
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }
}