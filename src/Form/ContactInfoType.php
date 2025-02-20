<?php

namespace App\Form;

use App\Entity\ContactInfo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ContactInfoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('companyName', TextType::class, [
                'label'    => 'Nom de la société',
                'required' => false,
            ])
            ->add('phone', TextType::class, [
                'label'    => 'Téléphone',
                'required' => false,
            ])
            ->add('email', TextType::class, [
                'label'    => 'Email',
                'required' => false,
            ])
            ->add('address', TextType::class, [
                'label'    => 'Adresse',
                'required' => false,
            ])
            ->add('mapEmbedUrl', TextType::class, [
                'label'    => 'URL Google Maps (iframe)',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactInfo::class,
        ]);
    }
}
