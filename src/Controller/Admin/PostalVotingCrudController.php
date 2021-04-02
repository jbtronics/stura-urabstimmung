<?php


namespace App\Controller\Admin;


use App\Entity\Embeddable\Address;
use App\Entity\PostalVotingRegistration;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;


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
        $actions->disable(Crud::PAGE_NEW);
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addPanel('registration.new.general'),
            IdField::new('id', 'registration.id'),
            TextField::new('first_name', 'registration.first_name'),
            TextField::new('last_name', 'registration.last_name'),
            TextField::new('email', 'registration.email'),
            TextField::new('student_number', 'registration.student_number'),
            TextField::new('secret', 'registration.secret')->setFormTypeOption('disabled', true),
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
            BooleanField::new('printed', 'registration.printed')->hideOnIndex(),
            BooleanField::new('counted', 'registration.counted')->hideOnIndex(),
        ];
    }
}