<?php

declare(strict_types=1);

use App\Filament\Tenant\Resources\FormResource\Pages\ListForms;

it('exposes a create header action on the tenant forms list page', function () {
    $page = new class extends ListForms
    {
        public function exposeHeaderActions(): array
        {
            return $this->getHeaderActions();
        }
    };

    $actions = $page->exposeHeaderActions();

    expect(array_map(fn ($action) => $action->getName(), $actions))
        ->toContain('create');
});
