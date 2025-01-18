<?php

namespace App\Controller\Admin;

use App\Entity\Topo;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

class TopoCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Topo::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions

            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action
                    ->setIcon('fa fa-plus')
                    ->setLabel('Topo hinzufügen')
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
                    ->setLabel('Speichern und ein weiteres Topo hinzufügen');
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
            ->setPageTitle(Crud::PAGE_INDEX, 'Übersicht der Topos')
            ->setPageTitle(Crud::PAGE_NEW, 'Topo hinzufügen')
            ->showEntityActionsInlined()
            ->setPageTitle(Crud::PAGE_EDIT, static function (Topo $topo) {
                return $topo->getName();
            })
            ->setPageTitle(Crud::PAGE_DETAIL, static function (Topo $topo) {
                return $topo->getName();
            })
            ->setFormOptions(['attr' => ['novalidate' => null]]);
    }


    public function configureFields(string $pageName): iterable
    {
        yield Field::new('id')
            ->hideonForm();
        yield Field::new('name')
            ->setLabel('Name')
            ->setColumns('col-12');
        yield AssociationField::new('rocks')
            ->setLabel('Rock Id')
            ->setColumns('col-12');
        /*yield ImageField::new('image')
            ->setBasePath('uploads/topo')
            ->setUploadDir('public/uploads/topo')
            ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
            ->onlyOnForms()
        ;*/
        yield Field::new('image')
            ->setLabel('Bild')
            ->hideOnIndex()
            ->setColumns('col-12');
        yield Field::new('withSector')
            ->setLabel('Mit Sektoren')
            ->setColumns('col-12')
            ->setTemplatePath('admin/field/votes.html.twig');
        yield TextareaField::new('svg')
            ->setLabel('Topo SVG')
            ->hideOnIndex()
            ->hideOnDetail()
            ->setColumns('col-12');
        yield Field::new('pathCollection')
            ->setLabel('Tourenpfade')
            ->hideOnIndex()
            ->hideOnDetail()
            ->setColumns('col-12')
            ->setTemplatePath('admin/field/path_collection.html.twig'); // Use custom template
        // Hier macht ein Association Field für die Topo Id bei der Route Sinn!
        // In Entiy anlegen.
        yield Field::new('number')
            ->setLabel('Nummer Sektor')
            ->setColumns('col-12')
            ->setHelp('Die Nummer die zur Topo Id bei der Route korrespondiert!');
    }
}
