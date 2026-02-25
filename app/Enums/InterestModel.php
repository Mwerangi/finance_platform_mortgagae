<?php

namespace App\Enums;

enum InterestModel: string
{
    case REDUCING_BALANCE = 'reducing_balance';
    case FLAT_RATE = 'flat_rate';

    public function label(): string
    {
        return match($this) {
            self::REDUCING_BALANCE => 'Reducing Balance',
            self::FLAT_RATE => 'Flat Rate',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::REDUCING_BALANCE => 'Interest calculated on declining principal balance',
            self::FLAT_RATE => 'Interest calculated on original principal throughout tenure',
        };
    }
}
