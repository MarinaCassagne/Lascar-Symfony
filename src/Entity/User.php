<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;



#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column]
    private ?int $age = null;

    #[ORM\Column(length: 255)]
    private ?string $telephone = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $mot_de_passe = null;

    #[ORM\Column(nullable: true)]
    private ?bool $permis_de_conduire = null;

    #[ORM\Column]
    private ?bool $compteValide = null;

    #[ORM\OneToOne(mappedBy: 'User', cascade: ['persist', 'remove'])]
    private ?Moderation $idModeration = null;

    /**
     * @var Collection<int, Trajet>
     */
    #[ORM\OneToMany(targetEntity: Trajet::class, mappedBy: 'User')]
    private Collection $idTrajet;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'user')]
    private Collection $idReservation;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Solde $solde = null;

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'user')]
    private Collection $idNotification;

    /**
     * @var Collection<int, Vehicule>
     */
    #[ORM\OneToMany(targetEntity: Vehicule::class, mappedBy: 'user')]
    private Collection $idVehicule;

    /**
     * @var Collection<int, Avis>
     */
    #[ORM\OneToMany(targetEntity: Avis::class, mappedBy: 'user')]
    private Collection $idAvis;

    public function __construct()
    {
        $this->idTrajet = new ArrayCollection();
        $this->idReservation = new ArrayCollection();
        $this->idNotification = new ArrayCollection();
        $this->idVehicule = new ArrayCollection();
        $this->idAvis = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(int $age): static
    {
        $this->age = $age;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->mot_de_passe;
    }

    public function setMotDePasse(string $mot_de_passe): static
    {
        $this->mot_de_passe = $mot_de_passe;

        return $this;
    }

    public function isPermisDeConduire(): ?bool
    {
        return $this->permis_de_conduire;
    }


    public function getPermisDeConduire(): ?bool
    {
        return $this->permis_de_conduire;
    }


    public function setPermisDeConduire(?bool $permis_de_conduire): static
    {
        $this->permis_de_conduire = $permis_de_conduire;

        return $this;
    }

    public function isCompteValide(): ?bool
    {
        return $this->compteValide;
    }

    public function getCompteValide(): ?bool
    {
        return $this->compteValide;
    }


    public function setCompteValide(bool $compteValide): static
    {
        $this->compteValide = $compteValide;

        return $this;
    }

    public function getIdModeration(): ?Moderation
    {
        return $this->idModeration;
    }

    public function setIdModeration(?Moderation $idModeration): static
    {
        // unset the owning side of the relation if necessary
        if ($idModeration === null && $this->idModeration !== null) {
            $this->idModeration->setUser(null);
        }

        // set the owning side of the relation if necessary
        if ($idModeration !== null && $idModeration->getUser() !== $this) {
            $idModeration->setUser($this);
        }

        $this->idModeration = $idModeration;

        return $this;
    }

    /**
     * @return Collection<int, Trajet>
     */
    public function getIdTrajet(): Collection
    {
        return $this->idTrajet;
    }

    public function addIdTrajet(Trajet $idTrajet): static
    {
        if (!$this->idTrajet->contains($idTrajet)) {
            $this->idTrajet->add($idTrajet);
            $idTrajet->setUser($this);
        }

        return $this;
    }

    public function removeIdTrajet(Trajet $idTrajet): static
    {
        if ($this->idTrajet->removeElement($idTrajet)) {
            // set the owning side to null (unless already changed)
            if ($idTrajet->getUser() === $this) {
                $idTrajet->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getIdReservation(): Collection
    {
        return $this->idReservation;
    }

    public function addIdReservation(Reservation $idReservation): static
    {
        if (!$this->idReservation->contains($idReservation)) {
            $this->idReservation->add($idReservation);
            $idReservation->setUser($this);
        }

        return $this;
    }

    public function removeIdReservation(Reservation $idReservation): static
    {
        if ($this->idReservation->removeElement($idReservation)) {
            // set the owning side to null (unless already changed)
            if ($idReservation->getUser() === $this) {
                $idReservation->setUser(null);
            }
        }

        return $this;
    }

    public function getSolde(): ?Solde
    {
        return $this->solde;
    }

    public function setSolde(?Solde $solde): static
    {
        $this->solde = $solde;

        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getIdNotification(): Collection
    {
        return $this->idNotification;
    }

    public function addIdNotification(Notification $idNotification): static
    {
        if (!$this->idNotification->contains($idNotification)) {
            $this->idNotification->add($idNotification);
            $idNotification->setUser($this);
        }

        return $this;
    }

    public function removeIdNotification(Notification $idNotification): static
    {
        if ($this->idNotification->removeElement($idNotification)) {
            // set the owning side to null (unless already changed)
            if ($idNotification->getUser() === $this) {
                $idNotification->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Vehicule>
     */
    public function getIdVehicule(): Collection
    {
        return $this->idVehicule;
    }

    public function addIdVehicule(Vehicule $idVehicule): static
    {
        if (!$this->idVehicule->contains($idVehicule)) {
            $this->idVehicule->add($idVehicule);
            $idVehicule->setUser($this);
        }

        return $this;
    }

    public function removeIdVehicule(Vehicule $idVehicule): static
    {
        if ($this->idVehicule->removeElement($idVehicule)) {
            // set the owning side to null (unless already changed)
            if ($idVehicule->getUser() === $this) {
                $idVehicule->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Avis>
     */
    public function getIdAvis(): Collection
    {
        return $this->idAvis;
    }

    public function addIdAvi(Avis $idAvi): static
    {
        if (!$this->idAvis->contains($idAvi)) {
            $this->idAvis->add($idAvi);
            $idAvi->setUser($this);
        }

        return $this;
    }

    public function removeIdAvi(Avis $idAvi): static
    {
        if ($this->idAvis->removeElement($idAvi)) {
            // set the owning side to null (unless already changed)
            if ($idAvi->getUser() === $this) {
                $idAvi->setUser(null);
            }
        }

        return $this;
    }

    // FONCTION QUI NE NOUS SERT A RIEN POUR LE MOMENT ET QU'ON UTILISERA SUREMENT PAS 
    // MAIS SINON LES INTERFACES POSENT DES SOUCIS
        public function getRoles(): array
        {
            return [];
        }
}
