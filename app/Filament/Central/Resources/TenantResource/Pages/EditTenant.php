<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources\TenantResource\Pages;

use App\Filament\Central\Resources\TenantResource;
use App\Models\Central\Tenant;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
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
