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
use Symfony\Bundle\SecurityBundle\Security;

class BetSessionType extends AbstractType
{
    public function __construct(
        private Security $security
    ) {
    }

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
                'choice_label' => 'title',
                'placeholder' => 'Choose an artwork',
                'query_builder' => function ($repository) use ($options) {
                    return $repository->createQueryBuilder('a')
                        ->where('a.owner = :owner')
                        ->setParameter('owner', $options['user']);
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BetSession::class,
            'user' => $this->security->getUser(), 
        ]);
    }
}
