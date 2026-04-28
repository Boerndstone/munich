<?php

namespace App\Form;

use App\Dto\RockImprovementSuggestion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class RockGalleryUploadType extends AbstractType
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
                'row_attr' => [
                    'class' => 'pointer-events-none absolute -left-[9999px] m-0 block h-px w-px overflow-hidden border-0 p-0 opacity-0',
                    'aria-hidden' => 'true',
                ],
            ])
            ->add('rockName', TextType::class, [
                'label' => 'rock_improvement.rock_name',
                'attr' => [
                    'readonly' => 'readonly',
                    'class' => 'cursor-default bg-gray-100 dark:bg-gray-900',
                ],
                'row_attr' => ['class' => 'mb-3'],
            ])
            ->add('routeId', ChoiceType::class, [
                'label' => 'upload_photo.label.route',
                'required' => false,
                'placeholder' => 'upload_photo.placeholder.route',
                'choices' => $options['route_choices'],
                'row_attr' => ['class' => 'mb-3'],
            ])
            ->add('name', TextType::class, [
                'label' => 'rock_improvement.name',
                'attr' => ['placeholder' => $this->translator->trans('rock_improvement.placeholder.name')],
                'row_attr' => ['class' => 'mb-3'],
                'constraints' => [
                    new NotBlank(['message' => 'rock_improvement.name_required']),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'rock_improvement.email',
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('rock_improvement.placeholder.email'),
                    'autocomplete' => 'email',
                ],
                'row_attr' => ['class' => 'mb-3'],
            ])
            ->add('photographer', TextType::class, [
                'label' => 'upload_photo.label.photographer',
                'required' => false,
                'attr' => ['placeholder' => $this->translator->trans('upload_photo.placeholder.photographer')],
                'row_attr' => ['class' => 'mb-3'],
            ])
            ->add('image', FileType::class, [
                'label' => 'rock_improvement.image',
                'required' => true,
                'attr' => [
                    'accept' => 'image/jpeg,image/png,image/webp,image/gif',
                ],
                'row_attr' => ['class' => 'mb-3'],
                'constraints' => [
                    new NotBlank(['message' => 'upload_photo.validation.image_required']),
                ],
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'upload_photo.label.description',
                'required' => false,
                'attr' => ['placeholder' => $this->translator->trans('rock_improvement.placeholder.comment'), 'rows' => 3],
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
            'route_choices' => [],
        ]);
        $resolver->setAllowedTypes('route_choices', ['array']);
    }
}
