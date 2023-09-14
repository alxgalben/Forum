<?php

namespace App\Form;

use App\Entity\Reply;
use App\Form\DataTransformer\HtmlSanitizerTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ReplyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('body', TextareaType::class, [
                'label' => 'Content'
            ])
            ->add($builder->create('file', FileType::class, [
                'label' => 'File',
                'required' => false,
                'data_class' => null,
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '1024k',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                        'mimeTypesMessage' => 'Please upload a valid image file!',
                    ]),
                ]
            ]))
        ;

        $builder
            ->get('body')
            ->addModelTransformer(new HtmlSanitizerTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reply::class,
        ]);
    }
}
