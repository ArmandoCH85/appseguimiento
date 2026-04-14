<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\SubmissionResource\Pages;

use App\Enums\SubmissionStatus;
use App\Filament\Tenant\Resources\SubmissionResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListSubmissions extends ListRecords
{
    protected static string $resource = SubmissionResource::class;

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->with(['formVersion.form', 'user', 'responses'])
            ->withCount('responses');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Exportar a Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => $this->exportSubmissions()),
        ];
    }

    protected function exportSubmissions(): StreamedResponse
    {
        $filename = 'respuestas-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            // Obtener query respetando filtros activos de la tabla
            $query = $this->getTable()->getQuery()
                ->with(['formVersion.form', 'user'])
                ->withCount('responses');

            $handle = fopen('php://output', 'w');

            // Escribir BOM UTF-8 para Excel
            fwrite($handle, "\xEF\xBB\xBF");

            // Headers del CSV
            fputcsv($handle, [
                'Formulario',
                'Usuario',
                'Email',
                'Estado',
                'Fecha de Envío',
                'Cantidad de Respuestas',
                'Respuestas',
            ], ';', '"');

            // Iterar con cursor() para eficiencia de memoria
            foreach ($query->cursor() as $submission) {
                // Formatear respuestas como "Campo: Valor; Campo: Valor"
                $respuestasTexto = $this->formatResponses($submission->responses);

                fputcsv($handle, [
                    $submission->formVersion?->form?->name ?? '',
                    $submission->user?->name ?? 'Anónimo',
                    $submission->user?->email ?? '',
                    $this->getStatusLabel($submission->status),
                    $submission->submitted_at?->format('d/m/Y H:i') ?? '',
                    $submission->responses_count ?? 0,
                    $respuestasTexto,
                ], ';', '"');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Obtiene el label legible del estado.
     */
    protected function getStatusLabel(SubmissionStatus $status): string
    {
        return match ($status) {
            SubmissionStatus::Draft => 'Borrador',
            SubmissionStatus::PendingPhotos => 'Pendiente de Fotos',
            SubmissionStatus::Complete => 'Completado',
            default => 'Desconocido',
        };
    }

    /**
     * Formatea las respuestas de una submission como texto legible.
     * Ejemplo: "Pregunta1: Sí; Pregunta2: 5; Pregunta3: Bueno"
     *
     * @param  Collection  $responses
     */
    protected function formatResponses($responses): string
    {
        if ($responses->isEmpty()) {
            return '';
        }

        $formatted = [];

        foreach ($responses as $response) {
            $label = $response->field_name;
            $value = $response->value;

            // Si el valor es JSON (checkbox múltiple), decodificarlo
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = implode(', ', $decoded);
            }

            // Limpiar el valor para el CSV
            $value = str_replace(["\r\n", "\r", "\n", ';'], [' ', ' ', ' ', ','], $value);
            $value = trim($value);

            if ($value !== '' && $value !== null) {
                $formatted[] = $label.': '.$value;
            }
        }

        return implode('; ', $formatted);
    }
}
