<?php

namespace App\Form;

use App\Entity\Blog;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class BlogType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'attr' => [
                    'class' => 'form-control bg-dark text-light',
                    'placeholder' => 'Enter blog title',
                    'minlength' => 3,
                    'maxlength' => 255,
                ],
                'label_attr' => [
                    'class' => 'text-purple'
                ],
            ])
            ->add('content', TextareaType::class, [
                'attr' => [
                    'class' => 'form-control bg-dark text-light',
                    'placeholder' => 'Write your blog content here...',
                    'rows' => 10,
                    'minlength' => 10,
                    'style' => 'resize: vertical;'
                ],
                'label_attr' => [
                    'class' => 'text-purple'
                ],
            ])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control bg-dark text-light',
                    'max' => (new \DateTime())->modify('+1 year')->format('Y-m-d'),
                    'min' => (new \DateTime())->modify('-1 year')->format('Y-m-d'),
                ],
                'label_attr' => [
                    'class' => 'text-purple'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Please select a date',
                    ]),
                    new Assert\Type([
                        'type' => 'object',
                        'message' => 'Please enter a valid date',
                    ]),
                    new Assert\LessThanOrEqual([
                        'value' => '+1 year',
                        'message' => 'Date cannot be more than 1 year in the future',
                    ]),
                    new Assert\GreaterThanOrEqual([
                        'value' => '-1 year',
                        'message' => 'Date cannot be more than 1 year in the past',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Blog::class,
            'attr' => [
                'novalidate' => 'novalidate',
            ],
        ]);
    }
}
