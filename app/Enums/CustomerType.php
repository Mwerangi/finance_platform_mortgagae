<?php

namespace App\Enums;

enum CustomerType: string
{
    case SALARY = 'salary';
    case BUSINESS = 'business';
    case MIXED = 'mixed';

    public function label(): string
    {
        return match($this) {
            self::SALARY => 'Salary Client',
            self::BUSINESS => 'Business Client',
            self::MIXED => 'Mixed Income Client',
        };
    }
}
