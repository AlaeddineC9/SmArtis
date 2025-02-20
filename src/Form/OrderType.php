<?php

namespace App\Form;

use App\Entity\Address;
use App\Entity\Carrier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Champ des adresses, via EntityType
        $builder->add('addresses', EntityType::class, [
            'label' => "Choisissez votre adresse de livraison",
            'required' => true,
            'class' => Address::class,
            'expanded' => true,
            'multiple' => false,
            'choices' => $options['addresses'],
            'label_html' => true
        ]);
        
        // Champ des carriers, on utilise ChoiceType pour nos carriers éphémères
        $builder->add('carriers', ChoiceType::class, [
            'label' => "Choisissez votre transporteur",
            'required' => true,
            'expanded' => true,
            'multiple' => false,
            'choices' => $options['carriers'],
            'choice_label' => function (?Carrier $carrier) {
                return $carrier ? sprintf('%s (%.2f €)', $carrier->getName(), $carrier->getPrice()) : '';
            },
            'choice_value' => function (?Carrier $carrier) {
                return $carrier ? $carrier->getCodeTransporter() : '';
            },
        ]);
        
        $builder->add('submit', SubmitType::class, [
            'label' => "Valider ma commande",
            'attr' => [
                'class' => 'btn btn-success mb-5 w-100'
            ]
        ]);
    }
    
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'addresses' => null,
            'carriers' => []
        ]);
    }
}
