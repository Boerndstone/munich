<?php

namespace App\Form\Type;

use App\Entity\RockTranslation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RockTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('locale', ChoiceType::class, [
                'label' => 'Sprache',
                'choices' => [
                    'Deutsch' => 'de',
                    'English' => 'en',
                ],
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Beschreibung',
                'required' => false,
                'empty_data' => '',
                'attr' => ['rows' => 6],
            ])
            ->add('access', TextareaType::class, [
                'label' => 'Zustieg',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('nature', TextareaType::class, [
                'label' => 'Naturschutz',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('flowers', TextareaType::class, [
                'label' => 'Pflanzen',
                'required' => false,
                'attr' => ['rows' => 3],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RockTranslation::class,
        ]);
    }
}
