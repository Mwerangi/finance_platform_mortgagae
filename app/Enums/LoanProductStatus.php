<?php

namespace App\Enums;

enum LoanProductStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::ARCHIVED => 'Archived',
        };
    }
}
