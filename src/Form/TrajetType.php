<?php

namespace App\Form;

use App\Entity\Moderation;
use App\Entity\Trajet;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TrajetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date_de_depart')
            ->add('lieu_depart_conducteur')
            ->add('longitude_lieu_depart_conducteur')
            ->add('latitude_lieu_depart_conducteur')
            ->add('longitude_lieu_arrive_conducteur')
            ->add('latitude_lieu_arrive_conducteur')
            ->add('duree')
            ->add('nombre_de_km')
            ->add('nombre_de_place')
            ->add('prix')
            ->add('date_de_publication')
            ->add('nature_trajet')
            ->add('type_trajet')
            ->add('statut_valide')
            ->add('idModeration', EntityType::class, [
                'class' => Moderation::class,
                'choice_label' => 'id',
            ])
            ->add('User', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trajet::class,
        ]);
    }
}
