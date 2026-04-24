<?php

namespace App\Form;

use App\Entity\Photos;
use App\Entity\Routes;
use App\Entity\Rock;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;

class PhotoUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('belongsToRock', EntityType::class, [
                'class' => Rock::class,
                'choice_label' => 'name',
                'label' => 'upload_photo.label.rock',
                'placeholder' => 'upload_photo.placeholder.rock',
                'required' => true,
                'query_builder' => function ($er) {
                    return $er->createQueryBuilder('r')
                        ->where('r.online = :online')
                        ->setParameter('online', true)
                        ->orderBy('r.name', 'ASC');
                },
                'constraints' => [
                    new NotBlank(['message' => 'upload_photo.validation.rock_required']),
                ],
            ])
            ->add('belongsToRoute', EntityType::class, [
                'class' => Routes::class,
                'choice_label' => function (Routes $route) {
                    return $route->getName() . ' (' . ($route->getRock() ? $route->getRock()->getName() : '') . ')';
                },
                'label' => 'upload_photo.label.route',
                'placeholder' => 'upload_photo.placeholder.route',
                'required' => false,
                'query_builder' => function ($er) {
                    return $er->createQueryBuilder('r')
                        ->where('r.rock IS NOT NULL')
                        ->orderBy('r.name', 'ASC');
                },
            ])
            ->add('image', FileType::class, [
                'label' => 'upload_photo.label.image',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'upload_photo.validation.image_required']),
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'upload_photo.validation.mime_types',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'upload_photo.label.description',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                ],
            ])
            ->add('uploaderName', TextType::class, [
                'label' => 'upload_photo.label.uploader_name',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'upload_photo.validation.name_required']),
                ],
            ])
            ->add('uploaderEmail', EmailType::class, [
                'label' => 'upload_photo.label.uploader_email',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'upload_photo.validation.email_required']),
                    new Email(['message' => 'upload_photo.validation.email_invalid']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Photos::class,
            'translation_domain' => 'messages',
        ]);
    }
}
