<?php

namespace App\Enums;

enum LeadStatus: string
{
    case NEW = 'NEW';
    case CONTACTED = 'CONTACTED';
    case QUALIFIED = 'QUALIFIED';
    case UNRESPONSIVE = 'UNRESPONSIVE';
    case DISCARDED = 'DISCARDED';
    case CONVERTED = 'CONVERTED';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Nueva',
            self::CONTACTED => 'Contactada',
            self::QUALIFIED => 'Calificada',
            self::UNRESPONSIVE => 'Sin respuesta',
            self::DISCARDED => 'Descartada',
            self::CONVERTED => 'Convertida',
        };
    }
}
