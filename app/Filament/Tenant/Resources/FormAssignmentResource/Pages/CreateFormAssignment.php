<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FormAssignmentResource\Pages;

use App\Filament\Tenant\Resources\FormAssignmentResource;
use App\Models\Tenant\FormAssignment;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class CreateFormAssignment extends CreateRecord
{
    protected static string $resource = FormAssignmentResource::class;

    public function form(Schema $schema): Schema
    {
        return FormAssignmentResource::createForm($schema);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $userIds = $data['user_ids'];
        $assignedAt = $data['assigned_at'];
        $formId = $data['form_id'];

        $created = 0;
        $skipped = 0;
        $first = null;

        foreach ($userIds as $userId) {
            $alreadyAssigned = FormAssignment::query()
                ->where('form_id', $formId)
                ->where('user_id', $userId)
                ->whereNull('revoked_at')
                ->exists();

            if ($alreadyAssigned) {
                $skipped++;
                continue;
            }

            $assignment = FormAssignment::create([
                'form_id' => $formId,
                'user_id' => $userId,
                'assigned_at' => $assignedAt,
            ]);

            $first ??= $assignment;
            $created++;
        }

        $message = match (true) {
            $created > 0 && $skipped > 0 => "{$created} asignación(es) creada(s). {$skipped} usuario(s) ya tenían el formulario asignado.",
            $created > 0 => "{$created} asignación(es) creada(s) correctamente.",
            default => 'Todos los usuarios seleccionados ya tenían este formulario asignado.',
        };

        $notification = Notification::make()
            ->title($created > 0 ? 'Asignaciones creadas' : 'Sin cambios')
            ->body($message);

        $created > 0 ? $notification->success() : $notification->warning();

        $notification->send();

        return $first ?? new FormAssignment();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    public function getTitle(): string
    {
        return 'Asignar formulario a usuarios';
    }

    public function getSubheading(): ?string
    {
        return 'Seleccioná el formulario y todos los usuarios que deben completarlo. Se ignoran los que ya lo tienen asignado.';
    }
}
