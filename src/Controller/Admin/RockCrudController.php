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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\RoutesRepository;
use App\Entity\Routes;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use App\Controller\Admin\DashboardController;

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
            })
            ->add(Crud::PAGE_DETAIL, Action::new('importRoutes', 'Routen importieren')
                ->linkToRoute('admin_rock_import_routes', function (Rock $rock): array {
                    return ['rockId' => $rock->getId()];
                })
                ->setIcon('fa fa-upload')
                ->setCssClass('btn btn-primary'));
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
            ->setLabel(false)
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

    #[Route('/admin/rock/{rockId}/routes/import', name: 'admin_rock_import_routes', methods: ['GET', 'POST'])]
    public function importRoutes(int $rockId, Request $request, EntityManagerInterface $entityManager, AdminUrlGenerator $adminUrlGenerator): Response
    {
        $rock = $entityManager->getRepository(Rock::class)->find($rockId);
        
        if (!$rock) {
            $this->addFlash('error', 'Fels nicht gefunden.');
            return $this->redirect($adminUrlGenerator
                ->setDashboard(DashboardController::class)
                ->setController(RockCrudController::class)
                ->setAction('index')
                ->generateUrl());
        }

        if ($request->isMethod('POST')) {
            $routesData = $request->request->get('routes_data', '');
            
            if (empty($routesData)) {
                $this->addFlash('error', 'Keine Daten zum Importieren gefunden.');
                return $this->render('admin/rock/import_routes.html.twig', [
                    'rock' => $rock,
                ]);
            }

            $imported = 0;
            $errors = [];
            $lines = explode("\n", $routesData);
            
            // Get the current max nr for this rock
            $maxNr = $entityManager->createQueryBuilder()
                ->select('MAX(r.nr)')
                ->from(Routes::class, 'r')
                ->where('r.rock = :rock')
                ->setParameter('rock', $rock)
                ->getQuery()
                ->getSingleScalarResult() ?? 0;

            // Pattern to match grade at the start of a line (e.g., "8+/9-", "7-", "NN", "5+", etc.)
            $gradePattern = '/^([0-9]+[+-]?(\/[0-9]+[+-]?)?|NN|NN\d*)/i';

            foreach ($lines as $lineNumber => $line) {
                $line = trim($line);
                
                // Skip empty lines
                if (empty($line)) {
                    continue;
                }

                // Skip lines that are clearly not route entries
                if (preg_match('/^(Erstbegehung|Sport|[\s]*$)/i', $line)) {
                    continue;
                }

                // Check if line starts with a grade pattern
                if (!preg_match($gradePattern, $line)) {
                    // This might be a continuation of the previous route name, skip it
                    continue;
                }

                // Try to split by tab first, then by multiple spaces
                $parts = preg_split('/\t+/', $line);
                if (count($parts) < 2) {
                    // Try splitting by multiple spaces (2 or more)
                    $parts = preg_split('/\s{2,}/', $line);
                }
                
                // If still no parts, try splitting by single space but only if we have a clear grade pattern
                if (count($parts) < 2) {
                    // Try to match grade at start and take everything after as route name
                    if (preg_match($gradePattern, $line, $matches)) {
                        $grade = $matches[0];
                        $routeName = trim(substr($line, strlen($grade)));
                        if (!empty($routeName)) {
                            $parts = [$grade, $routeName];
                        }
                    }
                }
                
                if (count($parts) < 2) {
                    $errors[] = "Zeile " . ($lineNumber + 1) . ": Ungültiges Format. Erwartet: 'Grade\tRoute Name' oder 'Grade  Route Name'";
                    continue;
                }

                $grade = trim($parts[0]);
                $routeName = trim($parts[1]);

                // Skip if grade is empty or route name is empty
                if (empty($grade) || empty($routeName)) {
                    $errors[] = "Zeile " . ($lineNumber + 1) . ": Grade oder Routenname fehlt";
                    continue;
                }

                // Skip "NN" entries without a route name (or handle them differently)
                if (strtoupper($grade) === 'NN' && (empty($routeName) || strtoupper($routeName) === 'NN')) {
                    continue;
                }

                // Remove stars (★★★, ★★, ★, etc.) from route name
                $routeName = preg_replace('/[★☆]+[\s]*/', '', $routeName);
                $routeName = trim($routeName);

                // Look ahead for "Erstbegehung" line (check next 5 lines)
                $firstAscent = null;
                $yearFirstAscent = null;
                for ($i = $lineNumber + 1; $i < min($lineNumber + 6, count($lines)); $i++) {
                    $nextLine = trim($lines[$i]);
                    if (preg_match('/^Erstbegehung:/i', $nextLine)) {
                        // Parse "Erstbegehung: K. Tonkovic & R. Müller, 1999"
                        // Extract the name after "&" and the year
                        if (preg_match('/Erstbegehung:\s*[^&]*&\s*([^,]+),\s*(\d{4})/i', $nextLine, $matches)) {
                            $firstAscent = trim($matches[1]);
                            $yearFirstAscent = (int)$matches[2];
                        } elseif (preg_match('/Erstbegehung:\s*[^&]*&\s*([^,]+)/i', $nextLine, $matches)) {
                            // If no year, just get the name after &
                            $firstAscent = trim($matches[1]);
                        }
                        break; // Found Erstbegehung, stop looking
                    }
                    // If we hit another route line (starts with grade), stop looking
                    if (!empty($nextLine) && preg_match($gradePattern, $nextLine)) {
                        break;
                    }
                }

                // Create new route
                $route = new Routes();
                $route->setName($routeName);
                $route->setGrade($grade);
                $route->setRock($rock);
                
                // Set area from rock if available
                if ($rock->getArea()) {
                    $route->setArea($rock->getArea());
                }

                // Set first ascent information if found
                if ($firstAscent) {
                    $route->setFirstAscent($firstAscent);
                }
                if ($yearFirstAscent) {
                    $route->setYearFirstAscent($yearFirstAscent);
                }

                // Set nr (increment from max)
                $maxNr++;
                $route->setNr($maxNr);

                try {
                    $entityManager->persist($route);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Zeile " . ($lineNumber + 1) . ": Fehler beim Speichern - " . $e->getMessage();
                }
            }

            if ($imported > 0) {
                $entityManager->flush();
                $this->addFlash('success', "$imported Route(n) erfolgreich importiert.");
            }

            if (!empty($errors)) {
                $this->addFlash('warning', count($errors) . " Fehler beim Import:");
                foreach ($errors as $error) {
                    $this->addFlash('warning', $error);
                }
            }

            if ($imported > 0) {
                return $this->redirect($adminUrlGenerator
                    ->setDashboard(DashboardController::class)
                    ->setController(RockCrudController::class)
                    ->setAction('detail')
                    ->setEntityId($rockId)
                    ->generateUrl());
            }
        }

        $detailUrl = $adminUrlGenerator
            ->setDashboard(DashboardController::class)
            ->setController(RockCrudController::class)
            ->setAction('detail')
            ->setEntityId($rockId)
            ->generateUrl();

        return $this->render('admin/rock/import_routes.html.twig', [
            'rock' => $rock,
            'detailUrl' => $detailUrl,
        ]);
    }
}
