<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources\TenantResource\Pages;

use App\Filament\Central\Resources\TenantResource;
use App\Models\Central\Tenant;
use App\Models\Tenant\User as TenantUser;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
            Action::make('changeAdminPassword')
                ->label('Cambiar clave')
                ->icon('heroicon-o-key')
                ->color('gray')
                ->modalHeading('Cambiar clave del administrador del tenant')
                ->modalDescription('Se actualizará la contraseña del usuario admin dentro de la base del tenant.')
                ->form(function (): array {
                    /** @var Tenant $tenant */
                    $tenant = $this->getRecord();

                    $adminUsers = $tenant->run(fn () => TenantUser::query()
                        ->whereHas('roles', fn ($q) => $q->where('name', 'admin'))
                        ->orderBy('created_at')
                        ->get()
                        ->mapWithKeys(fn (TenantUser $u) => [(string) $u->getKey() => "{$u->name} · {$u->email}"])
                        ->all()
                    );

                    $defaultUserId = array_key_first($adminUsers);

                    return [
                        Select::make('user_id')
                            ->label('Usuario admin')
                            ->options($adminUsers)
                            ->required()
                            ->default($defaultUserId)
                            ->searchable()
                            ->native(false),
                        TextInput::make('new_password')
                            ->label('Nueva contraseña')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8)
                            ->confirmed(),
                        TextInput::make('new_password_confirmation')
                            ->label('Confirmar contraseña')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8),
                    ];
                })
                ->action(function (array $data): void {
                    /** @var Tenant $tenant */
                    $tenant = $this->getRecord();

                    $updated = $tenant->run(function () use ($data): bool {
                        $user = TenantUser::query()->find($data['user_id'] ?? null);

                        if (! $user) {
                            return false;
                        }

                        $user->password = (string) ($data['new_password'] ?? '');
                        $user->save();

                        return true;
                    });

                    if (! $updated) {
                        Notification::make()
                            ->danger()
                            ->title('No se pudo cambiar la clave')
                            ->body('No se encontró el usuario admin en este tenant.')
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title('Clave actualizada')
                        ->body('La contraseña del usuario admin fue actualizada correctamente.')
                        ->send();
                }),
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
