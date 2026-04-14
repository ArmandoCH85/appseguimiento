<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Device extends Model
{
    use HasUlids;
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'imei',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gpsTracks(): HasMany
    {
        return $this->hasMany(GpsTrack::class)->orderByDesc('time');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['user_id', 'imei'])
            ->logOnlyDirty();
    }

    public static function generateUniqueImei(int $maxAttempts = 50): string
    {
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $imei = static::generateImei();

            if (! static::query()->where('imei', $imei)->exists()) {
                return $imei;
            }
        }

        throw new RuntimeException('No se pudo generar un IMEI único.');
    }

    protected static function generateImei(): string
    {
        $base = '';

        for ($i = 0; $i < 14; $i++) {
            $base .= (string) random_int(0, 9);
        }

        return $base . static::calculateLuhnCheckDigit($base);
    }

    protected static function calculateLuhnCheckDigit(string $number): int
    {
        $sum = 0;
        $shouldDouble = true;

        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $digit = (int) $number[$i];

            if ($shouldDouble) {
                $digit *= 2;
                $digit = intdiv($digit, 10) + ($digit % 10);
            }

            $sum += $digit;
            $shouldDouble = ! $shouldDouble;
        }

        return (10 - ($sum % 10)) % 10;
    }
}
