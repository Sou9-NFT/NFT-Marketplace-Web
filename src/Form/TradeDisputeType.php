<?php

namespace App\Form;

use App\Entity\TradeDispute;
use App\Entity\TradeOffer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotBlank;

class TradeDisputeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Only show trade_id field if not in admin edit mode
        if (!($options['is_admin'] && $options['is_edit'])) {
            $builder->add('trade_id', EntityType::class, [
                'class' => TradeOffer::class,
                'choice_label' => function (TradeOffer $tradeOffer) {
                    return sprintf('Trade #%d', $tradeOffer->getId());
                },
                'placeholder' => 'Select a trade offer',
                'required' => true,
            ]);
        }

        $builder->add('reason', TextType::class, [
            'required' => true,
            'attr' => [
                'placeholder' => 'Enter the reason for your dispute',
            ],
        ]);

        // Only add evidence field if not admin edit mode
        if (!($options['is_admin'] && $options['is_edit'])) {
            // Build constraints array based on whether we're editing
            $constraints = [
                new Image([
                    'maxSize' => '5M',
                    'mimeTypes' => [
                        'image/jpeg',
                        'image/png',
                        'image/gif'
                    ],
                    'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, GIF)',
                ])
            ];

            // Add NotBlank constraint only for new disputes
            if (!$options['is_edit']) {
                $constraints[] = new NotBlank([
                    'message' => 'Please upload an evidence image for your dispute'
                ]);
            }

            $builder->add('evidence', FileType::class, [
                'label' => 'Evidence (Image)',
                'mapped' => false,
                'required' => !$options['is_edit'],
                'constraints' => $constraints,
                'attr' => [
                    'accept' => 'image/*',
                ],
            ]);

            // Add form event to set the current filename
            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $tradeDispute = $event->getData();
                $form = $event->getForm();

                if ($tradeDispute && $tradeDispute->getEvidence()) {
                    $form->add('evidence', FileType::class, [
                        'label' => 'Evidence (Image)',
                        'mapped' => false,
                        'required' => false,
                        'constraints' => [
                            new Image([
                                'maxSize' => '5M',
                                'mimeTypes' => [
                                    'image/jpeg',
                                    'image/png',
                                    'image/gif'
                                ],
                                'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, GIF)',
                            ])
                        ],
                        'attr' => [
                            'accept' => 'image/*',
                        ],
                        'help' => 'Current file: ' . $tradeDispute->getEvidence(),
                        'help_html' => true,
                    ]);
                }
            });
        }

        // Only add status field for admin users
        if ($options['is_admin']) {
            $builder->add('status', ChoiceType::class, [
                'choices' => [
                    'Pending' => 'pending',
                    'Resolved' => 'resolved',
                    'Rejected' => 'rejected'
                ],
                'required' => true,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TradeDispute::class,
            'is_admin' => false,
            'is_edit' => false,
        ]);

        $resolver->setAllowedTypes('is_admin', 'bool');
        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
