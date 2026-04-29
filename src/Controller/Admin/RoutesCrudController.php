<?php

namespace App\Controller\Admin;

use App\Entity\Rock;
use App\Entity\Routes;
use App\Repository\RockRepository;
use App\Service\GradeTranslationService;
use App\Service\RouteTopoChoiceService;
use App\Service\RockAccessService;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class RoutesCrudController extends AbstractCrudController
{
    public function __construct(
        private GradeTranslationService $gradeTranslationService,
        private RockAccessService $rockAccessService,
        private RouteTopoChoiceService $routeTopoChoiceService,
        private RockRepository $rockRepository,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Routes::class;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $this->rockAccessService->restrictRoutesQueryBuilder($qb, $this->getUser());

        return $qb;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return parent::configureFilters($filters)
            ->add(EntityFilter::new('area'))
            ->add(EntityFilter::new('rock'));
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions

            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action
                    ->setIcon('fa fa-plus')
                    ->setLabel('Tour hinzufügen')
                    ->setCssClass('btn btn-success');
            })

            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN, function (Action $action) {
                return $action
                    ->setLabel('Änderungen speichern')
                    ->setCssClass('btn btn-success');
            })

            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE, function (Action $action) {
                return $action
                    ->setLabel('Speichern und bearbeiten fortsetzen');
            })

            ->update(Crud::PAGE_NEW, Action::SAVE_AND_RETURN, function (Action $action) {
                return $action
                    ->setLabel('Speichern')
                    ->setCssClass('btn btn-success');
            })

            ->update(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER, function (Action $action) {
                return $action
                    ->setLabel('Speichern und ein weiteres Gebiet hinzufügen');
            })

            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action
                    ->setIcon('fa fa-trash')
                    ->setLabel(false);
            });
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setPageTitle(Crud::PAGE_INDEX, 'Übersicht der Routen')
            ->setPageTitle(Crud::PAGE_NEW, 'Neue Route hinzufügen')
            ->showEntityActionsInlined()
            ->setPageTitle(Crud::PAGE_EDIT, static function (Routes $routes) {
                return $routes->getName();
            })
            ->setFormOptions([
                'attr' => [
                    'novalidate' => null,
                    'data-controller' => 'admin-route-topo-sync',
                    'data-admin-route-topo-sync-url-template-value' => str_replace(
                        '888888888',
                        '__ROCK_ID__',
                        $this->generateUrl('admin_routes_topos_for_rock', ['rockId' => 888888888])
                    ),
                ],
            ]);
    }

    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $builder = parent::createNewFormBuilder($entityDto, $formOptions, $context);
        $this->attachRouteTopoFormListeners($builder);

        return $builder;
    }

    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $builder = parent::createEditFormBuilder($entityDto, $formOptions, $context);
        $this->attachRouteTopoFormListeners($builder);

        return $builder;
    }

    private function attachRouteTopoFormListeners(FormBuilderInterface $builder): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $route = $event->getData();
            $form = $event->getForm();
            $rock = $route instanceof Routes ? $route->getRock() : null;
            $this->replaceTopoChoiceField($form, $rock);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            if (!\is_array($data)) {
                return;
            }
            $form = $event->getForm();
            $rockId = $data['rock'] ?? null;
            $rock = null;
            if ($rockId !== null && $rockId !== '') {
                $rock = $this->rockRepository->find((int) $rockId);
            }
            $this->replaceTopoChoiceField($form, $rock);
        });
    }

    /**
     * Topo is not an ORM relation on Routes; choices are Topos for the selected Rock (Topo.rocks).
     * Rebuilt on load and on submit so labels match routes.topo_id ↔ topo.number.
     */
    private function replaceTopoChoiceField(FormInterface $form, ?Rock $rock): void
    {
        if ($form->has('topo_id')) {
            $form->remove('topo_id');
        }

        $choices = $this->routeTopoChoiceService->choicesForRock($rock);

        $form->add('topo_id', ChoiceType::class, [
            'label' => 'Sektor',
            'required' => false,
            'placeholder' => 'Topo wählen …',
            'choices' => $choices,
            'empty_data' => null,
            'property_path' => 'topoId',
            'attr' => [
                'class' => 'form-select',
                'data-admin-route-topo-sync-target' => 'topo',
            ],
            'help' => 'Wähle den entsprechenden Sektor.',
        ]);
    }

    public function createEntity(string $entityFqcn): Routes
    {
        $entity = parent::createEntity($entityFqcn);
        
        // Handle quick-add from rock page
        $request = $this->container->get('request_stack')->getCurrentRequest();
        if ($request) {
            $rockId = $request->query->get('rockId') ?? $request->query->get('rock');
            if ($rockId) {
                $rock = $this->container->get('doctrine')->getRepository(\App\Entity\Rock::class)->find($rockId);
                if ($rock) {
                    $entity->setRock($rock);
                    if (!$entity->getArea() && $rock->getArea()) {
                        $entity->setArea($rock->getArea());
                    }
                }
            }
            
            // Configuration for property setting from query
            $propertyConfig = [
                ['name'            , 'setName'         , null      , false],
                ['grade'           , 'setGrade'        , null      , false],
                ['first_ascent'    , 'setFirstAscent'  , null      , true ],
                ['year_first_ascent', 'setYearFirstAscent', 'int'   , true ],
                ['protection'      , 'setProtection'   , 'int'     , true ],
                ['rating'          , 'setRating'       , 'int'     , true ],
                ['topo_id'         , 'setTopoId'       , 'int'     , true ],
                ['climbed'         , 'setClimbed'      , 'bool'    , true ],
                ['rock_quality'    , 'setRockQuality'  , 'bool'    , true ],
            ];

            foreach ($propertyConfig as [$queryKey, $setter, $cast, $allowEmpty]) {
                $value = $request->query->get($queryKey);
                $hasValue = $allowEmpty ? ($value !== null && $value !== '') : ($value);
                if ($hasValue) {
                    if ($cast === 'int') {
                        $value = (int)$value;
                    } elseif ($cast === 'bool') {
                        $value = (bool)$value;
                    }
                    $entity->$setter($value);
                }
            }
        }
        
        return $entity;
    }

    public function persistEntity(\Doctrine\ORM\EntityManagerInterface $entityManager, $entityInstance): void
    {
        // Automatically set area from rock if rock is set and area is not
        if ($entityInstance->getRock() && !$entityInstance->getArea()) {
            $entityInstance->setArea($entityInstance->getRock()->getArea());
        }
        
        // Ensure grade translation happens
        if ($entityInstance->getGrade()) {
            $entityInstance->setGradeNoFromGrade($entityInstance->getGrade());
        }
        
        // Auto-set nr if not set and rock is set
        if ($entityInstance->getRock() && $entityInstance->getNr() === null) {
            static $maxNrCache = [];
            $rockId = $entityInstance->getRock()->getId();
            if (!isset($maxNrCache[$rockId])) {
                $maxNrCache[$rockId] = $entityManager->createQueryBuilder()
                    ->select('MAX(r.nr)')
                    ->from(\App\Entity\Routes::class, 'r')
                    ->where('r.rock = :rock')
                    ->setParameter('rock', $entityInstance->getRock())
                    ->getQuery()
                    ->getSingleScalarResult();
            }
            $entityInstance->setNr(($maxNrCache[$rockId] ?? 0) + 1);
            $maxNrCache[$rockId] = $entityInstance->getNr();
        }
        
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(\Doctrine\ORM\EntityManagerInterface $entityManager, $entityInstance): void
    {
        // Automatically set area from rock if rock is set and area is not
        if ($entityInstance->getRock() && !$entityInstance->getArea()) {
            $entityInstance->setArea($entityInstance->getRock()->getArea());
        }
        
        // Ensure grade translation happens
        if ($entityInstance->getGrade()) {
            $entityInstance->setGradeNoFromGrade($entityInstance->getGrade());
        }
        
        parent::updateEntity($entityManager, $entityInstance);
    }

    public function configureFields(string $pageName): iterable
    {
        yield Field::new('id')
            ->hideonForm()
            ->hideonIndex();
        yield Field::new('nr')
            ->setLabel('Reihenfolge')
            ->setColumns('col-12');
        yield Field::new('name')
            ->setLabel('Name der Route')
            ->setColumns('col-12');
        yield AssociationField::new('area')
            ->setLabel('Gebiet')
            ->setColumns('col-12')
            ->hideOnForm();

        $rockField = AssociationField::new('rock')
            ->setLabel('Fels')
            ->setColumns('col-12')
            ->setCrudController(RockCrudController::class);

        if ($this->routesFormRockChoiceCount() > 10) {
            $rockField
                ->autocomplete()
                ->setFormTypeOption('attr', [
                    'data-admin-route-topo-sync-target' => 'rock',
                ]);
        } else {
            $rockField
                ->renderAsNativeWidget()
                ->setFormTypeOption('attr', [
                    'class' => 'form-select',
                    'data-admin-route-topo-sync-target' => 'rock',
                ])
                ->setQueryBuilder(function (QueryBuilder $qb): QueryBuilder {
                    $this->rockAccessService->restrictRockQueryBuilder($qb, $this->getUser());
                    $qb->orderBy('entity.name', 'ASC');

                    return $qb;
                });
        }

        yield $rockField;
        yield Field::new('topo_id')
            ->setLabel('Topo-Nr.')
            ->hideOnForm()
            ->hideOnIndex();
        yield ChoiceField::new('grade')
            ->setLabel('Schwierigkeitsgrad')
            ->setColumns('col-12')
            ->setChoices($this->getGradeChoices())
            ->setHelp('Wählen Sie den Schwierigkeitsgrad aus. Der numerische Wert wird automatisch gesetzt.');
        $climbedField = BooleanField::new('climbed')
            ->setLabel('Bereits geklettert')
            ->setColumns('col-12')
            ->renderAsSwitch();
        if ($this->rockAccessService->isRockScoped($this->getUser())) {
            $climbedField->hideOnForm()->hideOnIndex()->hideOnDetail();
        }
        yield $climbedField;
        yield Field::new('first_ascent')
            ->setLabel('Erstbegeher')
            ->setColumns('col-12')
            ->hideOnIndex();
        yield Field::new('year_first_ascent')
            ->setLabel('Jahr der Erstbegehung')
            ->setColumns('col-12')
            ->hideOnIndex();
        yield ChoiceField::new('protection')
            ->setLabel('Absicherung')
            ->setColumns('col-12')
            ->hideOnIndex()
            ->setHelp('Wie die Absicherung ist, von gut bis sehr gefährlich!')
            ->setChoices(
                [
                    'gut abgesichert' => '1',
                    'vorsichtig' => '2',
                    'gefährlich' => '3',
                ]
            );
        yield BooleanField::new('rock_quality')
            ->setLabel('Felsqualität zweifelhaft')
            ->hideOnIndex()
            ->setColumns('col-12')
            ->setHelp('Wenn aktiv, wird die Felsqualität als zweifelhaft gekennzeichnet.')
            ->renderAsSwitch();

        yield ChoiceField::new('climbingStyle')
            ->setLabel('Kletterstil')
            ->setChoices([
                'Mehrseillängen Tour' => 'multi-pitch',
                'Trad Tour' => 'trad',
                'Platte' => 'slab',
                'Überhang' => 'overhang',
                'Riss' => 'crack',
                'Dach' => 'roof',
                'Kamin' => 'chimney',
                'Reibungsklettern' => 'friction',
            ])
            ->allowMultipleChoices()
            ->hideOnIndex()
            ->setColumns('col-12')
            ->setHelp('Kletterstil(e) der Route – mehrere möglich.');

        yield Field::new('grade_no')
            ->setLabel('Grade (numerisch)')
            ->setColumns('col-12')
            ->hideOnIndex()
            ->setFormTypeOption('disabled', true)
            ->setHelp('Dieser Wert wird automatisch basierend auf dem Schwierigkeitsgrad berechnet.');
        yield ChoiceField::new('rating')
            ->setLabel('Schönheit')
            ->setColumns('col-12')
            ->hideOnIndex()
            ->setHelp('Schönheit der Route.')
            ->setChoices(
                [
                    'schlecht => Mülltonne' => '-1',
                    'keine Angabe' => '0',
                    'gut => ein Stern' => '1',
                    'super  => zwei Sterne' => '2',
                    'fantastisch   => drei Sterne' => '3',
                ]
            );

    }

    /**
     * Get grade choices for the choice field
     */
    private function getGradeChoices(): array
    {
        return $this->gradeTranslationService->getGradeFormChoices();
    }

    /**
     * Rocks available for this user in the route form (same rules as Fels dropdown / Rock CRUD).
     */
    private function routesFormRockChoiceCount(): int
    {
        $qb = $this->rockRepository->createQueryBuilder('entity');
        $qb->select('COUNT(entity.id)');
        $this->rockAccessService->restrictRockQueryBuilder($qb, $this->getUser());

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
