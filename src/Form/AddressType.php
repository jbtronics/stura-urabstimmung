<?php


namespace App\Form;


use App\Entity\Embeddable\Address;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('street_and_number', TextType::class, [
            'label' => 'address.street_and_number'
        ]);
        $builder->add('address_addition', TextType::class, [
            'label' => 'address.address_addition',
            'required' => false,
            'empty_data' => '',
        ]);
        $builder->add('postal_code', TextType::class, [
            'label' => 'address.postal_code'
        ]);
        $builder->add('city', TextType::class, [
            'label' => 'address.city'
        ]);
        $builder->add('country', CountryType::class, [
            'label' => 'address.country',
            'disabled' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', Address::class);
    }
}