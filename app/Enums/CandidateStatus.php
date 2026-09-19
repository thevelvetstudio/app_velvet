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
}
