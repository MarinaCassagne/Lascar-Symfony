<?php

namespace App\Entity;

use App\Enum\StatutReservation;
use App\Repository\ReservationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $numero_reservation = null;

    #[ORM\Column]
    private ?\DateTime $date_reservation = null;

    #[ORM\Column]
    private ?float $longitude_point_de_depart_passager = null;

    #[ORM\Column]
    private ?float $latitude_point_de_depart_passager = null;

    #[ORM\Column]
    private ?float $longitude_point_arrive_passager = null;

    #[ORM\Column]
    private ?float $latitude_point_arrive_passager = null;

    #[ORM\Column]
    private ?float $longitude_point_de_rdv_passager = null;

    #[ORM\Column]
    private ?float $latitude_point_de_rdv_passager = null;

    #[ORM\Column]
    private ?\DateTime $date_heure_depart = null;

    #[ORM\Column]
    private ?\DateTime $date_heure_arrive = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $nombre_de_passager = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $montant_total_reservation = null;

    #[ORM\Column(enumType: StatutReservation::class)]
    private ?StatutReservation $statut_reservation = null;

    #[ORM\ManyToOne(inversedBy: 'idReservation')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Trajet $trajet = null;

    #[ORM\ManyToOne(inversedBy: 'idReservation')]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroReservation(): ?string
    {
        return $this->numero_reservation;
    }

    public function setNumeroReservation(string $numero_reservation): static
    {
        $this->numero_reservation = $numero_reservation;

        return $this;
    }

    public function getDateReservation(): ?\DateTime
    {
        return $this->date_reservation;
    }

    public function setDateReservation(\DateTime $date_reservation): static
    {
        $this->date_reservation = $date_reservation;

        return $this;
    }

    public function getLongitudePointDeDepartPassager(): ?float
    {
        return $this->longitude_point_de_depart_passager;
    }

    public function setLongitudePointDeDepartPassager(float $longitude_point_de_depart_passager): static
    {
        $this->longitude_point_de_depart_passager = $longitude_point_de_depart_passager;

        return $this;
    }

    public function getLatitudePointDeDepartPassager(): ?float
    {
        return $this->latitude_point_de_depart_passager;
    }

    public function setLatitudePointDeDepartPassager(float $latitude_point_de_depart_passager): static
    {
        $this->latitude_point_de_depart_passager = $latitude_point_de_depart_passager;

        return $this;
    }

    public function getLongitudePointArrivePassager(): ?float
    {
        return $this->longitude_point_arrive_passager;
    }

    public function setLongitudePointArrivePassager(float $longitude_point_arrive_passager): static
    {
        $this->longitude_point_arrive_passager = $longitude_point_arrive_passager;

        return $this;
    }

    public function getLatitudePointArrivePassager(): ?float
    {
        return $this->latitude_point_arrive_passager;
    }

    public function setLatitudePointArrivePassager(float $latitude_point_arrive_passager): static
    {
        $this->latitude_point_arrive_passager = $latitude_point_arrive_passager;

        return $this;
    }

    public function getLongitudePointDeRdvPassager(): ?float
    {
        return $this->longitude_point_de_rdv_passager;
    }

    public function setLongitudePointDeRdvPassager(float $longitude_point_de_rdv_passager): static
    {
        $this->longitude_point_de_rdv_passager = $longitude_point_de_rdv_passager;

        return $this;
    }

    public function getLatitudePointDeRdvPassager(): ?float
    {
        return $this->latitude_point_de_rdv_passager;
    }

    public function setLatitudePointDeRdvPassager(float $latitude_point_de_rdv_passager): static
    {
        $this->latitude_point_de_rdv_passager = $latitude_point_de_rdv_passager;

        return $this;
    }

    public function getDateHeureDepart(): ?\DateTime
    {
        return $this->date_heure_depart;
    }

    public function setDateHeureDepart(\DateTime $date_heure_depart): static
    {
        $this->date_heure_depart = $date_heure_depart;

        return $this;
    }

    public function getDateHeureArrive(): ?\DateTime
    {
        return $this->date_heure_arrive;
    }

    public function setDateHeureArrive(\DateTime $date_heure_arrive): static
    {
        $this->date_heure_arrive = $date_heure_arrive;

        return $this;
    }

    public function getNombreDePassager(): ?int
    {
        return $this->nombre_de_passager;
    }

    public function setNombreDePassager(int $nombre_de_passager): static
    {
        $this->nombre_de_passager = $nombre_de_passager;

        return $this;
    }

    public function getMontantTotalReservation(): ?string
    {
        return $this->montant_total_reservation;
    }

    public function setMontantTotalReservation(string $montant_total_reservation): static
    {
        $this->montant_total_reservation = $montant_total_reservation;

        return $this;
    }

    public function getStatutReservation(): ?StatutReservation
    {
        return $this->statut_reservation;
    }

    public function setStatutReservation(StatutReservation $statut_reservation): static
    {
        $this->statut_reservation = $statut_reservation;

        return $this;
    }

    public function getTrajet(): ?Trajet
    {
        return $this->trajet;
    }

    public function setTrajet(?Trajet $trajet): static
    {
        $this->trajet = $trajet;

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
