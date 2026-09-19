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
}
