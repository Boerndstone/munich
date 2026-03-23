<?php

namespace App\Controller\Admin;

use App\Entity\Comment;
use App\Service\RockAccessService;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class CommentCrudController extends AbstractCrudController
{
    public function __construct(
        private Security $security,
        private RockAccessService $rockAccessService,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Comment::class;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $this->rockAccessService->restrictCommentsQueryBuilder($qb, $this->getUser());

        return $qb;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions

            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action
                    ->setIcon('fa fa-plus')
                    ->setLabel('Kommentar hinzufügen')
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
            ->setPageTitle(Crud::PAGE_INDEX, 'Kommentare zu Routen')
            ->setPageTitle(Crud::PAGE_NEW, 'Kommentare zur Route hinzufügen')
            ->showEntityActionsInlined()
            ->setPageTitle(Crud::PAGE_EDIT, static function (Comment $comment) {
                return $comment->getComment();
            })
            ->setPageTitle(Crud::PAGE_DETAIL, static function (Comment $comment) {
                return $comment->getComment();
            })
            ->setFormOptions(['attr' => ['novalidate' => null]]);
    }


    public function configureFields(string $pageName): iterable
    {
        /** @var UserInterface|null $user */
        $user = $this->security->getUser();

        yield TextField::new('user')
            ->setLabel('User')
            ->setFormattedValue($user instanceof UserInterface ? $user->getUserIdentifier() : '')
            ->setDisabled()
            ->setColumns('col-12')
            ->hideOnIndex();
        yield AssociationField::new('route')
            ->setLabel('Route')
            ->setColumns('col-12')
            ->setQueryBuilder(function (QueryBuilder $qb) {
                $this->rockAccessService->restrictRoutesQueryBuilder($qb, $this->getUser());

                return $qb;
            });
        yield TextEditorField::new('comment')
            ->setLabel('Kommentar zur Route')
            ->setColumns('col-12')
            ->hideOnIndex();
    }

    public function createEntity(string $entityFqcn): Comment
    {
        $comment = new Comment();
        $comment->setUser($this->getUser()); // set the user

        return $comment;
    }
}
