<?php

declare(strict_types=1);

namespace App\Support;

final class TenantPermissionCatalog
{
    /**
     * @return array<string, array<string, string>>
     */
    public static function groupedPermissionLabels(): array
    {
        return [
            'Formularios' => [
                'forms.view' => 'Ver formularios',
                'forms.create' => 'Crear formularios',
                'forms.update' => 'Editar formularios',
                'forms.delete' => 'Eliminar formularios',
                'forms.publish' => 'Publicar versiones',
            ],
            'Asignaciones' => [
                'assignments.view' => 'Ver asignaciones',
                'assignments.manage' => 'Gestionar asignaciones',
            ],
            'Respuestas' => [
                'submissions.view' => 'Ver respuestas',
                'submissions.create' => 'Crear respuestas',
                'submissions.upload_photos' => 'Subir fotos',
            ],
            'Usuarios' => [
                'users.view' => 'Ver usuarios',
                'users.manage' => 'Gestionar usuarios',
            ],
            'Reportes' => [
                'reports.view' => 'Ver reportes',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function permissions(): array
    {
        return array_keys(self::permissionLabels());
    }

    /**
     * @return array<string, string>
     */
    public static function permissionLabels(): array
    {
        return array_merge(...array_values(self::groupedPermissionLabels()));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function rolePermissions(): array
    {
        return [
            'admin' => self::permissions(),
            'supervisor' => [
                'forms.view',
                'assignments.view',
                'submissions.view',
                'users.view',
                'reports.view',
            ],
            'operator' => [
                'forms.view',             // Ver lista de formularios para poder responderlos
                'submissions.create',
                'submissions.upload_photos',
            ],
        ];
    }
}
