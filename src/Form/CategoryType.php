<?php

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Image' => 'image',
                    'Video' => 'video',
                    'Audio' => 'audio',
                    'Other' => 'other'
                ],
                'placeholder' => 'Select a type'
            ])
            ->add('description', TextareaType::class)
            ->add('allowedMimeTypes', ChoiceType::class, [
                'choices' => [
                    'Image Formats' => [
                        'JPEG' => 'image/jpeg',
                        'PNG' => 'image/png',
                        'GIF' => 'image/gif',
                        'WebP' => 'image/webp',
                        'SVG' => 'image/svg+xml',
                    ],
                    'Video Formats' => [
                        'MP4' => 'video/mp4',
                        'WebM' => 'video/webm',
                        'AVI' => 'video/x-msvideo',
                        'MPEG' => 'video/mpeg',
                    ],
                    'Audio Formats' => [
                        'MP3' => 'audio/mpeg',
                        'WAV' => 'audio/wav',
                        'OGG' => 'audio/ogg',
                    ],
                    'Document Formats' => [
                        'PDF' => 'application/pdf',
                    ]
                ],
                'multiple' => true,
                'expanded' => true,
            ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();
            
            if (isset($data['type']) && $data['type']) {
                $category = $form->getData();
                if ($category) {
                    $category->setType($data['type']);
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}
