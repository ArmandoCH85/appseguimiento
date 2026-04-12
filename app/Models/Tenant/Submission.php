<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Submission extends Model implements HasMedia
{
    use HasUlids;
    use InteractsWithMedia;
    use LogsActivity;

    protected $fillable = [
        'form_version_id',
        'user_id',
        'idempotency_key',
        'latitude',
        'longitude',
        'status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'status' => SubmissionStatus::class,
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * Determine if this submission is a draft.
     */
    public function isDraft(): bool
    {
        return $this->status === SubmissionStatus::Draft;
    }

    public function formVersion(): BelongsTo
    {
        return $this->belongsTo(FormVersion::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SubmissionResponse::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('submissions')
            ->useDisk('public');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['form_version_id', 'user_id', 'idempotency_key', 'status', 'submitted_at'])
            ->logOnlyDirty();
    }
}
