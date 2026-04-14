<?php

declare(strict_types=1);

namespace App\Exports;

use App\Services\GpsRouteReportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class GpsTrackExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private readonly array $deviceInfo,
        private readonly Collection $points,
        private readonly GpsRouteReportService $reportService,
    ) {}

    public function sheets(): array
    {
        return [
            new SummarySheet($this->deviceInfo, $this->points, $this->reportService),
            new PointsSheet($this->points, $this->reportService),
        ];
    }
}
