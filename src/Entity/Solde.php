<?php

namespace App\Entity;

use App\Repository\SoldeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SoldeRepository::class)]
class Solde
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?float $montant_solde = 0;

    #[ORM\ManyToOne(inversedBy: 'idUser')]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMontantSolde(): ?string
    {
        return $this->montant_solde;
    }

    public function setMontantSolde(float $montant_solde): static
    {
        $this->montant_solde = $montant_solde;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
