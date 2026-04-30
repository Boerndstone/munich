<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Rock;
use App\Entity\Topo;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

final class TopoEditorCreateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rocks', EntityType::class, [
                'class' => Rock::class,
                'choice_label' => static fn (Rock $r): string => (string) ($r->getName() ?? ''),
                'label' => 'Fels',
                'required' => true,
                'constraints' => [new NotBlank()],
                'query_builder' => $options['rock_query_builder'], // (RockRepository) => QueryBuilder
            ])
            ->add('name', TextType::class, [
                'label' => 'Name',
                'required' => true,
                'constraints' => [new NotBlank()],
            ])
            ->add('number', IntegerType::class, [
                'label' => 'Topo-Nummer (Sektor)',
                'required' => true,
                'constraints' => [new NotBlank()],
            ])
            ->add('withSector', CheckboxType::class, [
                'label' => 'Mit Sektoren',
                'required' => false,
            ])
            ->add('image', FileType::class, [
                'label' => 'Topo-Bild (optional)',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new File(
                        maxSize: '20M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                        mimeTypesMessage: 'Bitte ein gültiges Bild hochladen (JPEG, PNG, WebP oder GIF).'
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Topo::class,
        ]);
        $resolver->setRequired('rock_query_builder');
        $resolver->setAllowedTypes('rock_query_builder', \Closure::class);
    }
}
