<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\GpsTrack;
use Illuminate\Support\Collection;

class GpsRouteReportService
{
    /**
     * Calcular distancia entre dos puntos usando Haversine (en metros).
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // metros

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Calcular distancia total de un recorrido en metros.
     */
    public function calculateTotalDistance(Collection $points): float
    {
        $totalDistance = 0.0;

        for ($i = 1; $i < $points->count(); $i++) {
            $prev = $points[$i - 1];
            $current = $points[$i];

            $totalDistance += $this->calculateDistance(
                (float) $prev->latitude,
                (float) $prev->longitude,
                (float) $current->latitude,
                (float) $current->longitude
            );
        }

        return $totalDistance;
    }

    /**
     * Calcular duración del recorrido en milisegundos.
     */
    public function calculateDuration(Collection $points): int
    {
        if ($points->count() < 2) {
            return 0;
        }

        $firstTime = (int) $points->first()->time;
        $lastTime = (int) $points->last()->time;

        return max(0, $lastTime - $firstTime);
    }

    /**
     * Formatear duración en formato legible (Xh Ymin).
     */
    public function formatDuration(int $durationMs): string
    {
        $totalMinutes = (int) floor($durationMs / 60000);
        $hours = (int) floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        if ($hours === 0) {
            return "{$minutes}min";
        }

        return "{$hours}h {$minutes}min";
    }

    /**
     * Formatear distancia en formato legible (X.XX km o X m).
     */
    public function formatDistance(float $distanceMeters): string
    {
        if ($distanceMeters >= 1000) {
            return number_format($distanceMeters / 1000, 2) . ' km';
        }

        return number_format($distanceMeters, 0) . ' m';
    }

    /**
     * Calcular velocidad entre dos puntos en km/h.
     */
    public function calculateSpeed(float $lat1, float $lon1, float $lat2, float $lon2, int $timeDiffMs): ?float
    {
        if ($timeDiffMs <= 0) {
            return null;
        }

        $distanceKm = $this->calculateDistance($lat1, $lon1, $lat2, $lon2) / 1000;
        $timeHours = $timeDiffMs / 3600000;

        return round($distanceKm / $timeHours, 1);
    }

    /**
     * Obtener tracks filtrados por dispositivo y rango de fechas.
     */
    public function getTracksForReport(int $deviceId, ?int $startTimeMs = null, ?int $endTimeMs = null): Collection
    {
        $query = GpsTrack::query()
            ->where('device_id', $deviceId)
            ->orderBy('time', 'asc');

        // Solo filtrar por tiempo si se proporcionan los valores
        if ($startTimeMs !== null && $endTimeMs !== null) {
            $query->whereBetween('time', [$startTimeMs, $endTimeMs]);
        }

        return $query->get();
    }
}
