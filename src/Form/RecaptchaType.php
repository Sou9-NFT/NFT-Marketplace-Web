<?php
// src/Form/RecaptchaType.php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

class RecaptchaType extends AbstractType
{
    private string $siteKey;

    public function __construct(string $siteKey)
    {
        $this->siteKey = $siteKey;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('recaptcha_response', HiddenType::class, [
            'constraints' => [new NotBlank()],
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['site_key'] = $this->siteKey;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'mapped' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'recaptcha';
    }
}
