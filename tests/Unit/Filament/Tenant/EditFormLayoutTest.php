<?php

declare(strict_types=1);

use App\Filament\Tenant\Resources\FormResource\Pages\EditForm;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('tenant'));
});

it('exposes workspace header actions on the edit form page', function () {
    $page = new class extends EditForm
    {
        public function exposeHeaderActions(): array
        {
            return $this->getHeaderActions();
        }
    };

    expect(array_map(fn ($action) => $action->getName(), $page->exposeHeaderActions()))
        ->toContain('builder')
        ->toContain('preview')
        ->toContain('index');
});

it('uses a workspace schema for basic form details', function () {
    $page = new class extends EditForm
    {
        public function exposeWorkspaceSchema(Schema $schema): Schema
        {
            return $this->form($schema);
        }
    };

    $schema = $page->exposeWorkspaceSchema(Schema::make($page));
    $components = $schema->getComponents();

    expect($schema->getColumns())->toMatchArray([
        'default' => 1,
        'xl' => 12,
    ]);

    expect($components)->toHaveCount(2);

    expect($components[0])
        ->toBeInstanceOf(Section::class)
        ->and($components[0]->getHeading())->toBe('Datos básicos')
        ->and($components[0]->getColumnSpan('xl'))->toBe(8);

    expect($components[1])
        ->toBeInstanceOf(Group::class)
        ->and($components[1]->getColumnSpan('xl'))->toBe(4);

    $asideHeadings = array_map(
        fn ($component) => $component->getHeading(),
        $components[1]->getChildSchema()->getComponents(),
    );

    expect($asideHeadings)->toBe([
        'Estado del formulario',
        'Versión actual',
    ]);
});