<?php

namespace App\Form;

use App\Dto\RockImprovementSuggestion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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
                'label' => false,
                'required' => false,
                'attr' => [
                    'tabindex' => '-1',
                    'autocomplete' => 'off',
                    'aria-hidden' => 'true',
                ],
                // Honeypot: must stay empty. Bootstrap visually-hidden is not in Tailwind — use off-screen + zero box.
                'row_attr' => [
                    'class' => 'pointer-events-none absolute -left-[9999px] m-0 block h-px w-px overflow-hidden border-0 p-0 opacity-0',
                    'aria-hidden' => 'true',
                ],
            ])
            ->add('name', TextType::class, [
                'label' => 'rock_improvement.name',
                'attr' => ['class' => 'form-control form-control-sm', 'placeholder' => $this->translator->trans('rock_improvement.placeholder.name')],
                'label_attr' => ['class' => 'form-label small fw-medium'],
                'row_attr' => ['class' => 'mb-3'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'rock_improvement.email',
                'required' => false,
                'attr' => [
                    'class' => 'form-control form-control-sm',
                    'placeholder' => $this->translator->trans('rock_improvement.placeholder.email'),
                    'autocomplete' => 'email',
                ],
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

        $builder->get('email')->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $value = $event->getData();
            if (!\is_string($value) || '' === trim($value)) {
                $event->setData(null);

                return;
            }
            $event->setData(trim($value));
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RockImprovementSuggestion::class,
        ]);
    }
}
