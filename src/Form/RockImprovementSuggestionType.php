<?php

namespace App\Form;

use App\Dto\RockImprovementSuggestion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class RockImprovementSuggestionType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('website', TextType::class, [
                'label' => 'Website',
                'required' => false,
                'attr' => [
                    'class' => 'form-control form-control-sm',
                    'tabindex' => '-1',
                    'autocomplete' => 'off',
                ],
                'label_attr' => ['class' => 'visually-hidden'],
                'row_attr' => ['class' => 'visually-hidden position-absolute'],
            ])
            ->add('name', TextType::class, [
                'label' => 'rock_improvement.name',
                'attr' => ['class' => 'form-control form-control-sm', 'placeholder' => $this->translator->trans('rock_improvement.placeholder.name')],
                'label_attr' => ['class' => 'form-label small fw-medium'],
                'row_attr' => ['class' => 'mb-3'],
            ])
            ->add('rockName', TextType::class, [
                'label' => 'rock_improvement.rock_name',
                'attr' => [
                    'class' => 'form-control form-control-sm bg-light',
                    'readonly' => 'readonly',
                ],
                'label_attr' => ['class' => 'form-label small fw-medium'],
                'row_attr' => ['class' => 'mb-3'],
            ])
            ->add('routeName', TextType::class, [
                'label' => 'rock_improvement.route_name',
                'attr' => ['class' => 'form-control form-control-sm', 'placeholder' => $this->translator->trans('rock_improvement.placeholder.route_name')],
                'label_attr' => ['class' => 'form-label small fw-medium'],
                'row_attr' => ['class' => 'mb-3'],
            ])
            ->add('grade', TextType::class, [
                'label' => 'rock_improvement.grade',
                'required' => false,
                'attr' => ['class' => 'form-control form-control-sm', 'placeholder' => $this->translator->trans('rock_improvement.placeholder.grade')],
                'label_attr' => ['class' => 'form-label small fw-medium'],
                'row_attr' => ['class' => 'mb-3'],
            ])
            ->add('firstAscent', TextType::class, [
                'label' => 'rock_improvement.first_ascent',
                'required' => false,
                'attr' => ['class' => 'form-control form-control-sm', 'placeholder' => $this->translator->trans('rock_improvement.placeholder.first_ascent')],
                'label_attr' => ['class' => 'form-label small fw-medium'],
                'row_attr' => ['class' => 'mb-3'],
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'rock_improvement.comment',
                'required' => false,
                'attr' => ['class' => 'form-control form-control-sm', 'placeholder' => $this->translator->trans('rock_improvement.placeholder.comment'), 'rows' => 3],
                'label_attr' => ['class' => 'form-label small fw-medium'],
                'row_attr' => ['class' => 'mb-3'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RockImprovementSuggestion::class,
        ]);
    }
}
