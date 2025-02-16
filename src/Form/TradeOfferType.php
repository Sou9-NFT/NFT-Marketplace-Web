<?php
namespace App\Form;

use App\Entity\TradeOffer;
use App\Entity\User;
use App\Entity\Artwork;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\NotBlank;


class TradeOfferType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('receiver_name', EntityType::class, [
            'class' => User::class,
            'choice_label' => 'name',
            'placeholder' => 'Select a receiver',
            'required' => true,
        ])
    // The rest of the fields remain unchanged


        
    ->add('offered_item', EntityType::class, [
        'class' => Artwork::class, // Specify the related entity class
        'choice_label' => 'imageName', // Assuming 'imageName' is a field in the Artwork entity
        'placeholder' => 'Choose a Offered Item',
        'required' => true,
    ])
    ->add('received_item', EntityType::class, [
        'class' => Artwork::class, // Specify the related entity class
        'choice_label' => 'imageName', // Assuming 'imageName' is a field in the Artwork entity
        'placeholder' => 'Choose a Received Item',
        'required' => true,
    ])
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Create Offer',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TradeOffer::class,
            'users' => [],
            'artworks' => [],
        ]);
    }

    
}
