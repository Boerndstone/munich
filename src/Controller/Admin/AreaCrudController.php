<?php

namespace App\Controller\Admin;

use App\Entity\Area;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use App\Form\Type\JsonFieldType;

class AreaCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Area::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions

            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action
                    ->setIcon('fa fa-plus')
                    ->setLabel('Gebiet hinzufügen')
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
            ->setPageTitle(Crud::PAGE_INDEX, 'Übersicht der Gebiete')
            ->setPageTitle(Crud::PAGE_NEW, 'Gebiete anlegen')
            ->showEntityActionsInlined()
            ->setPageTitle(Crud::PAGE_EDIT, static function (Area $area) {
                return $area->getName();
            })
            ->setFormOptions(['attr' => ['novalidate' => null]]);
    }

    public function configureFields(string $pageName): iterable
    {
        yield Field::new('name')
            ->setLabel('Name des Gebiet\'s')
            ->setHelp('Der Name des Gebiet\'s ist ein Pflichtfeld!')
            ->setColumns('col-12');

        yield Field::new('slug')
            ->setLabel('URL des Gebiet\'s')
            ->hideOnIndex()
            ->setFormTypeOption(
                'disabled',
                $pageName !== Crud::PAGE_NEW
            )
            ->setHelp('Die URL darf keine Leerzeichen oder Umlaute beinhalten!')
            ->setColumns('col-12');

        yield ChoiceField::new('orientation')
            ->setLabel('Lage')
            ->renderAsNativeWidget()
            ->setChoices([
                'Im Norden Münchens' => 'north',
                'Im Süden Münchens' => 'south',
                'Im Westen Münchens' => 'west',
                'Im Osten Münchens' => 'east',
            ])
            ->hideOnIndex()
            ->setHelp('Die Lage beschreibt wo sich das Gebiet befindet.')
            ->setColumns('col-12');

        yield Field::new('sequence')
            ->setLabel('Reihenfolge')
            ->setColumns('col-12');

        yield ChoiceField::new('online')
            ->setLabel('Status:')
            ->renderAsNativeWidget()
            ->setChoices([
                'Online' => '1',
                'Offline' => '0',
            ])
            ->setTemplatePath('admin/field/status.html.twig')
            ->setHelp('Ob das Gebiet Online/Offline ist.')
            ->setColumns('col-12');

        yield Field::new('image')
            ->setLabel('Bild')
            ->hideOnIndex()
            ->setColumns('col-12');

        yield Field::new('header_image')
            ->setLabel('Header Bild')
            ->hideOnIndex()
            ->setColumns('col-12');

        yield Field::new('lat')
            ->setLabel('Breitengrad')
            ->hideOnIndex()
            ->setColumns('col-12');

        yield Field::new('lng')
            ->setLabel('Längengrad')
            ->hideOnIndex()
            ->setColumns('col-12');

        yield Field::new('zoom')
            ->setLabel('Zoomstufe')
            ->hideOnIndex()
            ->setColumns('col-12')
            ->setHelp('Zoom Stufe relevant für Kartenansicht auf der Gebietsseite.');

        yield Field::new('rock_responsibility')
            ->setLabel('Verantwortlicher Felsbetreuer')
            ->hideOnIndex()
            ->setColumns('col-12')
            ->setHelp('Wenn es einen verantwortlichen Felsbetreuer gibt, dann wird dieser bei der Gebietsübersicht angezeigt.');

        yield CodeEditorField::new('railway_station')
            ->setFormType(JsonFieldType::class)
            ->setLanguage('js')
            ->setLabel('Bahnhof')
            ->hideOnIndex()
            ->hideOnDetail()
            ->setColumns('col-12')
            ->setHelp('Längen- und Breitengrad werden als Json Objekt gespeichert [lat, lng].');
    }
}
