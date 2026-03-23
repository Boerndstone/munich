<?php

namespace App\Controller\Admin;

use App\Entity\Videos;
use App\Service\RockAccessService;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;

class VideosCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly RockAccessService $rockAccessService,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Videos::class;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $this->rockAccessService->restrictVideosQueryBuilder($qb, $this->getUser());

        return $qb;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions

            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action
                    ->setIcon('fa fa-plus')
                    ->setLabel('Video hinzufügen')
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
                    ->setLabel('Speichern und ein weiteres Video hinzufügen');
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
            ->setPageTitle(Crud::PAGE_INDEX, 'Übersicht der Videos')
            ->setPageTitle(Crud::PAGE_NEW, 'Video hinzufügen')
            ->showEntityActionsInlined()
            ->setPageTitle(Crud::PAGE_EDIT, static function (Videos $videos) {
                return $videos->getVideoRoutes();
            })
            ->setPageTitle(Crud::PAGE_DETAIL, static function (Videos $videos) {
                return $videos->getVideoRoutes();
            })
            ->setFormOptions(['attr' => ['novalidate' => null]]);
    }

    public function configureFields(string $pageName): iterable
    {
        yield Field::new('id')
            ->hideOnIndex()
            ->hideonForm();
        yield AssociationField::new('videoArea')
            ->setLabel('Gebiet')
            ->setColumns('col-12');
        yield AssociationField::new('videoRocks')
            ->setLabel('Fels')
            ->setColumns('col-12');
        yield AssociationField::new('videoRoutes')
            ->setLabel('Tour')
            ->setColumns('col-12');
        yield Field::new('videoLink')
            ->setLabel('Link')
            ->setColumns('col-12');
    }
}
