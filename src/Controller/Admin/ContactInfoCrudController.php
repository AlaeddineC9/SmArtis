<?php

namespace App\Controller\Admin;

use App\Entity\ContactInfo;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
// Les champs standard
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class ContactInfoCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        // L'entité gérée par ce CRUD
        return ContactInfo::class;
    }

    /**
     * Configure les champs à afficher/éditer pour chaque vue (index, edit, etc.)
     */
    public function configureFields(string $pageName): iterable
    {
        return [
            // Sur la page "index", on verra l'ID
            IdField::new('id')->hideOnForm(),
            TextField::new('companyName', 'Nom de la Société'),
            TextField::new('phone', 'Téléphone'),
            TextField::new('email', 'Email'),
            TextField::new('address', 'Adresse'),
            TextField::new('mapEmbedUrl', 'Lien Google Maps'),
        ];
    }

    /**
     * (Optionnel) Personnalise d'autres aspects (pagination, etc.)
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Coordonnée')
            ->setEntityLabelInPlural('Coordonnées de contact')
            ->setPageTitle('index', 'Informations de contact')
            ->setDefaultSort(['id' => 'ASC']);
    }
}
