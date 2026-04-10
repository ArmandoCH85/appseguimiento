<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FormResource\Pages;

use App\Filament\Tenant\Resources\FormResource;
use App\Services\FormVersionService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class BuildForm extends EditForm
{
    protected Width | string | null $maxContentWidth = Width::Full;

    public function getTitle(): string
    {
        return 'Constructor del formulario';
    }

    public function form(Schema $schema): Schema
    {
        return FormResource::builderForm($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editDetails')
                ->label('Editar datos básicos')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(fn (): string => static::getResource()::getUrl('edit', ['record' => $this->getRecord()])),
            Action::make('preview')
                ->label('Vista previa')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->url(fn (): string => static::getResource()::getUrl('preview', ['record' => $this->getRecord()])),
            Action::make('publish')
                ->label('Publicar versión')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('¿Publicar nueva versión?')
                ->modalDescription('Se va a crear una nueva versión con las preguntas actuales. Los operadores verán esta versión en sus formularios.')
                ->modalSubmitActionLabel('Sí, publicar')
                ->action(function (): void {
                    app(FormVersionService::class)->publish($this->getRecord());
                    $this->refreshFormData(['current_version_id']);
                    Notification::make()
                        ->title('Versión publicada')
                        ->body('Los operadores ya pueden ver los cambios.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getSubheading(): ?string
    {
        return 'Acá agregás y organizás las preguntas que verá el usuario final.';
    }
}
