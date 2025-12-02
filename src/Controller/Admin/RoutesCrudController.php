<?php

namespace App\Controller\Admin;

use App\Entity\Routes;
use App\Service\GradeTranslationService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class RoutesCrudController extends AbstractCrudController
{
    public function __construct(
        private GradeTranslationService $gradeTranslationService
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Routes::class;
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

            ->update(Crud::PAGE_DETAIL, Action::EDIT, function (Action $action) {
                return $action
                    ->setLabel('Bearbeiten')
                    ->setCssClass('btn btn-success');
            })

            ->update(Crud::PAGE_DETAIL, Action::INDEX, function (Action $action) {
                return $action
                    ->setLabel('Zurück zur Liste');
            })
            ->update(Crud::PAGE_DETAIL, Action::DELETE, function (Action $action) {
                return $action
                    ->setLabel('Löschen');
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
            ->setPageTitle(Crud::PAGE_DETAIL, static function (Routes $routes) {
                return $routes->getName();
            })
            ->setFormOptions(['attr' => ['novalidate' => null]]);
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
                ['description'     , 'setDescription'  , null      , true ],
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
        yield AssociationField::new('rock')
            ->setLabel('Fels')
            ->setColumns('col-12');
        yield ChoiceField::new('grade')
            ->setLabel('Schwierigkeitsgrad')
            ->setColumns('col-12')
            ->setChoices($this->getGradeChoices())
            ->setHelp('Wählen Sie den Schwierigkeitsgrad aus. Der numerische Wert wird automatisch gesetzt.');
        yield Field::new('climbed')
            ->setLabel('Bereits geklettert')
            ->setColumns('col-12')
            ->setTemplatePath('admin/field/votes.html.twig');
        yield Field::new('first_ascent')
            ->setLabel('Erstbegeher')
            ->setColumns('col-12')
            ->hideOnIndex();
        yield AssociationField::new('relatesToRoute')
            ->setLabel('Erstbegeher Neu')
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
            ->setLabel('Felsqualität')
            ->hideOnIndex()
            ->setColumns('col-12')
            ->setHelp('Wenn aktiv, dann wird die Felsqualität zweifelhaft!')
            ->setTemplatePath('admin/field/votes.html.twig');

        yield Field::new('description')
            ->setLabel('Beschreibung')
            ->setColumns('col-12')
            ->hideOnIndex();
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
        yield Field::new('topo_id')
            ->setLabel('Topo ID')
            ->setColumns('col-12')
            ->hideOnIndex();
        
    }

    /**
     * Get grade choices for the choice field
     */
    private function getGradeChoices(): array
    {
        $grades = $this->gradeTranslationService->getAvailableGrades();
        $choices = [];
        
        foreach ($grades as $grade) {
            $choices[$grade] = $grade;
        }
        
        return $choices;
    }
}
