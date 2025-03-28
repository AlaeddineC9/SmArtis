<?php

namespace App\Form;

use App\Entity\Address;
use App\Entity\Carrier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $builder
        ->add('addresses', EntityType::class, [
            'label' => false,
            'required' => true,
            'class' => Address::class,
            'choices' => $options['addresses'],
            'expanded' => true,
            'multiple' => false,
            'choice_label' => function (Address $address) {
                return $address->getFullName() . ' - ' . $address->getAddress();
            },
            'choice_value' => 'id' // Ajout crucial
        ])
        ->add('carriers', EntityType::class, [
            'label' => false,
            'required' => true,
            'class' => Carrier::class,
            'choices' => $options['carriers'],
            'expanded' => true,
            'multiple' => false,
            'choice_label' => function (Carrier $carrier) {
                return $carrier->getName() . ' (' . number_format($carrier->getPrice(), 2, ',', ' ') . ' €)';
            },
            'choice_value' => 'id' // Ajout crucial
        ]);
}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'addresses' => null,
            'carriers'  => [],
            'validation_groups' => ['Default'], 
        ]);
    }
}
