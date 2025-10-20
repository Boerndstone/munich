<?php

namespace App\Controller\Admin;

use App\Entity\Rock;
use App\Entity\RockTranslation;
use App\Form\Type\JsonFieldType;
use App\Repository\RockTranslationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
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

class RockCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly RockTranslationRepository $rockTranslationRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
    ) {
    }

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
        $currentRequest = $this->requestStack->getCurrentRequest();
        $adminContext = $currentRequest?->attributes->get('ea');
        $currentEntity = $adminContext?->getEntity()?->getInstance();

        $descriptionDe = null;
        $descriptionEn = null;
        $natureDe = null;
        $natureEn = null;
        $accessDe = null;
        $accessEn = null;
        $flowersDe = null;
        $flowersEn = null;

        if ($currentEntity instanceof Rock) {
            $descriptionDe = $this->getTranslationFieldValue($currentEntity, 'de', 'description');
            $descriptionEn = $this->getTranslationFieldValue($currentEntity, 'en', 'description');
            $natureDe = $this->getTranslationFieldValue($currentEntity, 'de', 'nature');
            $natureEn = $this->getTranslationFieldValue($currentEntity, 'en', 'nature');
            $accessDe = $this->getTranslationFieldValue($currentEntity, 'de', 'access');
            $accessEn = $this->getTranslationFieldValue($currentEntity, 'en', 'access');
            $flowersDe = $this->getTranslationFieldValue($currentEntity, 'de', 'flowers');
            $flowersEn = $this->getTranslationFieldValue($currentEntity, 'en', 'flowers');
        }

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
            ->setColumns('col-12 col-md-4');


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

        // Translated fields (DE/EN) - saved into RockTranslation
        yield TextareaField::new('description_de')
            ->setLabel('Beschreibung (DE)')
            ->onlyOnForms()
            ->setFormTypeOption('mapped', false)
            ->setFormTypeOption('data', $descriptionDe)
            ->setColumns('col-12');

        yield TextareaField::new('description_en')
            ->setLabel('Beschreibung (EN)')
            ->onlyOnForms()
            ->setFormTypeOption('mapped', false)
            ->setFormTypeOption('data', $descriptionEn)
            ->setColumns('col-12');

        yield TextareaField::new('nature')
            ->setLabel('Naturschutz')
            ->hideOnIndex()
            ->hideOnDetail()
            ->setColumns('col-12')
            ->setHelp('Angaben die für den Naturschutz wichtig sind.');

        yield TextareaField::new('nature_de')
            ->setLabel('Naturschutz (DE)')
            ->onlyOnForms()
            ->setFormTypeOption('mapped', false)
            ->setFormTypeOption('data', $natureDe)
            ->setColumns('col-12');

        yield TextareaField::new('nature_en')
            ->setLabel('Naturschutz (EN)')
            ->onlyOnForms()
            ->setFormTypeOption('mapped', false)
            ->setFormTypeOption('data', $natureEn)
            ->setColumns('col-12');

        yield TextareaField::new('access')
            ->setLabel('Zustieg')
            ->hideOnIndex()
            ->hideOnDetail()
            ->setColumns('col-12')
            ->setHelp('Beschreibung des Zustiegs zum Fels.');

        yield TextareaField::new('access_de')
            ->setLabel('Zustieg (DE)')
            ->onlyOnForms()
            ->setFormTypeOption('mapped', false)
            ->setFormTypeOption('data', $accessDe)
            ->setColumns('col-12');

        yield TextareaField::new('access_en')
            ->setLabel('Zustieg (EN)')
            ->onlyOnForms()
            ->setFormTypeOption('mapped', false)
            ->setFormTypeOption('data', $accessEn)
            ->setColumns('col-12');

        // Flowers (translated)
        yield TextareaField::new('flowers_de')
            ->setLabel('Flora & Fauna (DE)')
            ->onlyOnForms()
            ->setFormTypeOption('mapped', false)
            ->setFormTypeOption('data', $flowersDe)
            ->setColumns('col-12');

        yield TextareaField::new('flowers_en')
            ->setLabel('Flora & Fauna (EN)')
            ->onlyOnForms()
            ->setFormTypeOption('mapped', false)
            ->setFormTypeOption('data', $flowersEn)
            ->setColumns('col-12');

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


    private function getTranslationFieldValue(Rock $rock, string $locale, string $field): ?string
    {
        $translation = $this->rockTranslationRepository->findOneBy([
            'rock' => $rock,
            'locale' => $locale,
        ]);

        if (!$translation instanceof RockTranslation) {
            return null;
        }

        return match ($field) {
            'description' => $translation->getDescription(),
            'nature' => $translation->getNature(),
            'access' => $translation->getAccess(),
            'flowers' => $translation->getFlowers(),
            default => null,
        };
    }

    private function upsertTranslation(Rock $rock, string $locale, array $data): void
    {
        $translation = $this->rockTranslationRepository->findOneBy([
            'rock' => $rock,
            'locale' => $locale,
        ]) ?? (new RockTranslation())->setRock($rock)->setLocale($locale);

        if (array_key_exists('description', $data)) {
            $translation->setDescription((string) $data['description']);
        }
        if (array_key_exists('nature', $data)) {
            $translation->setNature($data['nature'] !== null ? (string) $data['nature'] : null);
        }
        if (array_key_exists('access', $data)) {
            $translation->setAccess($data['access'] !== null ? (string) $data['access'] : null);
        }
        if (array_key_exists('flowers', $data)) {
            $translation->setFlowers($data['flowers'] !== null ? (string) $data['flowers'] : null);
        }

        $this->entityManager->persist($translation);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        parent::persistEntity($entityManager, $entityInstance);

        if (!$entityInstance instanceof Rock) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        $formData = $request?->request->all()['Rock'] ?? [];

        $this->upsertTranslation($entityInstance, 'de', [
            'description' => $formData['description_de'] ?? null,
            'nature' => $formData['nature_de'] ?? null,
            'access' => $formData['access_de'] ?? null,
            'flowers' => $formData['flowers_de'] ?? null,
        ]);

        $this->upsertTranslation($entityInstance, 'en', [
            'description' => $formData['description_en'] ?? null,
            'nature' => $formData['nature_en'] ?? null,
            'access' => $formData['access_en'] ?? null,
            'flowers' => $formData['flowers_en'] ?? null,
        ]);

        $this->entityManager->flush();
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        parent::updateEntity($entityManager, $entityInstance);

        if (!$entityInstance instanceof Rock) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        $formData = $request?->request->all()['Rock'] ?? [];

        $this->upsertTranslation($entityInstance, 'de', [
            'description' => $formData['description_de'] ?? null,
            'nature' => $formData['nature_de'] ?? null,
            'access' => $formData['access_de'] ?? null,
            'flowers' => $formData['flowers_de'] ?? null,
        ]);

        $this->upsertTranslation($entityInstance, 'en', [
            'description' => $formData['description_en'] ?? null,
            'nature' => $formData['nature_en'] ?? null,
            'access' => $formData['access_en'] ?? null,
            'flowers' => $formData['flowers_en'] ?? null,
        ]);

        $this->entityManager->flush();
    }
}