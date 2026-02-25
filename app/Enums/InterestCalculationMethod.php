<?php

namespace App\Enums;

enum InterestCalculationMethod: string
{
    case REDUCING_BALANCE = 'reducing_balance';
    case FLAT_RATE = 'flat_rate';
}
