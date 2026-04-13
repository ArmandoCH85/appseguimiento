<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GpsTrack extends Model
{
    use HasUlids;

    protected $table = 'gps_tracks';

    protected $fillable = [
        'device_id',
        'latitude',
        'longitude',
        'time',
        'elapsed_realtime_millis',
        'accuracy',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'time' => 'integer',
            'elapsed_realtime_millis' => 'integer',
            'accuracy' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
