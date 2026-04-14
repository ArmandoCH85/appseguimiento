<?php

declare(strict_types=1);

namespace App\Exports;

use App\Services\GpsRouteReportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PointsSheet implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithTitle
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

                $sheet->setAutoFilter('A1:F1');
                $sheet->freezePane('A2');

                $sheet->getStyle('A1:F1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '10B981'],
                    ],
                ]);
            },
        ];
    }
}
