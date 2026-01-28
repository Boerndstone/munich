<?php

namespace App\Controller\Admin;

use App\Entity\Photos;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PhotosCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Photos::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions

            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action
                    ->setIcon('fa fa-plus')
                    ->setLabel('Foto hinzufügen')
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
                    ->setLabel('Speichern und ein weiteres Foto hinzufügen');
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
            ->add(Crud::PAGE_INDEX, Action::new('approve', 'Freigeben')
                ->linkToRoute('admin_photo_approve', function (Photos $photo) {
                    return ['id' => $photo->getId()];
                })
                ->setIcon('fa fa-check')
                ->setCssClass('btn btn-success btn-sm')
                ->displayIf(fn (Photos $photo) => $photo->isPending()))
            ->add(Crud::PAGE_INDEX, Action::new('reject', 'Ablehnen')
                ->linkToRoute('admin_photo_reject', function (Photos $photo) {
                    return ['id' => $photo->getId()];
                })
                ->setIcon('fa fa-times')
                ->setCssClass('btn btn-danger btn-sm')
                ->displayIf(fn (Photos $photo) => $photo->isPending()))
            ->add(Crud::PAGE_DETAIL, Action::new('approve', 'Freigeben')
                ->linkToRoute('admin_photo_approve', function (Photos $photo) {
                    return ['id' => $photo->getId()];
                })
                ->setIcon('fa fa-check')
                ->setCssClass('btn btn-success')
                ->displayIf(fn (Photos $photo) => $photo->isPending()))
            ->add(Crud::PAGE_DETAIL, Action::new('reject', 'Ablehnen')
                ->linkToRoute('admin_photo_reject', function (Photos $photo) {
                    return ['id' => $photo->getId()];
                })
                ->setIcon('fa fa-times')
                ->setCssClass('btn btn-danger')
                ->displayIf(fn (Photos $photo) => $photo->isPending()));
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setPageTitle(Crud::PAGE_INDEX, 'Übersicht der Fotos')
            ->setPageTitle(Crud::PAGE_NEW, 'Foto hinzufügen')
            ->showEntityActionsInlined()
            ->setPageTitle(Crud::PAGE_EDIT, static function (Photos $photos) {
                return $photos->getBelongsToRoute() ? $photos->getBelongsToRoute()->getName() : 'Foto bearbeiten';
            })
            ->setPageTitle(Crud::PAGE_DETAIL, static function (Photos $photos) {
                return $photos->getBelongsToRoute() ? $photos->getBelongsToRoute()->getName() : 'Foto Details';
            })
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setFormOptions(['attr' => ['novalidate' => null]]);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status')
                ->setChoices([
                    'Pending' => 'pending',
                    'Approved' => 'approved',
                    'Rejected' => 'rejected',
                ]))
            ->add(EntityFilter::new('belongsToRock'))
            ->add(EntityFilter::new('belongsToRoute'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield Field::new('id')
            ->hideOnIndex()
            ->hideOnForm();
        
        yield ChoiceField::new('status')
            ->setLabel('Status')
            ->setChoices([
                'Pending' => 'pending',
                'Approved' => 'approved',
                'Rejected' => 'rejected',
            ])
            ->renderAsBadges([
                'pending' => 'warning',
                'approved' => 'success',
                'rejected' => 'danger',
            ])
            ->setColumns('col-12');
        
        yield AssociationField::new('belongsToArea')
            ->setLabel('Gebiet')
            ->setColumns('col-12');
        yield AssociationField::new('belongsToRock')
            ->setLabel('Fels')
            ->setColumns('col-12');
        yield AssociationField::new('belongsToRoute')
            ->setLabel('Tour')
            ->setColumns('col-12');
        
        yield ImageField::new('name')
            ->setBasePath('uploads/galerie')
            ->setUploadDir('public/uploads/galerie')
            ->setLabel('Bild')
            ->setColumns('col-12');

        yield Field::new('description')
            ->setLabel('Beschreibung')
            ->hideOnIndex()
            ->setColumns('col-12');
        yield Field::new('photgrapher')
            ->setLabel('Fotograph')
            ->setColumns('col-12');
        
        yield TextField::new('uploaderName')
            ->setLabel('Hochgeladen von (Name)')
            ->hideOnIndex()
            ->setColumns('col-12');
        
        yield EmailField::new('uploaderEmail')
            ->setLabel('Hochgeladen von (E-Mail)')
            ->hideOnIndex()
            ->setColumns('col-12');
        
        yield DateTimeField::new('createdAt')
            ->setLabel('Hochgeladen am')
            ->setFormat('dd.MM.yyyy HH:mm')
            ->hideOnForm()
            ->setColumns('col-12');
    }

    #[Route('/admin/photos/{id}/approve', name: 'admin_photo_approve')]
    public function approve(Photos $photo, EntityManagerInterface $entityManager): Response
    {
        $photo->setStatus('approved');
        $entityManager->flush();

        $this->addFlash('success', 'Foto wurde freigegeben.');

        return $this->redirect($this->generateUrl('admin', [
            'crudAction' => 'index',
            'crudControllerFqcn' => self::class,
        ]));
    }

    #[Route('/admin/photos/{id}/reject', name: 'admin_photo_reject')]
    public function reject(Photos $photo, EntityManagerInterface $entityManager): Response
    {
        $photo->setStatus('rejected');
        $entityManager->flush();

        $this->addFlash('warning', 'Foto wurde abgelehnt.');

        return $this->redirect($this->generateUrl('admin', [
            'crudAction' => 'index',
            'crudControllerFqcn' => self::class,
        ]));
    }
}
