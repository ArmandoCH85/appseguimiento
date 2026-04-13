<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Pages;

use App\Exports\GpsTrackExport;
use App\Models\Tenant\Device;
use App\Models\Tenant\GpsTrack;
use App\Services\GpsRouteReportService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Facades\Excel;

class GpsRouteReportPage extends Page
{
    protected string $view = 'filament.tenant.pages.gps-route-report';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Reporte de Recorrido';

    protected static ?string $title = 'Reporte de Recorrido';

    protected static ?int $navigationSort = 11;

    protected Width|string|null $maxContentWidth = Width::Full;

    // Form state
    public ?string $selectedDeviceId = null;

    public string $dateFilter = 'today';

    public ?string $startDate = null;

    public ?string $endDate = null;

    // Report data
    public array $reportPoints = [];

    public array $reportSummary = [];

    public int $pointsCount = 0;

    public string $distanceFormatted = '0 m';

    public string $durationFormatted = '0min';

    public string $firstTimeFormatted = 'N/A';

    public string $lastTimeFormatted = 'N/A';

    public bool $reportGenerated = false;

    // Pagination
    public int $perPage = 20;

    public int $currentPage = 1;

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $today = now()->setTimezone('America/Lima')->format('Y-m-d');
        $this->startDate = $today;
        $this->endDate = $today;
    }

    public function updatedSelectedDeviceId(): void
    {
        $this->generateReport();
    }

    public function updatedDateFilter(): void
    {
        if (filled($this->selectedDeviceId)) {
            $this->generateReport();
        }
    }

    public function updatedStartDate(): void
    {
        if (filled($this->selectedDeviceId) && $this->dateFilter === 'custom') {
            $this->generateReport();
        }
    }

    public function updatedEndDate(): void
    {
        if (filled($this->selectedDeviceId) && $this->dateFilter === 'custom') {
            $this->generateReport();
        }
    }

    public function goToPage(int $page): void
    {
        $this->currentPage = $page;
    }

    public function nextPage(): void
    {
        if ($this->currentPage < $this->getTotalPages()) {
            $this->currentPage++;
        }
    }

    public function previousPage(): void
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
        }
    }

    public function getPaginatedPoints(): array
    {
        $offset = ($this->currentPage - 1) * $this->perPage;
        return array_slice($this->reportPoints, $offset, $this->perPage);
    }

    public function getTotalPages(): int
    {
        return (int) ceil(count($this->reportPoints) / $this->perPage);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermissionTo('devices.view') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getTitle(): string
    {
        return 'Reporte de Recorrido';
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('selectedDeviceId')
                ->label('Dispositivo')
                ->options(function (): array {
                    return Device::query()
                        ->with('user')
                        ->orderBy('imei')
                        ->get()
                        ->mapWithKeys(function (Device $device): array {
                            $label = $device->imei;
                            if ($device->user) {
                                $label .= ' · ' . $device->user->name;
                            }
                            return [$device->id => $label];
                        })
                        ->all();
                })
                ->searchable()
                ->placeholder('Seleccioná un dispositivo')
                ->live(),

            Select::make('dateFilter')
                ->label('Período')
                ->options([
                    'today' => 'Hoy',
                    'yesterday' => 'Ayer',
                    'custom' => 'Personalizado',
                ])
                ->default('today')
                ->live()
                ->required(),

            DatePicker::make('startDate')
                ->label('Fecha inicio')
                ->visible(fn(callable $get): bool => $get('dateFilter') === 'custom')
                ->required(fn(callable $get): bool => $get('dateFilter') === 'custom'),

            DatePicker::make('endDate')
                ->label('Fecha fin')
                ->visible(fn(callable $get): bool => $get('dateFilter') === 'custom')
                ->required(fn(callable $get): bool => $get('dateFilter') === 'custom'),
        ]);
    }

    public function generateReport(): void
    {
        if (blank($this->selectedDeviceId)) {
            return;
        }

        $device = Device::query()->with('user')->find($this->selectedDeviceId);

        if (!$device) {
            return;
        }

        // Calculate date range
        [$startTimeMs, $endTimeMs] = $this->getTimeRange();

        $reportService = app(GpsRouteReportService::class);

        $points = $reportService->getTracksForReport(
            (int) $this->selectedDeviceId,
            $startTimeMs,
            $endTimeMs
        );

        // Format points for map and table
        $this->reportPoints = $points->map(function (GpsTrack $track, int $index) use ($points, $reportService): array {
            $timeHuman = now()
                ->setTimestamp((int) ($track->time / 1000))
                ->setTimezone('America/Lima')
                ->format('d/m/Y H:i:s');

            // Calculate speed
            $speed = null;
            $speedHuman = '-';
            if ($index > 0) {
                $prevTrack = $points[$index - 1];
                $timeDiff = (int) $track->time - (int) $prevTrack->time;
                $speed = $reportService->calculateSpeed(
                    (float) $prevTrack->latitude,
                    (float) $prevTrack->longitude,
                    (float) $track->latitude,
                    (float) $track->longitude,
                    $timeDiff
                );
                $speedHuman = $speed ? "{$speed} km/h" : '-';
            }

            return [
                'latitude' => (float) $track->latitude,
                'longitude' => (float) $track->longitude,
                'accuracy' => $track->accuracy,
                'accuracy_human' => "{$track->accuracy} m",
                'time' => $track->time,
                'time_human' => $timeHuman,
                'speed' => $speed,
                'speed_human' => $speedHuman,
                'index' => $index + 1,
            ];
        })->all();

        // Calculate summary
        $distance = $points->count() > 1 ? $reportService->calculateTotalDistance($points) : 0;
        $duration = $reportService->calculateDuration($points);

        $this->pointsCount = $points->count();
        $this->distanceFormatted = $reportService->formatDistance($distance);
        $this->durationFormatted = $reportService->formatDuration($duration);
        $this->reportGenerated = $points->count() > 0;

        // First and last time
        if ($points->count() > 0) {
            $this->firstTimeFormatted = now()
                ->setTimestamp((int) ($points->first()->time / 1000))
                ->setTimezone('America/Lima')
                ->format('d/m/Y H:i:s');

            $this->lastTimeFormatted = now()
                ->setTimestamp((int) ($points->last()->time / 1000))
                ->setTimezone('America/Lima')
                ->format('d/m/Y H:i:s');
        }

        // Store summary for export
        $periodLabel = match ($this->dateFilter) {
            'today' => 'Hoy (' . now()->setTimezone('America/Lima')->format('d/m/Y') . ')',
            'yesterday' => 'Ayer (' . now()->setTimezone('America/Lima')->subDay()->format('d/m/Y') . ')',
            'custom' => ($this->startDate ?? '') . ' → ' . ($this->endDate ?? ''),
        };

        $this->reportSummary = [
            'imei' => $device->imei,
            'user_name' => $device->user?->name,
            'user_email' => $device->user?->email,
            'period' => $periodLabel,
            'first_time' => $this->firstTimeFormatted,
            'last_time' => $this->lastTimeFormatted,
        ];
    }

    public function exportToExcel(): void
    {
        if (blank($this->selectedDeviceId) || empty($this->reportPoints)) {
            return;
        }

        $reportService = app(GpsRouteReportService::class);

        // Rebuild points collection for export
        $points = collect($this->reportPoints)->map(function (array $point): object {
            $obj = new \stdClass();
            $obj->latitude = $point['latitude'];
            $obj->longitude = $point['longitude'];
            $obj->accuracy = $point['accuracy'];
            $obj->time = $point['time'];
            return $obj;
        });

        $export = new GpsTrackExport($this->reportSummary, $points, $reportService);

        $fileName = 'recorrido_' . ($this->reportSummary['imei'] ?? 'dispositivo') . '_' . now()->format('Y-m-d_His') . '.xlsx';

        Excel::download($export, $fileName);
    }

    /**
     * Get the time range in milliseconds based on the date filter.
     */
    protected function getTimeRange(): array
    {
        $tz = 'America/Lima';

        return match ($this->dateFilter) {
            'today' => (function () use ($tz): array {
                $today = now()->setTimezone($tz)->startOfDay();
                return [
                    (int) $today->copy()->timestamp * 1000,
                    (int) $today->copy()->endOfDay()->timestamp * 1000,
                ];
            })(),
            'yesterday' => (function () use ($tz): array {
                $yesterday = now()->setTimezone($tz)->subDay()->startOfDay();
                return [
                    (int) $yesterday->copy()->timestamp * 1000,
                    (int) $yesterday->copy()->endOfDay()->timestamp * 1000,
                ];
            })(),
            'custom' => (function () use ($tz): array {
                $start = $this->startDate
                    ? Carbon::createFromFormat('Y-m-d', $this->startDate, $tz)->startOfDay()
                    : now()->setTimezone($tz)->startOfDay();
                $end = $this->endDate
                    ? Carbon::createFromFormat('Y-m-d', $this->endDate, $tz)->endOfDay()
                    : now()->setTimezone($tz)->endOfDay();
                return [
                    (int) $start->timestamp * 1000,
                    (int) $end->timestamp * 1000,
                ];
            })(),
        };
    }

    protected function getViewData(): array
    {
        return [
            'devices' => Device::query()
                ->with('user')
                ->orderBy('imei')
                ->get(),
        ];
    }
}
