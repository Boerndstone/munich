<?php

namespace App\Controller\Admin;

use App\Entity\Rock;
use App\Form\Type\JsonFieldType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\RoutesRepository;

class RockCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Rock::class;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return parent::configureFilters($filters)
            ->add(EntityFilter::new('area'));
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions

            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action
                    ->setIcon('fa fa-plus')
                    ->setLabel('Fels hinzufügen')
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
            ->setPageTitle(Crud::PAGE_INDEX, 'Übersicht der Felsen')
            ->setPageTitle(Crud::PAGE_NEW, 'Neuen Fels anlegen')
            ->showEntityActionsInlined()
            ->setPageTitle(Crud::PAGE_EDIT, static function (Rock $rock) {
                return $rock->getName();
            })
            ->setPageTitle(Crud::PAGE_DETAIL, static function (Rock $rock) {
                return $rock->getName();
            });
    }

    public function configureFields(string $pageName): iterable
    {
        yield Field::new('name')
            ->setLabel('Name Fels')
            ->hideOnDetail()
            ->setColumns('col-12');

        yield Field::new('slug')
            ->setLabel('URL des Fels')
            ->hideOnIndex()
            ->hideOnDetail()
            ->setColumns('col-12')
            ->setHelp('Die URL darf keine Leerzeichen oder Umlaute beinhalten!');

        yield AssociationField::new('area')
            ->setLabel('Gebiet')
            ->hideOnDetail()
            ->setColumns('col-12')
            ->setHelp('Zu welchem Gebiet der Fels gehört.');

        yield CollectionField::new('routes')
            ->setLabel('Routen')
            ->onlyOnDetail()
            ->setTemplatePath('admin/field/routes.html.twig')
            ->addCssClass('field-address')
            ->setColumns('col-12');


        yield Field::new('nr')
            ->setLabel('Reihenfolge')
            ->hideOnIndex()
            ->hideOnDetail()
            ->setHelp('Reihenfolge auf der Live Seite in der Überischtstabelle')
            ->setColumns('col-12');

        yield TextareaField::new('description')
            ->setLabel('Beschreibung')
            ->hideOnIndex()
            ->hideOnDetail()
            ->setHelp('Beschreibung zum Fels.')
            ->setColumns('col-12');

        yield TextareaField::new('nature')
            ->setLabel('Naturschutz')
            ->hideOnIndex()
            ->hideOnDetail()
            ->setColumns('col-12')
            ->setHelp('Angaben die für den Naturschutz wichtig sind.');

        yield TextareaField::new('access')
            ->setLabel('Zustieg')
            ->hideOnIndex()
            ->hideOnDetail()
            ->setColumns('col-12')
            ->setHelp('Beschreibung des Zustiegs zum Fels.');

        yield BooleanField::new('train')
            ->setLabel('Anfahrt mit Zug möglich')
            ->hideOnIndex()
            ->hideOnDetail()
            ->setColumns('col-12')
            ->setHelp('Ist der Fels gut mit dem Zug erreicht.');


        yield ChoiceField::new('zone')
            ->setLabel('Zone')
            ->hideOnIndex()
            ->hideOnDetail()
            ->setColumns('col-12')
            ->setChoices([
                'Zone 1' => '1',
                'Zone 2' => '2',
                'Zone 3' => '3',
            ])
            ->setHelp('Befindet sich der Fels in einem zonierten Gebiet? Zone 1 - 3.');

        yield ChoiceField::new('banned')
            ->setLabel('Jahreszeitliche Sperrung')
            ->hideOnIndex()
            ->hideOnDetail()
            ->setColumns('col-12')
            ->setChoices([
                'keine Angabe' => '0',
                'Sperrungen bis 30.06.' => '1',
                'Sperrungen bis 31.07.' => '2',
            ])
            ->setHelp('Gibt es eine jahreszeitliche Sperrung.');

        yield NumberField::new('height')
            ->setLabel('Höhe')
            ->setTemplatePath('admin/field/height.html.twig')
            ->hideOnDetail()
            ->setColumns('col-12');

        yield ChoiceField::new('online')
            ->setLabel('Status')
            ->renderAsNativeWidget()
            ->setChoices([
                'online' => '1',
                'offline' => '0',
            ])
            ->setTemplatePath('admin/field/status.html.twig')
            ->hideOnDetail()
            ->setColumns('col-12');

        yield Field::new('orientation')
            ->setLabel('Ausrichtung')
            ->hideOnIndex()
            ->hideOnDetail()
            ->setColumns('col-12');

        yield ChoiceField::new('season')
            ->setLabel('Beste Jahreszeit')
            ->hideOnIndex()
            ->hideOnDetail()
            ->setChoices([
                'Sommer' => 'Sommer',
                'Sommer Herbst' => 'Sommer Herbst',
                'Frühling Sommer Herbst' => 'Frühling Sommer Herbst',
            ])
            ->setColumns('col-12');

        yield BooleanField::new('child_friendly')
            ->setLabel('Kinderfreundlich')
            ->hideOnIndex()
            ->hideOnDetail()
            ->setColumns('col-12')
            ->setHelp('Wie gut ist der Fels mit Kindern geeignet.');

        yield ChoiceField::new('sunny')
            ->setLabel('Sonnig')
            ->renderAsNativeWidget()
            ->setChoices([
                'keine Sonne' => '1',
                'teils Sonne' => '2',
                'sonnig' => '3',
            ])
            ->hideOnIndex()
            ->hideOnDetail()
            ->setColumns('col-12');

        yield ChoiceField::new('rain')
            ->setLabel('Regensicher')
            ->renderAsNativeWidget()
            ->setChoices([
                'regensicher' => '1',
                'kaum regensicher' => '2',
                'nicht regensicher' => '3',
            ])
            ->hideOnIndex()
            ->hideOnDetail()
            ->setColumns('col-12');

        yield Field::new('image')
            ->setLabel('Bilder')
            ->hideOnIndex()
            ->hideOnDetail()
            ->setColumns('col-12');

        yield Field::new('header_image')
            ->setLabel('Header Bild')
            ->hideOnIndex()
            ->hideOnDetail()
            ->setColumns('col-12');

        yield NumberField::new('lat')
            ->setLabel('Breitengrad')
            ->setNumDecimals(6)
            ->hideOnIndex()
            ->hideOnDetail()
            ->setColumns('col-12');

        yield NumberField::new('lng')
            ->setLabel('Längengrad')
            ->setNumDecimals(6)
            ->hideOnIndex()
            ->hideOnDetail()
            ->setColumns('col-12');

        yield CodeEditorField::new('path_coordinates')
            ->setFormType(JsonFieldType::class)
            ->setLanguage('js')
            ->setLabel('Pfad Koordinaten')
            ->hideOnIndex()
            ->hideOnDetail()
            ->setColumns('col-12');

        yield NumberField::new('zoom')
            ->setLabel('Zoomfaktor für die Karte')
            ->hideOnIndex()
            ->hideOnDetail()
            ->setColumns('col-12');
    }

    #[Route('/admin/rock/{rockId}/routes/reorder', name: 'admin_rock_routes_reorder', methods: ['POST'])]
    public function reorderRoutes(int $rockId, Request $request, EntityManagerInterface $entityManager, RoutesRepository $routesRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['routeIds']) || !is_array($data['routeIds'])) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid data'], 400);
        }

        $routeIds = $data['routeIds'];
        
        if (empty($routeIds)) {
            return new JsonResponse(['success' => false, 'message' => 'No route IDs provided'], 400);
        }

        $rock = $entityManager->getRepository(Rock::class)->find($rockId);
        
        if (!$rock) {
            return new JsonResponse(['success' => false, 'message' => 'Rock not found'], 404);
        }

        // Fetch all routes in a single query to avoid N+1 problem
        $routes = $routesRepository->findBy([
            'id' => $routeIds,
            'rock' => $rock
        ]);

        if (count($routes) !== count($routeIds)) {
            return new JsonResponse(['success' => false, 'message' => 'Some routes were not found or do not belong to this rock'], 400);
        }

        // Create a map of route ID to route object for O(1) lookup
        $routesMap = [];
        foreach ($routes as $route) {
            $routesMap[$route->getId()] = $route;
        }

        // Update the nr field for each route based on the new order
        // The display will sort by topoId first, then by nr
        foreach ($routeIds as $index => $routeId) {
            if (isset($routesMap[$routeId])) {
                $routesMap[$routeId]->setNr($index + 1);
                $entityManager->persist($routesMap[$routeId]);
            }
        }

        $entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Routes reordered successfully']);
    }
}
