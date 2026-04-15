<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources\TenantResource\Pages;

use App\Filament\Central\Resources\TenantResource;
use App\Models\Central\Tenant;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Stancl\Tenancy\Jobs\DeleteDatabase;
use Throwable;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('deleteTenant')
                ->label('Borrar tenant')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('¿Borrar empresa (tenant)?')
                ->modalDescription('Esto eliminará la base de datos del tenant y su dominio. Esta acción no se puede deshacer.')
                ->modalSubmitActionLabel('Sí, borrar')
                ->action(function (): void {
                    /** @var Tenant $tenant */
                    $tenant = $this->getRecord();

                    try {
                        $dbName = $tenant->database()->getName();
                        $manager = $tenant->database()->manager();

                        if (filled($dbName) && $manager->databaseExists($dbName)) {
                            (new DeleteDatabase($tenant))->handle();
                        }

                        $tenant->domains()->delete();
                        $tenant->delete();
                    } catch (Throwable $e) {
                        report($e);

                        Notification::make()
                            ->danger()
                            ->title('No se pudo borrar el tenant')
                            ->body('Ocurrió un error al eliminar la base de datos o el dominio. Revisá los logs del servidor.')
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title('Tenant borrado')
                        ->body('Se eliminó el tenant, su dominio y su base de datos.')
                        ->send();

                    $this->redirect(static::getResource()::getUrl('index'));
                }),
        ];
    }

    /**
     * Inyectar el dominio actual en el form para que `primary_domain` sea editable.
     * El modelo Tenant tiene `primary_domain` como appended attribute (no columna),
     * así que Filament no lo carga automáticamente al hacer fill.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Tenant $record */
        $record = $this->getRecord();
        $data['primary_domain'] = $record->primary_domain ?? '';

        return $data;
    }

    /**
     * Al guardar en edit: actualizar name (y slug si viniera, aunque está disabled).
     * Si el dominio cambió, actualizar el primer domain del tenant.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Extraer primary_domain — no es columna del modelo Tenant, lo manejamos aparte
        $this->newDomain = $data['primary_domain'] ?? null;
        unset($data['primary_domain']);

        return $data;
    }

    /** @var string|null */
    private ?string $newDomain = null;

    protected function afterSave(): void
    {
        if (! $this->newDomain) {
            return;
        }

        /** @var Tenant $record */
        $record = $this->getRecord();

        $existingDomain = $record->domains()->first();

        if ($existingDomain) {
            // Actualizar el dominio existente si cambió
            if ($existingDomain->domain !== $this->newDomain) {
                $existingDomain->update(['domain' => $this->newDomain]);
            }
        } else {
            // Sin dominio aún (caso edge) — crear uno
            $record->domains()->create(['domain' => $this->newDomain]);
        }
    }
}
