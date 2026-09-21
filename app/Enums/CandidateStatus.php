<?php

namespace App\Enums;

enum CandidateStatus: string
{
    case NEW = 'NEW';
    case CONTACTED = 'CONTACTED';
    case PREQUALIFIED = 'PREQUALIFIED';
    case INTERVIEW = 'INTERVIEW';
    case EVALUATION = 'EVALUATION';
    case ADMITTED = 'ADMITTED';
    case WAITING = 'WAITING';
    case DISCARDED = 'DISCARDED';
    case ONBOARDING = 'ONBOARDING';
    case CONTRACTING = 'CONTRACTING';
    case INDUCTION = 'INDUCTION';
    case READY_TO_ACTIVATE = 'READY_TO_ACTIVATE';
    case ACTIVE = 'ACTIVE';
    case WITHDRAWN = 'WITHDRAWN';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Nuevo',
            self::CONTACTED => 'Contactado',
            self::PREQUALIFIED => 'Precalificado',
            self::INTERVIEW => 'Entrevista',
            self::EVALUATION => 'En evaluación',
            self::ADMITTED => 'Admitido',
            self::WAITING => 'En espera',
            self::DISCARDED => 'Descartado',
            self::ONBOARDING => 'En onboarding',
            self::CONTRACTING => 'En contratación',
            self::INDUCTION => 'En inducción',
            self::READY_TO_ACTIVATE => 'Listo para activar',
            self::ACTIVE => 'Activo',
            self::WITHDRAWN => 'Retirado',
        };
    }
}
