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
    private ?string $montant_solde = null;

    #[ORM\ManyToOne(inversedBy: 'idUser')]
    private ?User $User = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMontantSolde(): ?string
    {
        return $this->montant_solde;
    }

    public function setMontantSolde(string $montant_solde): static
    {
        $this->montant_solde = $montant_solde;

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
