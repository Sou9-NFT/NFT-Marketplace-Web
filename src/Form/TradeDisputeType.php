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
    if ($options['is_admin'] && $options['is_edit']) {
        // Admin edit form - only show status field
        $builder->add('status', ChoiceType::class, [
            'choices' => [
                'Pending' => 'pending',
                'Resolved' => 'resolved',
                'Rejected' => 'rejected'
            ],
            'required' => true,
        ]);
        return;
    }

    // Remove trade_id field since it's now set in the controller
    $builder->add('reason', TextType::class, [
        'required' => true,
        'attr' => [
            'placeholder' => 'Enter the reason for your dispute',
        ],
    ]);

    // Only add evidence field if not in admin edit mode
    if (!($options['is_admin'] && $options['is_edit'])) {
        $constraints = [
            new Image([
                'maxSize' => '5M',
                'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, GIF)',
            ])
        ];

        // Require evidence only for new disputes
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
            'attr' => ['accept' => 'image/*'],
        ]);
    }

    // Add status field for admin users managing disputes
    if ($options['is_admin'] && !$options['is_edit']) {
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
