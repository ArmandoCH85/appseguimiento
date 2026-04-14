<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\GpsTrack;
use Illuminate\Support\Collection;

class GpsRouteReportService
{
    private const SEGMENT_GAP_MS = 300000; // 5 minutos — gap mínimo para cortar segmento

    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

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

    public function calculateDuration(Collection $points): int
    {
        if ($points->count() < 2) {
            return 0;
        }

        $firstTime = (int) $points->first()->time;
        $lastTime = (int) $points->last()->time;

        return max(0, $lastTime - $firstTime);
    }

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

    public function formatDistance(float $distanceMeters): string
    {
        if ($distanceMeters >= 1000) {
            return number_format($distanceMeters / 1000, 2).' km';
        }

        return number_format($distanceMeters, 0).' m';
    }

    public function calculateSpeed(float $lat1, float $lon1, float $lat2, float $lon2, int $timeDiffMs): ?float
    {
        if ($timeDiffMs <= 0) {
            return null;
        }

        $distanceKm = $this->calculateDistance($lat1, $lon1, $lat2, $lon2) / 1000;
        $timeHours = $timeDiffMs / 3600000;

        return round($distanceKm / $timeHours, 1);
    }

    public function getTracksForReport(string $deviceId, ?int $startTimeMs = null, ?int $endTimeMs = null): Collection
    {
        $query = GpsTrack::query()
            ->where('device_id', $deviceId)
            ->orderBy('time', 'asc');

        if ($startTimeMs !== null && $endTimeMs !== null) {
            $query->whereBetween('time', [$startTimeMs, $endTimeMs]);
        }

        return $query->get();
    }

    public function segmentTracks(Collection $points, int $gapMs = self::SEGMENT_GAP_MS): array
    {
        if ($points->count() === 0) {
            return [];
        }

        $segments = [];
        $currentSegment = [$points[0]];

        for ($i = 1; $i < $points->count(); $i++) {
            $prevTime = (int) $points[$i - 1]->time;
            $currentTime = (int) $points[$i]->time;
            $gap = $currentTime - $prevTime;

            if ($gap > $gapMs) {
                $segments[] = collect($currentSegment);
                $currentSegment = [];
            }

            $currentSegment[] = $points[$i];
        }

        $segments[] = collect($currentSegment);

        return $segments;
    }
}
