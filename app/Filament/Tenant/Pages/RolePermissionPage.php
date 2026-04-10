<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Pages;

use App\Support\TenantPermissionCatalog;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * @property-read Schema $form
 */
class RolePermissionPage extends Page
{
    public ?array $data = [];

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Permisos';

    protected static string|\UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 50;

    protected static ?string $slug = 'permissions';

    protected Width | string | null $maxContentWidth = Width::Full;

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $defaultRoleId = Role::query()
            ->orderByRaw("name = 'admin' desc")
            ->orderBy('name')
            ->value('id');

        $this->fillForm($defaultRoleId);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermissionTo('users.manage') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getTitle(): string
    {
        return 'Permisos por rol';
    }

    public function getSubheading(): ?string
    {
        return 'Definí qué puede hacer cada rol dentro del tenant. Los cambios impactan en policies y navegación.';
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Rol a configurar')
                ->description('Elegí el rol que querés revisar o ajustar.')
                ->schema([
                    Select::make('role_id')
                        ->label('Rol')
                        ->options($this->getRoleOptions())
                        ->required()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function (?string $state, callable $set): void {
                            $set('permissions_by_domain', $this->groupPermissionState($this->getRolePermissionState($state)));
                        }),
                ]),
            Section::make('Permisos disponibles')
                ->description(fn (): string => $this->isLockedRoleId($this->data['role_id'] ?? null)
                    ? 'El rol admin está protegido. Se muestra en modo solo lectura para evitar dejar sin acceso al tenant.'
                    : 'Marcá los permisos que querés habilitar para el rol seleccionado.')
                ->schema($this->buildPermissionCheckboxLists()),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $role = Role::query()->findOrFail($data['role_id']);

        if ($this->isLockedRole($role)) {
            Notification::make()
                ->warning()
                ->title('El rol admin está protegido')
                ->body('No se pueden editar sus permisos desde esta pantalla.')
                ->send();

            $this->fillForm($role->getKey());

            return;
        }

        $this->syncRolePermissions($role, $this->flattenPermissionState($data['permissions_by_domain'] ?? []));

        Notification::make()
            ->success()
            ->title('Permisos actualizados')
            ->body("El rol {$role->name} ya usa la nueva configuración.")
            ->send();

        $this->fillForm($role->getKey());

        if ($redirectUrl = $this->getRedirectUrl()) {
            $this->redirect($redirectUrl, navigate: FilamentView::hasSpaMode($redirectUrl));
        }
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar permisos')
                ->submit('save')
                ->color('primary')
                ->keyBindings(['mod+s']),
        ];
    }

    protected function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment(Alignment::Start)
                    ->fullWidth()
                    ->sticky()
                    ->key('form-actions'),
            ]);
    }

    protected function getRedirectUrl(): ?string
    {
        return null;
    }

    /**
     * @return array<string, string>
     */
    protected function getRoleOptions(): array
    {
        return Role::query()
            ->orderByRaw("name = 'admin' desc")
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Role $role): array => [
                (string) $role->getKey() => str($role->name)->replace('-', ' ')->headline()->toString(),
            ])
            ->all();
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected function getPermissionOptions(): array
    {
        return TenantPermissionCatalog::groupedPermissionLabels();
    }

    /**
     * @return array<int, string>
     */
    protected function getRolePermissionState(int | string | null $roleId): array
    {
        if (blank($roleId)) {
            return [];
        }

        return Role::query()
            ->with('permissions')
            ->find($roleId)
            ?->permissions
            ->pluck('name')
            ->values()
            ->all() ?? [];
    }

    /**
     * @return array<int, CheckboxList>
     */
    protected function buildPermissionCheckboxLists(): array
    {
        $components = [];

        foreach ($this->getPermissionOptions() as $domain => $options) {
            $components[] = CheckboxList::make('permissions_by_domain.' . $this->domainKey($domain))
                ->label($domain)
                ->options($options)
                ->columns(2)
                ->bulkToggleable()
                ->gridDirection('row')
                ->disabled(fn (callable $get): bool => $this->isLockedRoleId($get('role_id')));
        }

        return $components;
    }

    protected function fillForm(int | string | null $roleId): void
    {
        $this->form->fill([
            'role_id' => $roleId ? (string) $roleId : null,
            'permissions_by_domain' => $this->groupPermissionState($this->getRolePermissionState($roleId)),
        ]);
    }

    protected function syncRolePermissions(Role $role, array $permissions): void
    {
        if ($this->isLockedRole($role)) {
            return;
        }

        $role->syncPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function isLockedRole(Role $role): bool
    {
        return $role->name === 'admin';
    }

    protected function isLockedRoleId(int | string | null $roleId): bool
    {
        if (blank($roleId)) {
            return false;
        }

        return Role::query()
            ->whereKey($roleId)
            ->where('name', 'admin')
            ->exists();
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array<string, array<int, string>>
     */
    protected function groupPermissionState(array $permissions): array
    {
        $groupedState = [];

        foreach ($this->getPermissionOptions() as $domain => $options) {
            $domainPermissions = array_keys($options);

            $groupedState[$this->domainKey($domain)] = array_values(array_intersect($permissions, $domainPermissions));
        }

        return $groupedState;
    }

    /**
     * @param  array<string, array<int, string> | null>  $groupedPermissions
     * @return array<int, string>
     */
    protected function flattenPermissionState(array $groupedPermissions): array
    {
        return collect($groupedPermissions)
            ->filter(fn ($permissions): bool => is_array($permissions))
            ->flatMap(fn (array $permissions): array => $permissions)
            ->values()
            ->unique()
            ->all();
    }

    protected function domainKey(string $domain): string
    {
        return Str::slug($domain, '_');
    }
}
