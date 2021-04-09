<?php


namespace App\Controller\Admin;


use App\Entity\PostalVotingRegistration;
use App\Message\SendEmailConfirmation;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\LanguageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Intl\Languages;


class PostalVotingCrudController extends AbstractCrudController
{

    public static function getEntityFqcn(): string
    {
        return PostalVotingRegistration::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('registration.label')
            ->setEntityLabelInPlural('registration.labelp')
            ->setSearchFields(['id','email', 'student_number', 'first_name', 'last_name']);
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
                ->displayIf(function (PostalVotingRegistration $registration) {
                    return !$registration->isConfirmed();
                })
                ->linkToCrudAction('sendConfirmationEmail');

            $actions->add('detail', $sendConfirmationEmail);
        }

        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function sendConfirmationEmail(AdminContext $context)
    {
        $registration = $context->getEntity()->getInstance();
        $this->dispatchMessage(new SendEmailConfirmation($registration));
        $this->addFlash('success', 'registration.send_confirmation_email.success');

        return $this->redirect($context->getReferrer());
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
                ->setPermission('ROLE_REGISTRATION_SECRET'),
            ChoiceField::new('language', 'registration.language')
                ->setChoices([
                    Languages::getName('de') => 'de',
                    Languages::getName('en') => 'en'
                ])->hideOnIndex(),
            BooleanField::new('voting_kit_requested', 'registration.voting_kit_requested')->hideOnIndex(),

            FormField::addPanel('registration.new.shipping'),
            TextField::new('address.street_and_number', 'address.street_and_number')->hideOnIndex(),
            TextField::new('address.address_addition', 'address.address_addition')->hideOnIndex(),
            TextField::new('address.postal_code', 'address.postal_code')->hideOnIndex(),
            TextField::new('address.city', 'address.city')->hideOnIndex(),
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