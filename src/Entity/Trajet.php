<?php

namespace App\Entity;

use App\Enum\NatureTrajet;
use App\Enum\StatutValidTrajet;
use App\Enum\TypeTrajet;
use App\Repository\TrajetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert; // permet de mettre des contraintes pour valider les données

#[ORM\Entity(repositoryClass: TrajetRepository::class)]
class Trajet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    private ?\DateTime $date_de_depart = null;

    #[ORM\Column(length: 255)]
    private ?string $adresse_Depart = null;

    #[ORM\Column(length: 255)]
    private ?string $adresse_Arrivee = null;

    #[ORM\Column]
    private ?float $longitude_lieu_depart_conducteur = null;

    #[ORM\Column]
    private ?float $latitude_lieu_depart_conducteur = null;

    #[ORM\Column]
    private ?float $longitude_lieu_arrive_conducteur = null;

    #[ORM\Column]
    private ?float $latitude_lieu_arrive_conducteur = null;

    #[ORM\Column]
    private ?int $duree = null;

    #[ORM\Column]
    private ?int $nombre_de_km = null;

    #[ORM\Column]
    private ?int $nombre_de_place = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $prix = null;

    #[ORM\Column]
    private ?\DateTime $date_de_publication = null;

    #[ORM\Column(enumType: NatureTrajet::class)]
    #[Assert\Choice(
    callback: [NatureTrajet::class],
    message: 'The nature of the path "{{ value }}" is not valid.'
    )]
    private ?NatureTrajet $nature_trajet = null;

    #[ORM\Column(enumType: TypeTrajet::class)]
    #[Assert\Choice(
    callback: [TypeTrajet::class],
    message: 'Le nature du trajet "{{ value }}" n\'est pas valide'
    )]
    private ?TypeTrajet $type_trajet = null;

    #[ORM\Column(enumType: StatutValidTrajet::class)]
    private ?StatutValidTrajet $statut_valide = null;

    #[ORM\OneToOne(mappedBy: 'idTrajet', cascade: ['persist', 'remove'])]
    private ?Moderation $idModeration = null;

    #[ORM\ManyToOne(inversedBy: 'idUser')]
    private ?User $User = null;

    /**
     * @var Collection<int, EtapeTrajet>
     */
    #[ORM\OneToMany(targetEntity: EtapeTrajet::class, mappedBy: 'Trajet')]
    private Collection $idEtapeTrajet;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'trajet', orphanRemoval: true)]
    private Collection $idReservation;

    public function __construct()
    {
        $this->idEtapeTrajet = new ArrayCollection();
        $this->idReservation = new ArrayCollection();
       
    }


    

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateDeDepart(): ?\DateTime
    {
        return $this->date_de_depart;
    }

    public function setDateDeDepart(\DateTime $date_de_depart): static
    {
        $this->date_de_depart = $date_de_depart;

        return $this;
    }

    public function getAdresseDepartConducteur(): ?string
    {
        return $this->adresse_Depart;
    }

    public function setAdresseDepartConducteur(string $adresse_Depart): static
    {
        $this->adresse_Depart = $adresse_Depart;

        return $this;
    }

    public function getAdresseArriveeConducteur(): ?string
    {
        return $this->adresse_Arrivee;
    }

    public function setAdresseArriveeConducteur(string $adresse_Arrivee): static
    {
        $this->adresse_Arrivee = $adresse_Arrivee;

        return $this;
    }

    public function getLongitudeLieuDepartConducteur(): ?float
    {
        return $this->longitude_lieu_depart_conducteur;
    }

    public function setLongitudeLieuDepartConducteur(float $longitude_lieu_depart_conducteur): static
    {
        $this->longitude_lieu_depart_conducteur = $longitude_lieu_depart_conducteur;

        return $this;
    }

    public function getLatitudeLieuDepartConducteur(): ?float
    {
        return $this->latitude_lieu_depart_conducteur;
    }

    public function setLatitudeLieuDepartConducteur(float $latitude_lieu_depart_conducteur): static
    {
        $this->latitude_lieu_depart_conducteur = $latitude_lieu_depart_conducteur;

        return $this;
    }

    public function getLongitudeLieuArriveConducteur(): ?float
    {
        return $this->longitude_lieu_arrive_conducteur;
    }

    public function setLongitudeLieuArriveConducteur(float $longitude_lieu_arrive_conducteur): static
    {
        $this->longitude_lieu_arrive_conducteur = $longitude_lieu_arrive_conducteur;

        return $this;
    }

    public function getLatitudeLieuArriveConducteur(): ?float
    {
        return $this->latitude_lieu_arrive_conducteur;
    }

    public function setLatitudeLieuArriveConducteur(float $latitude_lieu_arrive_conducteur): static
    {
        $this->latitude_lieu_arrive_conducteur = $latitude_lieu_arrive_conducteur;

        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(int $duree): static
    {
        $this->duree = $duree;

        return $this;
    }

    public function getNombreDeKm(): ?int
    {
        return $this->nombre_de_km;
    }

    public function setNombreDeKm(int $nombre_de_km): static
    {
        $this->nombre_de_km = $nombre_de_km;

        return $this;
    }

    public function getNombreDePlace(): ?int
    {
        return $this->nombre_de_place;
    }

    public function setNombreDePlace(int $nombre_de_place): static
    {
        $this->nombre_de_place = $nombre_de_place;

        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getDateDePublication(): ?\DateTime
    {
        return $this->date_de_publication;
    }

    public function setDateDePublication(\DateTime $date_de_publication): static
    {
        $this->date_de_publication = $date_de_publication;

        return $this;
    }

    public function getNatureTrajet(): ?NatureTrajet
    {
        return $this->nature_trajet;
    }

    public function setNatureTrajet(NatureTrajet $nature_trajet): static
    {
        $this->nature_trajet = $nature_trajet;

        return $this;
    }

    public function getTypeTrajet(): ?TypeTrajet
    {
        return $this->type_trajet;
    }

    public function setTypeTrajet(TypeTrajet $type_trajet): static
    {
        $this->type_trajet = $type_trajet;

        return $this;
    }

    public function getStatutValide(): ?StatutValidTrajet
    {
        return $this->statut_valide;
    }

    public function setStatutValide(StatutValidTrajet $statut_valide): static
    {
        $this->statut_valide = $statut_valide;

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
            $this->idModeration->setIdTrajet(null);
        }

        // set the owning side of the relation if necessary
        if ($idModeration !== null && $idModeration->getIdTrajet() !== $this) {
            $idModeration->setIdTrajet($this);
        }

        $this->idModeration = $idModeration;

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

    /**
     * @return Collection<int, EtapeTrajet>
     */
    public function getIdEtapeTrajet(): Collection
    {
        return $this->idEtapeTrajet;
    }

    public function addIdEtapeTrajet(EtapeTrajet $idEtapeTrajet): static
    {
        if (!$this->idEtapeTrajet->contains($idEtapeTrajet)) {
            $this->idEtapeTrajet->add($idEtapeTrajet);
            $idEtapeTrajet->setTrajet($this);
        }

        return $this;
    }

    public function removeIdEtapeTrajet(EtapeTrajet $idEtapeTrajet): static
    {
        if ($this->idEtapeTrajet->removeElement($idEtapeTrajet)) {
            // set the owning side to null (unless already changed)
            if ($idEtapeTrajet->getTrajet() === $this) {
                $idEtapeTrajet->setTrajet(null);
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
            $idReservation->setTrajet($this);
        }

        return $this;
    }

    public function removeIdReservation(Reservation $idReservation): static
    {
        if ($this->idReservation->removeElement($idReservation)) {
            // set the owning side to null (unless already changed)
            if ($idReservation->getTrajet() === $this) {
                $idReservation->setTrajet(null);
            }
        }

        return $this;
    }
}
