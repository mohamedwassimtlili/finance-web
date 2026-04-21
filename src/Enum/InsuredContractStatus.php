<?php
namespace App\Enum;

enum InsuredContractStatus: string
{
    case NOT_SIGNED = 'NOT_SIGNED';
    case SIGNED = 'SIGNED';
    case REJECTED = 'REJECTED';
}
