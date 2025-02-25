<?php

namespace App\Form;

use App\Entity\Blog;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class BlogType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'attr' => [
                    'class' => 'form-control bg-dark text-light',
                    'placeholder' => 'Enter title'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Please enter a title',
                    ]),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Title should be at least {{ limit }} characters',
                        'maxMessage' => 'Title cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('content', TextareaType::class, [
                'attr' => [
                    'class' => 'form-control bg-dark text-light',
                    'rows' => 10,
                    'placeholder' => 'Write your blog post content here'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Please enter content',
                    ]),
                    new Assert\Length([
                        'min' => 10,
                        'minMessage' => 'Content should be at least {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('imageFile', FileType::class, [
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control bg-dark text-light',
                    'accept' => 'image/*'
                ],
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPEG, PNG, GIF)',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Blog::class,
            'attr' => [
                'novalidate' => 'novalidate',
            ],
        ]);
    }
}
