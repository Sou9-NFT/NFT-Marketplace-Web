<?php

namespace App\Form;

use App\Entity\TradeDispute;
use App\Entity\TradeOffer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TradeDisputeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        
        $builder
        ->add('trade_id', EntityType::class, [
            'class' => TradeOffer::class,
            'choice_label' => function (TradeOffer $tradeOffer) {
                    return sprintf('%d', $tradeOffer->getId());
                },
            'placeholder' => 'Select a trade offer',
            'required' => true,
        ])
            ->add('reason')
            ->add('evidence', FileType::class, [
                'label' => 'Evidence (Image)',
                'mapped' => false,  // We won't map this field to the entity directly
                'required' => false,
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\Image([
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                        'mimeTypesMessage' => 'Please upload a valid image (jpeg, png, gif).',
                    ]),
                ],
            ])
            ->add('timestamp', null, [
                'widget' => 'single_text',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TradeDispute::class,
            'trade_offers' => [],
        ]);
    }
}
