<?php

namespace App\Form;

use App\Entity\Raffle;
use App\Entity\Artwork;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;
use Doctrine\ORM\EntityRepository;

class RaffleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];

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
            ->add('artwork', EntityType::class, [
                'class' => Artwork::class,
                'choice_label' => function(Artwork $artwork) {
                    return sprintf('%s (Price: $%s)', 
                        $artwork->getTitle(),
                        number_format($artwork->getPrice(), 2)
                    );
                },
                'group_by' => function($artwork) {
                    return $artwork->getCategory()->getName();
                },
                'query_builder' => function (EntityRepository $er) use ($user) {
                    return $er->createQueryBuilder('a')
                        ->where('a.owner = :owner')
                        ->setParameter('owner', $user)
                        ->orderBy('a.createdAt', 'DESC');
                },
                'label' => 'Select Artwork',
                'required' => true,
                'placeholder' => 'Choose your artwork...',
                'attr' => [
                    'class' => 'artwork-select',
                    'data-live-search' => 'true'
                ],
                'constraints' => [
                    new NotNull([
                        'message' => 'Please select an artwork to raffle',
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Raffle::class,
            'is_edit' => false,
            'user' => null,
        ]);

        $resolver->setRequired(['user']);
        $resolver->setAllowedTypes('user', [User::class]);
    }
}
