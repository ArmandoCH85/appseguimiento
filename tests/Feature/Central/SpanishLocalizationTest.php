<?php

declare(strict_types=1);

use App\Filament\Central\Resources\CentralUserResource;
use App\Filament\Central\Resources\TenantResource;
use App\Filament\Tenant\Resources\FormAssignmentResource;
use App\Filament\Tenant\Resources\FormResource;
use App\Filament\Tenant\Resources\SubmissionResource;
use App\Filament\Tenant\Resources\UserResource;

// ──────────────────────────────────────────────
// Requisito 1 — Panel Central en español
// ──────────────────────────────────────────────

describe('TenantResource labels in Spanish', function () {
    it('returns "Empresa" as singular label', function () {
        expect(TenantResource::getModelLabel())->toBe('Empresa');
    });

    it('returns "Empresas" as plural label', function () {
        expect(TenantResource::getPluralModelLabel())->toBe('Empresas');
    });

    it('returns "Empresas" as navigation label', function () {
        expect(TenantResource::getNavigationLabel())->toBe('Empresas');
    });
});

// ──────────────────────────────────────────────
// Requisito 2 — Panel Tenant en español
// ──────────────────────────────────────────────

describe('UserResource labels in Spanish', function () {
    it('returns "Usuario" as singular label', function () {
        expect(UserResource::getModelLabel())->toBe('Usuario');
    });

    it('returns "Usuarios" as plural label', function () {
        expect(UserResource::getPluralModelLabel())->toBe('Usuarios');
    });

    it('returns "Usuarios" as navigation label', function () {
        expect(UserResource::getNavigationLabel())->toBe('Usuarios');
    });
});

describe('FormResource labels in Spanish', function () {
    it('returns "Formulario" as singular label', function () {
        expect(FormResource::getModelLabel())->toBe('Formulario');
    });

    it('returns "Formularios" as plural label', function () {
        expect(FormResource::getPluralModelLabel())->toBe('Formularios');
    });

    it('returns "Formularios" as navigation label', function () {
        expect(FormResource::getNavigationLabel())->toBe('Formularios');
    });
});

describe('FormAssignmentResource labels in Spanish', function () {
    it('returns "Asignación" as singular label', function () {
        expect(FormAssignmentResource::getModelLabel())->toBe('Asignación');
    });

    it('returns "Asignaciones" as plural label', function () {
        expect(FormAssignmentResource::getPluralModelLabel())->toBe('Asignaciones');
    });

    it('returns "Asignaciones" as navigation label', function () {
        expect(FormAssignmentResource::getNavigationLabel())->toBe('Asignaciones');
    });
});

describe('SubmissionResource labels in Spanish', function () {
    it('returns "Respuesta" as singular label', function () {
        expect(SubmissionResource::getModelLabel())->toBe('Respuesta');
    });

    it('returns "Respuestas" as plural label', function () {
        expect(SubmissionResource::getPluralModelLabel())->toBe('Respuestas');
    });

    it('returns "Respuestas" as navigation label', function () {
        expect(SubmissionResource::getNavigationLabel())->toBe('Respuestas');
    });
});

describe('CentralUserResource labels in Spanish', function () {
    it('returns "Usuario Central" as singular label', function () {
        expect(CentralUserResource::getModelLabel())->toBe('Usuario Central');
    });

    it('returns "Usuarios Centrales" as plural label', function () {
        expect(CentralUserResource::getPluralModelLabel())->toBe('Usuarios Centrales');
    });

    it('returns "Usuarios Centrales" as navigation label', function () {
        expect(CentralUserResource::getNavigationLabel())->toBe('Usuarios Centrales');
    });
});

// ──────────────────────────────────────────────
// Requisito 3 — Mensajes de validación en español
// ──────────────────────────────────────────────

describe('Validation messages in Spanish', function () {
    beforeEach(function () {
        app()->setLocale('es');
    });

    it('shows required validation message in Spanish', function () {
        $validator = app('validator')->make(
            ['nombre' => ''],
            ['nombre' => 'required']
        );

        expect($validator->errors()->first('nombre'))
            ->toContain('obligatorio');
    });

    it('shows email validation message in Spanish', function () {
        $validator = app('validator')->make(
            ['email' => 'not-an-email'],
            ['email' => 'email']
        );

        expect($validator->errors()->first('email'))
            ->toContain('válido');
    });

    it('shows min validation message in Spanish', function () {
        $validator = app('validator')->make(
            ['contrasena' => 'abc'],
            ['contrasena' => 'min:8']
        );

        expect($validator->errors()->first('contrasena'))
            ->toContain('menos');
    });
});
