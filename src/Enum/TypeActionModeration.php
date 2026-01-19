<?php

namespace App\Enum;

enum TypeActionModeration: string 
{
    case Valide = 'Validé';
    case Supprime = 'Supprimé';
    case Rejete = 'Rejeté';
    case Suspendu = 'Suspendu';


}