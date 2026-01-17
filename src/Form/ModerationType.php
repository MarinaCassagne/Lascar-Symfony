<?php

namespace App\Form;

use App\Entity\Avis;
use App\Entity\Moderation;
use App\Entity\Trajet;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ModerationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('motif')
            ->add('date_de_creation')
            ->add('canal_de_moderation')
            ->add('type_de_cible')
            ->add('action_de_moderation')
            ->add('idTrajet', EntityType::class, [
                'class' => Trajet::class,
                'choice_label' => 'id',
            ])
            ->add('Avis', EntityType::class, [
                'class' => Avis::class,
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
            'data_class' => Moderation::class,
        ]);
    }
}
