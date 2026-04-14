<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Pages;

use App\Exports\GpsTrackExport;
use App\Models\Tenant\Device;
use App\Models\Tenant\GpsTrack;
use App\Services\GpsRouteReportService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class GpsRouteReportPage extends Page
{
    protected static ?string $resource = null;

    protected string $view = 'filament.tenant.pages.gps-route-report';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Reporte de Recorrido';

    protected static ?string $title = 'Reporte de Recorrido';

    protected static ?int $navigationSort = 11;

    protected Width|string|null $maxContentWidth = Width::Full;

    public ?array $data = [];

    public array $reportPoints = [];

    public array $reportSummary = [];

    public int $pointsCount = 0;

    public string $distanceFormatted = '0 m';

    public string $durationFormatted = '0min';

    public string $firstTimeFormatted = 'N/A';

    public string $lastTimeFormatted = 'N/A';

    public bool $reportGenerated = false;

    public array $reportSegments = [];

    public int $perPage = 20;

    public int $currentPage = 1;

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $today = now()->setTimezone('America/Lima')->format('Y-m-d');
        $this->form->fill([
            'selectedDeviceId' => null,
            'dateFilter' => 'today',
            'startDate' => $today,
            'endDate' => $today,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Filtros del Reporte')
                    ->icon('heroicon-o-map')
                    ->schema([
                        Grid::make(12)
                            ->schema([
                                Select::make('selectedDeviceId')
                                    ->label('Dispositivo')
                                    ->columnSpan(4)
                                    ->options(function (): array {
                                        return Device::query()
                                            ->with('user')
                                            ->orderBy('imei')
                                            ->get()
                                            ->mapWithKeys(fn (Device $device): array => [
                                                $device->id => $device->imei.($device->user ? ' · '.$device->user->name : ''),
                                            ])
                                            ->all();
                                    })
                                    ->searchable()
                                    ->placeholder('Seleccioná un dispositivo')
                                    ->live()
                                    ->afterStateUpdated(fn () => $this->generateReport()),

                                Select::make('dateFilter')
                                    ->label('Período')
                                    ->columnSpan(3)
                                    ->options([
                                        'today' => 'Hoy',
                                        'yesterday' => 'Ayer',
                                        'custom' => 'Personalizado',
                                    ])
                                    ->default('today')
                                    ->live()
                                    ->afterStateUpdated(fn () => $this->generateReport())
                                    ->required(),

                                DatePicker::make('startDate')
                                    ->label('Fecha inicio')
                                    ->columnSpan(2)
                                    ->visible(fn (callable $get): bool => $get('dateFilter') === 'custom')
                                    ->required(fn (callable $get): bool => $get('dateFilter') === 'custom')
                                    ->live()
                                    ->afterStateUpdated(fn () => $this->generateReport()),

                                DatePicker::make('endDate')
                                    ->label('Fecha fin')
                                    ->columnSpan(2)
                                    ->visible(fn (callable $get): bool => $get('dateFilter') === 'custom')
                                    ->required(fn (callable $get): bool => $get('dateFilter') === 'custom')
                                    ->live()
                                    ->afterStateUpdated(fn () => $this->generateReport()),
                            ]),
                    ]),
            ]);
    }

    public function generateReport(): void
    {
        $data = $this->form->getState();

        if (blank($data['selectedDeviceId'] ?? null)) {
            return;
        }

        $device = Device::query()->with('user')->find($data['selectedDeviceId']);

        if (! $device) {
            return;
        }

        $reportService = $this->getReportService();
        [$startTimeMs, $endTimeMs] = $this->getTimeRange($data);
        $points = $reportService->getTracksForReport($data['selectedDeviceId'], $startTimeMs, $endTimeMs);

        logger()->info('GPS Report Result', ['count' => $points->count(), 'range' => [$startTimeMs, $endTimeMs]]);

        $this->reportPoints = $points->map(function (GpsTrack $track, int $index) use ($points, $reportService): array {
            $timeHuman = now()
                ->setTimestamp((int) ($track->time / 1000))
                ->setTimezone('America/Lima')
                ->format('d/m/Y H:i:s');

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

        // Segment tracks by time gaps (>5 min) to avoid connecting unrelated trips
        $segments = $reportService->segmentTracks($points);
        $this->reportSegments = array_map(function (Collection $segment): array {
            return $segment->map(fn (GpsTrack $track): array => [
                'latitude' => (float) $track->latitude,
                'longitude' => (float) $track->longitude,
            ])->all();
        }, $segments);

        $distance = $points->count() > 1 ? $reportService->calculateTotalDistance($points) : 0;
        $duration = $reportService->calculateDuration($points);

        $this->pointsCount = $points->count();
        $this->distanceFormatted = $reportService->formatDistance($distance);
        $this->durationFormatted = $reportService->formatDuration($duration);
        $this->reportGenerated = $points->count() > 0;
        $this->currentPage = 1;

        $this->dispatch('gps-report-generated', points: $this->reportPoints, segments: $this->reportSegments);

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

        $periodLabel = match ($data['dateFilter']) {
            'today' => 'Hoy ('.now()->setTimezone('America/Lima')->format('d/m/Y').')',
            'yesterday' => 'Ayer ('.now()->setTimezone('America/Lima')->subDay()->format('d/m/Y').')',
            'custom' => ($data['startDate'] ?? '').' → '.($data['endDate'] ?? ''),
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
        $data = $this->form->getState();

        if (blank($data['selectedDeviceId'] ?? null) || empty($this->reportPoints)) {
            return;
        }

        $reportService = $this->getReportService();

        $points = collect($this->reportPoints)->map(function (array $point): object {
            $obj = new \stdClass;
            $obj->latitude = $point['latitude'];
            $obj->longitude = $point['longitude'];
            $obj->accuracy = $point['accuracy'];
            $obj->time = $point['time'];

            return $obj;
        });

        $export = new GpsTrackExport($this->reportSummary, $points, $reportService);

        $fileName = 'recorrido_'.($this->reportSummary['imei'] ?? 'dispositivo').'_'.now()->format('Y-m-d_His').'.xlsx';

        Excel::download($export, $fileName);
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateReport')
                ->label('Generar Reporte')
                ->icon('heroicon-m-magnifying-glass')
                ->color('primary')
                ->action(fn () => $this->generateReport()),

            Action::make('exportToExcel')
                ->label('Exportar Excel')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('success')
                ->outlined()
                ->action(fn () => $this->exportToExcel())
                ->visible(fn () => $this->reportGenerated && ! empty($this->reportPoints)),
        ];
    }

    private function getReportService(): GpsRouteReportService
    {
        static $service;

        return $service ??= app(GpsRouteReportService::class);
    }

    private function getTimeRange(array $data): array
    {
        $tz = 'America/Lima';
        $dateFilter = $data['dateFilter'] ?? 'today';
        $startDate = $data['startDate'] ?? null;
        $endDate = $data['endDate'] ?? null;

        return match ($dateFilter) {
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
            'custom' => (function () use ($tz, $startDate, $endDate): array {
                $start = $startDate
                    ? Carbon::createFromFormat('Y-m-d', $startDate, $tz)->startOfDay()
                    : now()->setTimezone($tz)->startOfDay();
                $end = $endDate
                    ? Carbon::createFromFormat('Y-m-d', $endDate, $tz)->endOfDay()
                    : now()->setTimezone($tz)->endOfDay();

                return [
                    (int) $start->timestamp * 1000,
                    (int) $end->timestamp * 1000,
                ];
            })(),
        };
    }
}
