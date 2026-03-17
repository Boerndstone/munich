<?php

namespace App\Controller\Admin;

use App\Entity\Topo;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class TopoCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Topo::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::new('editPaths', 'Tourenpfade bearbeiten', 'fa fa-pencil-square-o')
                ->linkToRoute('admin_topo_edit_paths', fn (Topo $topo) => ['id' => $topo->getId()])
                ->setCssClass('btn btn-secondary'))
            ->add(Crud::PAGE_DETAIL, Action::new('editPaths', 'Tourenpfade bearbeiten', 'fa fa-pencil-square-o')
                ->linkToRoute('admin_topo_edit_paths', fn (Topo $topo) => ['id' => $topo->getId()])
                ->setCssClass('btn btn-secondary'))
            ->add(Crud::PAGE_EDIT, Action::new('editPaths', 'Tourenpfade bearbeiten', 'fa fa-pencil-square-o')
                ->linkToRoute('admin_topo_edit_paths', fn (Topo $topo) => ['id' => $topo->getId()])
                ->setCssClass('btn btn-secondary'))

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
            ->hideOnForm();
        yield Field::new('name')
            ->setLabel('Name')
            ->setColumns('col-12');
        yield AssociationField::new('rocks')
            ->setLabel('Fels / Rock')
            ->setColumns('col-12');

        yield TextField::new('image')
            ->setLabel('Bild (Dateiname)')
            ->hideOnIndex()
            ->setColumns('col-12')
            ->setHelp('Dateiname ohne Erweiterung (z.B. burgsteinSuedwand). Bild muss unter build/images/topos/ als .webp liegen. Zusammen mit den Tourenpfaden wird daraus das Topo auf der Fels-Seite gerendert.');

        yield NumberField::new('number')
            ->setLabel('Nummer Sektor')
            ->setColumns('col-12')
            ->setHelp('Entspricht der Topo-Id bei den Touren (Route → Topo ID).');

        yield Field::new('withSector')
            ->setLabel('Mit Sektoren')
            ->setColumns('col-12')
            ->setTemplatePath('admin/field/votes.html.twig');

        yield TextareaField::new('pathCollection')
            ->setLabel('Tourenpfade (PHP oder JSON)')
            ->hideOnIndex()
            ->setColumns('col-12')
            ->setNumOfRows(14)
            ->setHelp('PHP-Array-Literal vom Topo Path Helper einfügen – oder ein JSON-Array. Wird mit dem Bild oben als Overlay gerendert (ViewBox immer 0 0 1024 820).')
            ->setTemplatePath('admin/field/path_collection.html.twig');
    }
}
