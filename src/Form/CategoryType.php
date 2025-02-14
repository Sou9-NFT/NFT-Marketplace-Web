<?php

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
{
    private array $defaultMimeTypes = [
        'image' => ['image/jpeg', 'image/png', 'image/webp'],
        'video' => ['video/mp4', 'video/webm'],
        'audio' => ['audio/mpeg', 'audio/wav']
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Image' => 'image',
                    'Video' => 'video',
                    'Audio' => 'audio'
                ]
            ])
            ->add('description', TextareaType::class)
            ->add('allowedMimeTypes', CollectionType::class, [
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'by_reference' => false
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $category = $event->getData();
            if ($category && $category->getType() && empty($category->getAllowedMimeTypes())) {
                $type = $category->getType();
                if (isset($this->defaultMimeTypes[$type])) {
                    $category->setAllowedMimeTypes($this->defaultMimeTypes[$type]);
                }
            }
        });

        $builder->get('type')->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $type = $event->getForm()->getData();
            $form = $event->getForm()->getParent();
            $category = $form->getData();

            if ($type && empty($category->getAllowedMimeTypes())) {
                if (isset($this->defaultMimeTypes[$type])) {
                    $category->setAllowedMimeTypes($this->defaultMimeTypes[$type]);
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class
        ]);
    }
}
