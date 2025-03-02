<?php
namespace App\Form;

use App\Entity\TradeOffer;
use App\Entity\User;
use App\Entity\Artwork;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class TradeOfferType extends AbstractType
{
    private $security;
    private $entityManager;

    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentUser = $this->security->getUser();
        $receiverArtwork = $options['receiver_artwork'] ?? null;

        $builder
            ->add('receiver_name', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'name',
                'label' => 'Trading With',
                'disabled' => $options['receiver_readonly'],
                'attr' => [
                    'class' => 'form-control form-control-sm'
                ]
            ])
            ->add('offered_item', EntityType::class, [
                'class' => Artwork::class,
                'choice_label' => function (Artwork $artwork) {
                    return $artwork->getTitle();
                },
                'label' => 'Your Item to Offer',
                'placeholder' => 'Select your artwork to offer',
                'required' => true,
                'query_builder' => function (EntityRepository $er) use ($currentUser) {
                    return $er->createQueryBuilder('a')
                        ->where('a.owner = :owner')
                        ->setParameter('owner', $currentUser);
                },
                'attr' => [
                    'class' => 'form-control form-control-sm'
                ]
            ])
            ->add('received_item', EntityType::class, [
                'class' => Artwork::class,
                'choice_label' => function (Artwork $artwork) {
                    return $artwork->getTitle();
                },
                'label' => 'Item You Want',
                'disabled' => $options['received_item_readonly'],
                'attr' => [
                    'class' => 'form-control form-control-sm'
                ]
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Enter a message for the trade offer (optional)',
                    'rows' => 4,
                    'class' => 'form-control'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TradeOffer::class,
            'csrf_protection' => false,
            'receiver_readonly' => false,
            'received_item_readonly' => false,
            'receiver_artwork' => null,
        ]);

        $resolver->setAllowedTypes('receiver_artwork', ['null', Artwork::class]);
    }
}