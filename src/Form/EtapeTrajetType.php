<?php

namespace App\Form;

use App\Entity\EtapeTrajet;
use App\Entity\Trajet;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EtapeTrajetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('latitude_etape')
            ->add('longitude_etape')
            ->add('Trajet', EntityType::class, [
                'class' => Trajet::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EtapeTrajet::class,
        ]);
    }
}
