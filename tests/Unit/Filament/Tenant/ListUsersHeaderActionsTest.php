<?php

declare(strict_types=1);

use App\Filament\Tenant\Resources\UserResource\Pages\ListUsers;

it('exposes a create header action on the tenant users list page', function () {
    $page = new class extends ListUsers
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