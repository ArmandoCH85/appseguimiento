<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\GpsLocationUpdated;
use App\Models\Central\Tenant;
use App\Models\Tenant\GpsTrack;
use App\Services\GpsTrackService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StoreGpsTrackBatchJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $tenantId,
        public array $validPoints,
    ) {}

    public function handle(GpsTrackService $service): void
    {
        $tenant = Tenant::query()->findOrFail($this->tenantId);

        $tenant->run(function (): void {
            $inserted = $service->insertBatch($this->validPoints);

            // Disparar evento del último punto para broadcast en tiempo real
            if ($inserted > 0 && ! empty($this->validPoints)) {
                $lastPoint = end($this->validPoints);
                $deviceId = $lastPoint['device_id'];

                GpsLocationUpdated::dispatch(
                    $this->tenantId,
                    $deviceId,
                    [
                        'latitud' => $lastPoint['latitude'],
                        'longitud' => $lastPoint['longitude'],
                        'time' => $lastPoint['time'],
                        'elapsedRealtimeMillis' => $lastPoint['elapsed_realtime_millis'],
                        'accuracy' => $lastPoint['accuracy'],
                    ],
                );
            }
        });
    }
}
