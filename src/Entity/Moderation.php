<?php

namespace App\Entity;//Le début du fichier (les bases)

use App\Enum\CanalDeModeration;
use App\Enum\TypeActionModeration;
use App\Enum\TypeCible;
use App\Repository\ModerationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;//Ça dit juste où se trouve la classe dans le projet

#[ORM\Entity(repositoryClass: ModerationRepository::class)]
class Moderation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $motif = null;

    #[ORM\Column]
    private ?\DateTime $date_de_creation = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY, enumType: CanalDeModeration::class)]
    private array $canal_de_moderation = [];

    #[ORM\Column(enumType: TypeCible::class)]
    private ?TypeCible $type_de_cible = null;

    #[ORM\Column(enumType: TypeActionModeration::class)]
    private ?TypeActionModeration $action_de_moderation = null;

    #[ORM\OneToOne(inversedBy: 'idModeration', cascade: ['persist', 'remove'])]
    private ?Trajet $idTrajet = null;

    #[ORM\OneToOne(inversedBy: 'idModeration', cascade: ['persist', 'remove'])]
    private ?Avis $Avis = null;

    #[ORM\OneToOne(inversedBy: 'idModeration', cascade: ['persist', 'remove'])]
    private ?User $User = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(string $motif): static
    {
        $this->motif = $motif;

        return $this;
    }

    public function getDateDeCreation(): ?\DateTime
    {
        return $this->date_de_creation;
    }

    public function setDateDeCreation(\DateTime $date_de_creation): static
    {
        $this->date_de_creation = $date_de_creation;

        return $this;
    }

    /**
     * @return CanalDeModeration[]
     */
    public function getCanalDeModeration(): array
    {
        return $this->canal_de_moderation;
    }

    public function setCanalDeModeration(array $canal_de_moderation): static
    {
        $this->canal_de_moderation = $canal_de_moderation;

        return $this;
    }

    public function getTypeDeCible(): ?TypeCible
    {
        return $this->type_de_cible;
    }

    public function setTypeDeCible(TypeCible $type_de_cible): static
    {
        $this->type_de_cible = $type_de_cible;

        return $this;
    }

    public function getActionDeModeration(): ?TypeActionModeration
    {
        return $this->action_de_moderation;
    }

    public function setActionDeModeration(TypeActionModeration $action_de_moderation): static
    {
        $this->action_de_moderation = $action_de_moderation;

        return $this;
    }

    public function getIdTrajet(): ?Trajet
    {
        return $this->idTrajet;
    }

    public function setIdTrajet(?Trajet $idTrajet): static
    {
        $this->idTrajet = $idTrajet;

        return $this;
    }

    public function getAvis(): ?Avis
    {
        return $this->Avis;
    }

    public function setAvis(?Avis $Avis): static
    {
        $this->Avis = $Avis;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->User;
    }

    public function setUser(?User $User): static
    {
        $this->User = $User;

        return $this;
    }
}
