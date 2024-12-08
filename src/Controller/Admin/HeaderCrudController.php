<?php

namespace App\Controller\Admin;

use App\Entity\Header;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use Vich\UploaderBundle\Form\Type\VichFileType;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;

class HeaderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Header::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Header')
            ->setEntityLabelInPlural('Headers')
            ->setPageTitle(Crud::PAGE_INDEX, 'Gestion des Headers')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer un Header')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier un Header')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail du Header');
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            TextField::new('title', 'Titre'),
            TextareaField::new('content', 'Contenu'),
            TextField::new('buttonTitle', 'Titre du bouton'),
            TextField::new('buttonLink', 'Lien du bouton'),
        ];

        if (in_array($pageName, [Crud::PAGE_NEW, Crud::PAGE_EDIT])) {
            $fields[] = Field::new('illustrationFile', 'Média d\'illustration')
                ->setFormType(VichFileType::class)
                ->setFormTypeOptions([
                    'allow_delete' => true, // Permet de supprimer l'image existante
                    'download_uri' => false, // Désactive le lien de téléchargement
                ])
                ->setHelp('Téléchargez une image ou une vidéo');
        }

        if ($pageName === Crud::PAGE_DETAIL) {
            $fields[] = ImageField::new('illustration', 'Média actuel')
                ->setBasePath('/uploads/headers') // Chemin public pour accéder aux images
                ->onlyOnDetail();
        }

        return $fields;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }
}
