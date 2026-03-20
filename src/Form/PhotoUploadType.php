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
                'label' => 'Fels',
                'placeholder' => 'Bitte wählen Sie einen Fels',
                'required' => true,
                'query_builder' => function ($er) {
                    return $er->createQueryBuilder('r')
                        ->where('r.online = :online')
                        ->setParameter('online', true)
                        ->orderBy('r.name', 'ASC');
                },
                'constraints' => [
                    new NotBlank(['message' => 'Bitte wählen Sie einen Fels']),
                ],
            ])
            ->add('belongsToRoute', EntityType::class, [
                'class' => Routes::class,
                'choice_label' => function (Routes $route) {
                    return $route->getName() . ' (' . ($route->getRock() ? $route->getRock()->getName() : '') . ')';
                },
                'label' => 'Route (optional)',
                'placeholder' => 'Optional: Wählen Sie eine Route',
                'required' => false,
                'query_builder' => function ($er) {
                    return $er->createQueryBuilder('r')
                        ->where('r.rock IS NOT NULL')
                        ->orderBy('r.name', 'ASC');
                },
            ])
            ->add('image', FileType::class, [
                'label' => 'Bild',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Bitte wählen Sie ein Bild']),
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Bitte laden Sie nur Bilder hoch (JPEG, PNG, WebP oder GIF)',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Beschreibung (optional)',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                ],
            ])
            ->add('uploaderName', TextType::class, [
                'label' => 'Ihr Name',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Bitte geben Sie Ihren Namen ein']),
                ],
            ])
            ->add('uploaderEmail', EmailType::class, [
                'label' => 'Ihre E-Mail',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Bitte geben Sie Ihre E-Mail ein']),
                    new Email(['message' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Photos::class,
        ]);
    }
}
