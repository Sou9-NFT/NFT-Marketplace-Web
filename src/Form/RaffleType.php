<?php

namespace App\Form;

use App\Entity\Raffle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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
            ->add('image', FileType::class, [
                'label' => 'Raffle Image',
                'mapped' => false,
                'required' => $options['require_image'] ?? true,
                'constraints' => [
                    new NotBlank(),
                    new File([
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ]
                    ])
                ],
            ])
            ->add('creator_name', null, [
                'label' => 'Creator Name',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 2,
                        'max' => 255, // Match the field length in the entity
                    ]),
                ],
            ])
            ->add('endTime', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'End Time',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new GreaterThan([
                        'value' => new \DateTime(),
                    ]),
                ],
            ])                      
            ->add('raffle_description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'class' => 'form-control',
                    'placeholder' => 'Enter a description for your raffle...'
                ],
                'constraints' => [
                    new Length([
                        'max' => 1000,
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Raffle::class,
            'require_image' => true,
        ]);
    }
}
