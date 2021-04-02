<?php


namespace App\Form;

use App\Entity\PostalVotingRegistration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostalVotingRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('first_name', TextType::class, [
            'label' => 'registration.first_name',
        ]);
        $builder->add('last_name', TextType::class, [
            'label' => 'registration.last_name'
        ]);
        $builder->add('email', EmailWithHostnameType::class, [
            'label' => 'registration.email',
            'hostname' => 'uni-jena.de'
        ]);
        $builder->add('student_number', TextType::class, [
            'label' => 'registration.student_number'
        ]);
        $builder->add('address', AddressType::class, [
            'label' => false
        ]);
        $builder->add('voting_kit_requested', CheckboxType::class, [
            'label' => 'registration.voting_kit_requested',
            'required' => false,
        ]);

        $builder->add('submit', SubmitType::class, [
            'label' => 'submit'
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', PostalVotingRegistration::class);
    }
}