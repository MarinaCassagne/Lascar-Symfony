<?php

namespace App\Form;

use App\Entity\Reservation;
use App\Entity\Trajet;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('numero_reservation')
            ->add('date_reservation')
            ->add('longitude_point_de_depart_passager')
            ->add('latitude_point_de_depart_passager')
            ->add('longitude_point_arrive_passager')
            ->add('latitude_point_arrive_passager')
            ->add('longitude_point_de_rdv_passager')
            ->add('latitude_point_de_rdv_passager')
            ->add('date_heure_depart')
            ->add('date_heure_arrive')
            ->add('nombre_de_passager')
            ->add('montant_total_reservation')
            ->add('statut_reservation')
            ->add('trajet', EntityType::class, [
                'class' => Trajet::class,
                'choice_label' => 'id',
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}
