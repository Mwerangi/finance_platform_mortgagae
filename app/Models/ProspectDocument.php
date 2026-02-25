<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProspectDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'prospect_id',
        'document_type_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'status',
        'rejection_reason',
        'verified_by',
        'verified_at',
        'notes',
        'expiry_date',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'verified_at' => 'datetime',
        'expiry_date' => 'date',
    ];

    // Relationships
    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere(function ($q) {
                $q->whereNotNull('expiry_date')
                  ->where('expiry_date', '<', now());
            });
    }

    // Accessors
    public function getFileUrlAttribute(): ?string
    {
        if ($this->file_path) {
            return Storage::url($this->file_path);
        }
        return null;
    }

    public function getFileSizeHumanAttribute(): string
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    // Helper Methods
    public function isExpired(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }
        return $this->expiry_date->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
