<?php


namespace App\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailWithHostnameType extends AbstractType
{
    public function getParent()
    {
        return TextType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(new CallbackTransformer(
            function (?string $data) use ($options) {
                if ($data === null) {
                    return null;
                }

                return str_replace('@' . $options['hostname'], '', $data);
            },
            function (?string $data) use ($options) {
                if ($data === null) {
                    return null;
                }

                //Remove a hostname part if it was inputted, increases the UX a bit
                $data = str_replace('@' . $options['hostname'], '', $data);
                //Only append if no hostname is given, to show a useful error message
                if (strpos($data, "@") === false) {
                    return $data.'@'.$options['hostname'];
                }
                return $data;
            }
        ));
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        parent::finishView($view, $form, $options);
        $view->vars['hostname_part'] = '@' . $options['hostname'];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('hostname');
        $resolver->setAllowedTypes('hostname', 'string');
    }
}