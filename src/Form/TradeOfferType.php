<?php
namespace App\Form;

use App\Entity\TradeOffer;
use App\Entity\User;
use App\Entity\Artwork;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class TradeOfferType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('receiver_name', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a receiver',
                'required' => true,
            ])
            ->add('offered_item', EntityType::class, [
                'class' => Artwork::class,
                'choice_label' => 'imageName',
                'placeholder' => 'Select an offered item',
                'required' => true,
            ])
            ->add('received_item', EntityType::class, [
                'class' => Artwork::class,
                'choice_label' => 'imageName',
                'placeholder' => 'Select a received item',
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Enter a description for your trade offer',
                    'rows' => 4
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TradeOffer::class,
            'csrf_protection' => false,
        ]);
    }
}
