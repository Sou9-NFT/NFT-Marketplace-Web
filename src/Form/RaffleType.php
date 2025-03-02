<?php

namespace App\Form;

use App\Entity\Raffle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\File;

class RaffleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a title',
                    ]),
                    new Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Title should be at least {{ limit }} characters long',
                        'maxMessage' => 'Title cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('creator_name', TextType::class, [
                'label' => 'Creator Name',
                'required' => true,
                'attr' => [
                    'readonly' => true,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter the creator name',
                    ]),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Creator name cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('raffleDescription', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'attr' => [
                    'rows' => 5,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a description',
                    ]),
                    new Length([
                        'min' => 10,
                        'max' => 1000,
                        'minMessage' => 'Description should be at least {{ limit }} characters long',
                        'maxMessage' => 'Description cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('endTime', DateTimeType::class, [
                'label' => 'End Time',
                'widget' => 'single_text',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please specify an end time',
                    ]),
                    new GreaterThan([
                        'value' => 'now',
                        'message' => 'End time must be in the future',
                    ]),
                ],
            ])
            ->add('image', FileType::class, [
                'label' => 'Raffle Image',
                'mapped' => false,
                'required' => !$options['is_edit'],
                'constraints' => $options['is_edit'] ? [] : [
                    new NotBlank([
                        'message' => 'Please upload an image',
                    ]),
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, GIF)',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Raffle::class,
            'is_edit' => false,
        ]);
    }
}
