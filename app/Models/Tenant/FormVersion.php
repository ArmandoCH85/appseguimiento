<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class FormVersion extends Model
{
    use HasUlids;
    use LogsActivity;

    protected $fillable = [
        'form_id',
        'version_number',
        'schema_snapshot',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'schema_snapshot' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['form_id', 'version_number', 'published_at'])
            ->logOnlyDirty();
    }
}
