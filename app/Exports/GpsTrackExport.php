<?php

declare(strict_types=1);

namespace App\Exports;

use App\Services\GpsRouteReportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

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

class SummarySheet implements FromCollection, WithTitle, WithHeadings
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

class PointsSheet implements FromCollection, WithTitle, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    public function __construct(
        private readonly Collection $points,
        private readonly GpsRouteReportService $reportService,
    ) {}

    public function collection(): Collection
    {
        return $this->points->values();
    }

    public function title(): string
    {
        return 'Recorrido';
    }

    public function headings(): array
    {
        return ['#', 'Hora GPS', 'Latitud', 'Longitud', 'Precisión (m)', 'Velocidad (km/h)'];
    }

    public function map($point): array
    {
        static $index = 0;
        $index++;

        $timeHuman = now()
            ->setTimestamp((int) ($point->time / 1000))
            ->setTimezone('America/Lima')
            ->format('d/m/Y H:i:s');

        $speed = null;
        if ($index > 1 && $this->points->has($index - 2)) {
            $prevPoint = $this->points->values()[$index - 2];
            $timeDiff = (int) $point->time - (int) $prevPoint->time;

            $speed = $this->reportService->calculateSpeed(
                (float) $prevPoint->latitude,
                (float) $prevPoint->longitude,
                (float) $point->latitude,
                (float) $point->longitude,
                $timeDiff
            );
        }

        return [
            $index,
            $timeHuman,
            number_format((float) $point->latitude, 8),
            number_format((float) $point->longitude, 8),
            (int) $point->accuracy,
            $speed ?? '-',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                // Auto-filter
                $sheet->setAutoFilter('A1:F1');

                // Freeze first row
                $sheet->freezePane('A2');

                // Style header row
                $sheet->getStyle('A1:F1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '10B981'],
                    ],
                ]);
            },
        ];
    }
}
