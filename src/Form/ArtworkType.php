<?php

namespace App\Form;

use App\Entity\Artwork;
use App\Entity\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\File;

class ArtworkType extends AbstractType
{
    private $requestStack;
    
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control', 'rows' => 5]
            ])
            ->add('price', NumberType::class, [
                'label' => 'Price (ETH)',
                'attr' => ['class' => 'form-control']
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Category',
                'attr' => ['class' => 'form-select']
            ]);
              // Always include the image file field, but mark it as not required if we're using an AI-generated image
        $request = $this->requestStack->getCurrentRequest();
        $aiImageData = $request ? $request->getSession()->get('ai_generated_image') : null;
        $aiImageParameter = $request ? $request->query->get('aiImage') : null;
        $usingAiImage = $aiImageData || $aiImageParameter;
        
        // Always add the imageFile field, but make it optional when using AI image
        $builder->add('imageFile', FileType::class, [
            'label' => 'Image File',
            'required' => (!$usingAiImage && $options['data']->getId() === null), // Required only for new artworks without AI image
            'attr' => ['class' => 'form-control'],
            'constraints' => $options['data']->getId() === null && !$usingAiImage ? [
                new File([
                    'maxSize' => '100M',
                ])
            ] : []
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Artwork::class,
            'validation_groups' => function ($form) {
                $data = $form->getData();
                $request = $this->requestStack->getCurrentRequest();
                $aiImage = $request && ($request->query->has('aiImage') || $request->getSession()->has('ai_generated_image'));
                
                // If editing an existing artwork with no new file, or using AI image
                if (($data && $data->getId()) || $aiImage || $data->getImageName()) {
                    return ['Default'];
                }
                
                return ['Default', 'file_required'];
            }
        ]);
    }
}
