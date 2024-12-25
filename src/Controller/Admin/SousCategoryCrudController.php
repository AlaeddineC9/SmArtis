<?php

namespace App\Controller\Admin;

use App\Entity\SousCategory;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Vich\UploaderBundle\Form\Type\VichImageType;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;

class SousCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SousCategory::class;
    }

    public function configureFields(string $pageName): iterable
    {
        // Champs communs : Nom, Slug, Catégorie
        $fields = [
            TextField::new('name', 'Nom'),
            SlugField::new('slug', 'Slug')
                ->setTargetFieldName('name'),
            AssociationField::new('category', 'Catégorie')
                ->autocomplete(),
        ];

        // Upload en création/édition
        if ($pageName === Crud::PAGE_NEW || $pageName === Crud::PAGE_EDIT) {
            $fields[] = TextField::new('illustrationFile', 'Image')
                ->setFormType(VichImageType::class)
                ->setFormTypeOptions([
                    'allow_delete' => true,
                    'download_uri' => false,
                ])
                ->setHelp('Télécharger une image de sous-catégorie (optionnel).');
        }

        // Affichage de l'image en index/détail (read-only)
        if ($pageName === Crud::PAGE_INDEX || $pageName === Crud::PAGE_DETAIL) {
            $fields[] = ImageField::new('illustration', 'Image')
                ->setBasePath('/uploads/souscategories')
                ->onlyOnIndex() // ou ->onlyOnIndex() et ->onlyOnDetail() selon vos préférences
            ;
        }

        return $fields;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);  // ajouter l'action Détail en index
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Sous-Catégorie')
            ->setEntityLabelInPlural('Sous-Catégories')
            ->setPageTitle(Crud::PAGE_INDEX, 'Gestion des Sous-Catégories')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer une Sous-Catégorie')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier une Sous-Catégorie')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail de la Sous-Catégorie');
    }
}
