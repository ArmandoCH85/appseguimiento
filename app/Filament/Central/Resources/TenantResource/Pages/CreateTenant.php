<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources\TenantResource\Pages;

use App\Actions\Tenant\CreateTenantAction;
use App\Filament\Central\Resources\TenantResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    /** Guardados aquí porque estos campos no son columnas del modelo Tenant. */
    private string $adminEmail    = '';
    private string $adminPassword = '';

    /**
     * Interceptamos los datos ANTES de que lleguen a handleRecordCreation.
     *
     * Aquí:
     * 1. Extraemos admin_email y admin_password (campos virtuales del formulario)
     * 2. Ensamblamos primary_domain desde el subdominio + dominio base del sistema
     * 3. Limpiamos campos virtuales para que no lleguen al modelo Tenant
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->adminEmail    = $data['admin_email']    ?? '';
        $this->adminPassword = $data['admin_password'] ?? '';

        // Ensamblar dominio completo: "empresa" + ".appseguimiento.test"
        $subdomain = $data['subdomain'] ?? $data['slug'] ?? '';
        $baseDomain = TenantResource::getBaseDomain();
        $data['primary_domain'] = "{$subdomain}.{$baseDomain}";

        unset($data['admin_email'], $data['admin_password'], $data['subdomain']);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateTenantAction::class)->execute([
            ...$data,
            'admin_email'    => $this->adminEmail,
            'admin_password' => $this->adminPassword,
        ]);
    }
}
