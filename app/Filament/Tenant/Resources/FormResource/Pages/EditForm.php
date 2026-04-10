<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FormResource\Pages;

use App\Filament\Tenant\Resources\FormResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class EditForm extends EditRecord
{
    protected static string $resource = FormResource::class;

    protected Width | string | null $maxContentWidth = Width::Full;

    public function form(Schema $schema): Schema
    {
        return FormResource::editDetailsForm($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('builder')
                ->label('Abrir constructor')
                ->icon('heroicon-o-pencil-square')
                ->color('primary')
                ->url(fn (): string => static::getResource()::getUrl('builder', ['record' => $this->getRecord()])),
            Action::make('preview')
                ->label('Vista previa')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->url(fn (): string => static::getResource()::getUrl('preview', ['record' => $this->getRecord()])),
            Action::make('index')
                ->label('Volver al listado')
                ->icon('heroicon-o-queue-list')
                ->color('gray')
                ->url(fn (): string => static::getResource()::getUrl('index')),
        ];
    }

    public function getTitle(): string
    {
        return 'Editar formulario';
    }

    public function getSubheading(): ?string
    {
        return 'Actualizá el contexto general del formulario. Las preguntas y opciones se administran desde el constructor.';
    }
}