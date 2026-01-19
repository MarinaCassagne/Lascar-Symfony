<?php

// Namespace : indique que cette classe se trouve dans App\Form
namespace App\Form;

// Import des entités utilisées dans le formulaire
use App\Entity\Avis;
use App\Entity\Moderation;
use App\Entity\Trajet;
use App\Entity\User;

// Import du type EntityType pour créer des champs liés à des entités
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

// Import des classes de base de Symfony Form
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

// Cette classe définit le formulaire de l'entité Moderation
class ModerationType extends AbstractType
{
    // Méthode qui construit le formulaire
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Champ texte pour le motif de la modération
            ->add('motif')

            // Champ pour la date de création (souvent DateTime)
            ->add('date_de_creation')

            // Champ indiquant par quel canal la modération a été faite
            ->add('canal_de_moderation')

            // Champ pour définir le type de cible (trajet, avis, utilisateur, etc.)
            ->add('type_de_cible')

            // Champ pour l’action effectuée (supprimer, avertir, bloquer…)
            ->add('action_de_moderation')

            // Champ lié à l'entité Trajet
            // EntityType permet de choisir un Trajet existant dans la base de données
            ->add('idTrajet', EntityType::class, [
                'class' => Trajet::class,      // Entité concernée
                'choice_label' => 'id',        // Ce qui s’affiche dans la liste déroulante
            ])

            // Champ lié à l'entité Avis
            ->add('Avis', EntityType::class, [
                'class' => Avis::class,
                'choice_label' => 'id',
            ])

            // Champ lié à l'entité User
            ->add('User', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])
        ;
    }

    // Configure les options du formulaire
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Indique que ce formulaire est lié à l'entité Moderation
            'data_class' => Moderation::class,
        ]);
    }
}
