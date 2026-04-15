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
        $pointsArray = $points->all();
        $count        = count($pointsArray);

        if ($count < 2) {
            return 0.0;
        }

        $totalDistance = 0.0;

        for ($i = 1; $i < $count; $i++) {
            $prev    = $pointsArray[$i - 1];
            $current = $pointsArray[$i];

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
        return GpsTrack::query()
            ->select(['latitude', 'longitude', 'time', 'accuracy'])
            ->where('device_id', $deviceId)
            ->when($startTimeMs !== null && $endTimeMs !== null, fn ($q) => $q->whereBetween('time', [$startTimeMs, $endTimeMs]))
            ->orderBy('time', 'asc')
            ->toBase()
            ->get();
    }

    /**
     * Decimate a segment to ~1 point per minute for map rendering.
     * Always keeps first and last point. Short segments (≤2 points) are returned as-is.
     *
     * @return object[]
     */
    public function decimateSegmentForMap(Collection $segment, int $intervalMs = 60_000): array
    {
        $arr   = $segment->all();
        $count = count($arr);

        if ($count <= 2) {
            return $arr;
        }

        $result       = [$arr[0]];
        $lastKeptTime = (int) $arr[0]->time;

        for ($i = 1; $i < $count - 1; $i++) {
            if ((int) $arr[$i]->time - $lastKeptTime >= $intervalMs) {
                $result[]     = $arr[$i];
                $lastKeptTime = (int) $arr[$i]->time;
            }
        }

        $result[] = $arr[$count - 1];

        return $result;
    }

    public function segmentTracks(Collection $points, int $gapMs = self::SEGMENT_GAP_MS): array
    {
        $pointsArray = $points->all();
        $count        = count($pointsArray);

        if ($count === 0) {
            return [];
        }

        $segments       = [];
        $currentSegment = [$pointsArray[0]];

        for ($i = 1; $i < $count; $i++) {
            if ((int) $pointsArray[$i]->time - (int) $pointsArray[$i - 1]->time > $gapMs) {
                $segments[]     = collect($currentSegment);
                $currentSegment = [];
            }

            $currentSegment[] = $pointsArray[$i];
        }

        $segments[] = collect($currentSegment);

        return $segments;
    }
}
