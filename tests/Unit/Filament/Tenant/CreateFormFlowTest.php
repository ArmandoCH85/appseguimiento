<?php

declare(strict_types=1);

use App\Filament\Tenant\Resources\FormResource\Pages\BuildForm;
use App\Filament\Tenant\Resources\FormResource\Pages\CreateForm;
use App\Filament\Tenant\Resources\FormResource\Pages\EditForm;
use App\Models\Tenant\Form;
use Filament\Facades\Filament;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('tenant'));
});

it('redirects new forms to the builder after creation', function () {
    $page = new class extends CreateForm
    {
        public function setTestRecord(Form $record): void
        {
            $this->record = $record;
        }

        public function exposeRedirectUrl(): string
        {
            return $this->getRedirectUrl();
        }
    };

    $record = new Form();
    $record->id = '01kntz6j6mp1yfr4f7hy4psw4a';

    $page->setTestRecord($record);

    expect($page->exposeRedirectUrl())->toContain('/app/forms/01kntz6j6mp1yfr4f7hy4psw4a/builder');
});

it('exposes a builder action on the basic form edit page', function () {
    $page = new class extends EditForm
    {
        public function exposeHeaderActions(): array
        {
            return $this->getHeaderActions();
        }
    };

    expect(array_map(fn ($action) => $action->getName(), $page->exposeHeaderActions()))
        ->toContain('builder');
});

it('exposes edit-details and publish actions on the builder page', function () {
    $page = new class extends BuildForm
    {
        public function exposeHeaderActions(): array
        {
            return $this->getHeaderActions();
        }
    };

    expect(array_map(fn ($action) => $action->getName(), $page->exposeHeaderActions()))
        ->toContain('editDetails')
        ->toContain('publish');
});

it('uses a friendly spanish title for the builder page', function () {
    expect((new BuildForm())->getTitle())->toBe('Constructor del formulario');
});
