<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\TopoPathSuggestion;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
final class TopoPathSuggestionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TopoPathSuggestion::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Tourenpfad-Vorschlag')
            ->setEntityLabelInPlural('Tourenpfad-Vorschläge')
            ->setPageTitle(Crud::PAGE_INDEX, 'Tourenpfad-Vorschläge (Mithelfen)')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setIcon('fa fa-trash')->setLabel(false);
            });
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield AssociationField::new('rock')->setLabel('Fels');
        yield IntegerField::new('topoNumber')->setLabel('Topo-Nr.');
        yield TextField::new('uploaderName')->setLabel('Name');
        yield TextField::new('uploaderEmail')->setLabel('E-Mail');
        yield TextField::new('status')->setLabel('Status')->onlyOnIndex();
        yield DateTimeField::new('createdAt')->setLabel('Eingang')->onlyOnIndex();
        yield TextareaField::new('comment')->setLabel('Kommentar')->hideOnIndex();
        yield TextareaField::new('pathCollection')->setLabel('Tourenpfade (PHP)')->setNumOfRows(18);
        yield TextField::new('referenceImageBasename')->setLabel('Referenzbild (Basisname)')->hideOnIndex();
    }
}
