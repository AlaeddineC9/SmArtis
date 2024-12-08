<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;

class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Produit')
            ->setEntityLabelInPlural('Produits')
            ->setPageTitle(Crud::PAGE_INDEX, 'Gestion des Produits')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer un Produit')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier un Produit')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail du Produit');
    }

    public function configureFields(string $pageName): iterable
    {
        $required = true;

        if ($pageName === Crud::PAGE_EDIT) {
            $required = false;
        }

        return [
            TextField::new('name')
                ->setLabel('Nom')
                ->setHelp('Nom de votre produit'),

            BooleanField::new('isHomepage')
                ->setLabel('Produit à la une ?')
                ->setHelp('Mettre en avant ce produit sur la page d\'accueil')
                ->renderAsSwitch(),

            SlugField::new('slug')
                ->setTargetFieldName('name')
                ->setLabel('URL')
                ->setHelp('URL de votre produit'),

            TextEditorField::new('description')
                ->setLabel('Description')
                ->setHelp('Description de votre produit'),

            ImageField::new('illustration')
                ->setLabel('Image')
                ->setHelp('Image de votre produit en 600x600px')
                ->setUploadedFileNamePattern('[year]-[month]-[day]-[contenthash].[extension]')
                ->setBasePath('/uploads/products')
                ->setUploadDir('public/uploads/products')
                ->setRequired($required),

            NumberField::new('price')
                ->setLabel('Prix H.T')
                ->setHelp('Prix H.T de votre produit sans le sigle €'),

            ChoiceField::new('tva')
                ->setLabel('Taux de TVA')
                ->setChoices([
                    '2.1%' => '2.1',
                    '5.5%' => '5.5',
                    '10%' => '10',
                    '20%' => '20',
                ]),

            AssociationField::new('category', 'Catégorie associée')
                ->autocomplete(),

            AssociationField::new('sousCategory', 'Sous-catégorie associée')
                ->autocomplete(),
        ];
    }
}
