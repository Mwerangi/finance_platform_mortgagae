<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class KycDocument extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'institution_id',
        'document_type',
        'document_number',
        'document_name',
        'description',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'uploaded_by',
        'verification_status',
        'verification_notes',
        'verified_by',
        'verified_at',
        'rejected_by',
        'rejected_at',
        'rejection_notes',
        'expiry_date',
        'is_expired',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'document_type' => DocumentType::class,
        'verification_status' => VerificationStatus::class,
        'file_size' => 'integer',
        'verified_at' => 'datetime',
        'rejected_at' => 'datetime',
        'expiry_date' => 'date',
        'is_expired' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($document) {
            // Check if document is expired
            if ($document->expiry_date && $document->expiry_date->isPast()) {
                $document->is_expired = true;
                if ($document->verification_status === VerificationStatus::VERIFIED) {
                    $document->verification_status = VerificationStatus::EXPIRED;
                }
            }
        });

        // Update customer profile completion when document is saved
        static::saved(function ($document) {
            if ($document->customer) {
                $document->customer->updateProfileCompletion();
            }
        });

        // Update customer profile completion when document is deleted
        static::deleted(function ($document) {
            if ($document->customer) {
                $document->customer->updateProfileCompletion();
            }
        });
    }

    /**
     * Get the customer that owns the document.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the institution that owns the document.
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the user who verified the document.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get the user who uploaded the document.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the user who rejected the document.
     */
    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Check if document is verified.
     */
    public function isVerified(): bool
    {
        return $this->verification_status === VerificationStatus::VERIFIED;
    }

    /**
     * Check if document is pending verification.
     */
    public function isPending(): bool
    {
        return $this->verification_status === VerificationStatus::PENDING;
    }

    /**
     * Check if document is expired.
     */
    public function checkExpiry(): void
    {
        if ($this->expiry_date && $this->expiry_date->isPast()) {
            $this->update([
                'is_expired' => true,
                'verification_status' => VerificationStatus::EXPIRED,
            ]);
        }
    }

    /**
     * Verify the document.
     */
    public function verify(int $verifiedBy, ?string $notes = null): void
    {
        $this->update([
            'verification_status' => VerificationStatus::VERIFIED,
            'verified_by' => $verifiedBy,
            'verified_at' => now(),
            'verification_notes' => $notes,
        ]);
    }

    /**
     * Reject the document.
     */
    public function reject(int $rejectedBy, string $notes): void
    {
        $this->update([
            'verification_status' => VerificationStatus::REJECTED,
            'rejected_by' => $rejectedBy,
            'rejected_at' => now(),
            'rejection_notes' => $notes,
        ]);
    }

    /**
     * Get file size in human-readable format.
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get the full file URL.
     */
    public function getFileUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    /**
     * Scope to verified documents.
     */
    public function scopeVerified($query)
    {
        return $query->where('verification_status', VerificationStatus::VERIFIED);
    }

    /**
     * Scope to pending documents.
     */
    public function scopePending($query)
    {
        return $query->where('verification_status', VerificationStatus::PENDING);
    }

    /**
     * Scope to documents of a specific type.
     */
    public function scopeOfType($query, DocumentType $type)
    {
        return $query->where('document_type', $type);
    }
}
