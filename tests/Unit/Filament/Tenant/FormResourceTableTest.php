<?php

declare(strict_types=1);

use App\Filament\Tenant\Resources\FormResource;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

it('builds the tenant form resource table without missing action classes', function () {
    $livewire = Mockery::mock(HasTable::class);
    $table = FormResource::table(Table::make($livewire));

    expect(array_keys($table->getFlatActions()))
        ->toContain('builder')
        ->toContain('edit');
});
