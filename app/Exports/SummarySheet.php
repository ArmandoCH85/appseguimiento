<?php

declare(strict_types=1);

namespace App\Exports;

use App\Services\GpsRouteReportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class SummarySheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private readonly array $deviceInfo,
        private readonly Collection $points,
        private readonly GpsRouteReportService $reportService,
    ) {}

    public function collection(): Collection
    {
        $distance = $this->points->count() > 1
            ? $this->reportService->calculateTotalDistance($this->points)
            : 0;

        $duration = $this->reportService->calculateDuration($this->points);

        return collect([
            ['Dispositivo', $this->deviceInfo['imei'] ?? 'N/A'],
            ['Usuario', $this->deviceInfo['user_name'] ?? 'N/A'],
            ['Email', $this->deviceInfo['user_email'] ?? 'N/A'],
            ['Período', $this->deviceInfo['period'] ?? 'N/A'],
            ['', ''],
            ['Total de puntos', $this->points->count()],
            ['Distancia total', $this->reportService->formatDistance($distance)],
            ['Duración', $this->reportService->formatDuration($duration)],
            ['', ''],
            ['Primera lectura', $this->deviceInfo['first_time'] ?? 'N/A'],
            ['Última lectura', $this->deviceInfo['last_time'] ?? 'N/A'],
            ['Exportado el', now()->setTimezone('America/Lima')->format('d/m/Y H:i:s')],
        ]);
    }

    public function title(): string
    {
        return 'Resumen';
    }

    public function headings(): array
    {
        return ['Campo', 'Valor'];
    }
}
