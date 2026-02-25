<?php

namespace App\Enums;

enum DocumentType: string
{
    case NATIONAL_ID = 'national_id';
    case PASSPORT = 'passport';
    case DRIVERS_LICENSE = 'drivers_license';
    case UTILITY_BILL = 'utility_bill';
    case BANK_STATEMENT = 'bank_statement';
    case EMPLOYMENT_LETTER = 'employment_letter';
    case BUSINESS_LICENSE = 'business_license';
    case TAX_CERTIFICATE = 'tax_certificate';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::NATIONAL_ID => 'National ID (NIDA)',
            self::PASSPORT => 'Passport',
            self::DRIVERS_LICENSE => 'Driver\'s License',
            self::UTILITY_BILL => 'Utility Bill',
            self::BANK_STATEMENT => 'Bank Statement',
            self::EMPLOYMENT_LETTER => 'Employment Letter',
            self::BUSINESS_LICENSE => 'Business License',
            self::TAX_CERTIFICATE => 'Tax Certificate',
            self::OTHER => 'Other Document',
        };
    }
}
