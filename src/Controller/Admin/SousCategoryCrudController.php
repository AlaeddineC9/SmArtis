<?php

namespace App\Controller\Admin;

use App\Entity\SousCategory;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Vich\UploaderBundle\Form\Type\VichImageType;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;

class SousCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SousCategory::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            // Champ Nom
            TextField::new('name', 'Nom'),

            // Champ Slug, généré automatiquement à partir du nom
            SlugField::new('slug', 'Slug')
                ->setTargetFieldName('name'),
                // ->setGenerateUrlFromField('name'), // Retiré car cette méthode n'existe pas

            // Association avec la catégorie parent, avec autocomplétion pour les grandes listes
            AssociationField::new('category', 'Catégorie')
                ->autocomplete(),
        ];

        // Gestion de l'upload de l'image pour les pages de création et d'édition
        // if ($pageName === Crud::PAGE_NEW || $pageName === Crud::PAGE_EDIT) {
        //     $fields[] = Field::new('imageFile', 'Image')
        //         ->setFormType(VichImageType::class)
        //         ->setFormTypeOptions([
        //             'allow_delete' => true, // Permet de supprimer l'image existante
        //             'download_uri' => false, // Désactive le lien de téléchargement
        //         ])
        //         ->setHelp('Téléchargez une image pour la sous-catégorie.');
        // }

        // // Affichage de l'image actuelle uniquement sur la page de détail
        // if ($pageName === Crud::PAGE_DETAIL) {
        //     $fields[] = ImageField::new('image', 'Image actuelle')
        //         ->setBasePath('/uploads/souscategories') // Chemin public pour accéder aux images
        //         ->onlyOnDetail();
        // }

        return $fields;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // Ajoute l'action "Détail" sur la page d'index
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // Définit les labels pour l'entité
            ->setEntityLabelInSingular('Sous-Catégorie')
            ->setEntityLabelInPlural('Sous-Catégories')
            // Définit les titres des pages CRUD
            ->setPageTitle(Crud::PAGE_INDEX, 'Gestion des Sous-Catégories')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer une Sous-Catégorie')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier une Sous-Catégorie')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail de la Sous-Catégorie');
    }
}
