<?php

namespace App\Enums;

enum VerificationStatus: string
{
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending Verification',
            self::VERIFIED => 'Verified',
            self::REJECTED => 'Rejected',
            self::EXPIRED => 'Expired',
        };
    }
}
