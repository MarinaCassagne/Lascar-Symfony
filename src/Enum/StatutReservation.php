<?php 

namespace App\Enum;

enum StatutReservation: string {
    case Valide = 'Validé';
    case Refuse = 'Refusé';
    case Annule = 'Annulé';
}