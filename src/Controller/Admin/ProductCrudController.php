<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Form\ProductMediaType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use Vich\UploaderBundle\Form\Type\VichImageType;

use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;


use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions;
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
        $name = TextField::new('name', 'Nom');
        $slug = SlugField::new('slug')->setTargetFieldName('name');
        $description = TextEditorField::new('description', 'Description');
        $price = NumberField::new('price', 'Prix HT');
        $tva = ChoiceField::new('tva', 'Taux de TVA')
            ->setChoices([
                '2.1%' => '2.1',
                '5.5%' => '5.5',
                '10%' => '10',
                '20%' => '20',
            ]);
        $isHomepage = BooleanField::new('isHomepage', 'Produit à la une ?');
        $illustration = TextField::new('illustrationFile', 'Image principale')
    ->setFormType(VichImageType::class)
    ->setFormTypeOptions(['required' => false]);
        // Champs poids & dimensions
        $weight = NumberField::new('weight', 'Poids (kg)');
        $dimensions = TextField::new('dimensions', 'Dimensions');

        $category = AssociationField::new('category', 'Catégorie associée')->autocomplete();
        $sousCategory = AssociationField::new('sousCategory', 'Sous-catégorie associée')->autocomplete();

        // CollectionField pour gérer ProductMedia
        $medias = CollectionField::new('medias', 'Médias (images/vidéos)')
            ->allowAdd(true)
            ->allowDelete(true)
            ->setEntryType(ProductMediaType::class)
            ->setFormTypeOptions([
                'by_reference' => false,
            ]);

        return [
            $name,
            $isHomepage,
            $slug,
            $description,
            $illustration, // vous pourriez aussi le remplacer par un champ VichUploader si vous le souhaitez
            $price,
            $tva,
            $weight,
            $dimensions,
            $category,
            $sousCategory,
            $medias,
        ];
    }
}
