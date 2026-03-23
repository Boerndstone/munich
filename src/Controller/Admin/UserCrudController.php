<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

#[\Symfony\Component\Security\Http\Attribute\IsGranted('ROLE_SUPER_ADMIN')]
class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->onlyOnIndex();
        yield AvatarField::new('avatar')
            ->formatValue(static function ($value, User $user) {
                return $user->getAvatarUrl();
            })
            ->hideOnForm();
        yield ImageField::new('avatar')
            ->setBasePath('uploads/avatars')
            ->setUploadDir('public/uploads/avatars')
            ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
            ->setRequired(false)
            ->setHelp('Leer lassen, um das aktuelle Bild beim Speichern zu behalten.')
            ->onlyOnForms();
        yield EmailField::new('email');
        yield TextField::new('firstname');
        yield TextField::new('lastname');
        yield TextField::new('username');
        yield TextField::new('password');
        yield AssociationField::new('editableRocks')
            ->setLabel('Editierbare Felsen')
            ->autocomplete()
            ->setHelp('Nur für Nutzer mit Rolle „Rock-Editor“: diese Felsen dürfen sie im Admin bearbeiten. Super-Admins ignorieren diese Liste.');
        $roles = ['ROLE_SUPER_ADMIN', 'ROLE_ADMIN', 'ROLE_MODERATOR', 'ROLE_ROCK_EDITOR', 'ROLE_USER'];
        yield ChoiceField::new('roles')
            ->setChoices(array_combine($roles, $roles))
            ->allowMultipleChoices()
            ->renderExpanded()
            ->renderAsBadges();
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setPageTitle(Crud::PAGE_INDEX, 'Übersicht der Nutzer')
            ->setPageTitle(Crud::PAGE_NEW, 'Nutzer hinzufügen')
            ->showEntityActionsInlined();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User) {
            $avatar = $entityInstance->getAvatar();
            if (null === $avatar || '' === $avatar) {
                $entityInstance->setAvatar('');
            }
        }
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User) {
            $id = $entityInstance->getId();
            if (null !== $id) {
                $existing = $entityManager->find(User::class, $id);
                $incoming = $entityInstance->getAvatar();
                if (null === $incoming || '' === $incoming) {
                    $entityInstance->setAvatar($existing?->getAvatar() ?? '');
                }
            }
        }
        parent::updateEntity($entityManager, $entityInstance);
    }
}
