<?php

namespace App\Form;

use App\Entity\Artwork;
use App\Entity\BetSession;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;

class BetSessionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startTime', DateTimeType::class, [
                'widget' => 'single_text',
            ])
            ->add('endTime', DateTimeType::class, [
                'widget' => 'single_text',
            ])
            ->add('initialPrice', MoneyType::class, [
                'currency' => false,
            ])
            ->add('artwork', EntityType::class, [
                'class' => Artwork::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BetSession::class,
        ]);
    }
}
