<?php

namespace App\Enums;

enum IncomeClassification: string
{
    case SALARY = 'salary';
    case BUSINESS = 'business';
    case MIXED = 'mixed';
    case IRREGULAR = 'irregular';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return match($this) {
            self::SALARY => 'Salary Income',
            self::BUSINESS => 'Business Income',
            self::MIXED => 'Mixed Income (Salary + Business)',
            self::IRREGULAR => 'Irregular Income',
            self::UNKNOWN => 'Unknown Income Pattern',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::SALARY => 'Regular monthly salary from employment',
            self::BUSINESS => 'Variable income from business operations',
            self::MIXED => 'Combination of salary and business income',
            self::IRREGULAR => 'No consistent income pattern detected',
            self::UNKNOWN => 'Unable to determine income pattern',
        };
    }
}
