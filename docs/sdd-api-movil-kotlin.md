# SDD Tasks: api-movil-kotlin

> Pendiente de implementar desde sdd-apply. 34 tareas en 6 fases.

## Phase 1: Foundation (enum, config, middleware, migration, error handler)

- [ ] 1.1 Add `Draft = 'draft'` case to `app/Enums/SubmissionStatus.php`
- [ ] 1.2 Set `'expiration' => env('SANCTUM_EXPIRATION', 60)` in `config/sanctum.php`
- [ ] 1.3 Create `app/Http/Middleware/EnsureOperatorRole.php` — reject non-operator with 403 `ROLE_NOT_ALLOWED`
- [ ] 1.4 Create `app/Exceptions/ApiExceptionHandler.php` — map auth/authz/validation/model-not-found/throttle exceptions to `{error: {code, message, details?}}`
- [ ] 1.5 Register `ApiExceptionHandler` in `bootstrap/app.php` for API-prefix routes
- [ ] 1.6 Update `EnsureTenantApiAccess` middleware to return JSON `{error: {code, message}}` instead of `abort()`
- [ ] 1.7 Create `config/api.php` with rate-limit defaults (60/min user, 5/min IP login, 30/min refresh) and error-code map
- [ ] 1.8 Create tenant migration `make_lat_lng_nullable_on_submissions` — change latitude/longitude columns to nullable
- [ ] 1.9 Make `latitude`, `longitude`, `submitted_at` nullable in `Submission` model `$fillable` and handle Draft casting

## Phase 2: Auth (login, refresh, logout, me)

- [ ] 2.1 Add `refresh()` method to `AuthController` — delete current token, issue new one, return `{token}`; apply `throttle:30,1`
- [ ] 2.2 Modify `AuthController@login` — wrap with `EnsureOperatorRole` middleware on route; add role + assignments_count to user response
- [ ] 2.3 Extract `/me` inline closure into `app/Http/Controllers/Api/V1/MeController.php` — return `{id, name, email, role, assignments_count}`
- [ ] 2.4 Create `app/Http/Resources/Api/V1/UserResource.php` for MeController response (MVP) — or inline in controller
- [ ] 2.5 Change `AuthController@logout` return to 204 no-content (already done — verify)

## Phase 3: Routes refactor

- [ ] 3.1 Refactor `routes/api.php` — prefix `/api/v1/{tenant}/`, group middleware: public (login + EnsureOperatorRole + throttle:5,1), authenticated (auth:sanctum + EnsureTenantApiAccess + throttle:60,1)
- [ ] 3.2 Wire all 13 endpoints in route file pointing to correct controller methods

## Phase 4: Forms (assignment validation, resource metadata)

- [ ] 4.1 Add assignment check in `FormController@show` — if operator without active assignment → 403 `ASSIGNMENT_REQUIRED`; admin/supervisor bypass
- [ ] 4.2 Eager-load `assignments` relation in `FormController@index` for operator's assigned forms
- [ ] 4.3 Add `assignment` key to `FormResource` — `{assigned_at, status: "active"|"revoked"}` for operator; null for admin/supervisor

## Phase 5: Submissions (draft flow, CRUD, listing, detail)

- [ ] 5.1 Modify `StoreSubmissionRequest` — make `latitude`, `longitude`, `responses` nullable when `status=draft`; add `status` field (in: draft,pending_photos)
- [ ] 5.2 Create `app/Http/Requests/Api/UpdateSubmissionRequest.php` — validate PATCH: optional status, lat, lng, responses; status transition draft→pending_photos only
- [ ] 5.3 Update `SubmissionService::createOrRetrieve` — support `status=draft`: skip required validation for lat/lng/responses; set `submitted_at` only for non-draft
- [ ] 5.4 Add `updateSubmission(Submission, User, array)` to `SubmissionServiceContract` and `SubmissionService` — validate status transition, partial responses update, lat/lng update
- [ ] 5.5 Add `index()` to `SubmissionController` — operators see own submissions; admin/supervisor see all; filter by status & form_id; paginated (15 default)
- [ ] 5.6 Add `show()` to `SubmissionController` — owner-or-admin check; return full SubmissionResource with responses
- [ ] 5.7 Add `update()` to `SubmissionController` — owner check; dispatch to SubmissionService::updateSubmission
- [ ] 5.8 Add assignment check in `SubmissionController@store` — verify operator has active assignment for form before creating
- [ ] 5.9 Create `app/Http/Resources/Api/V1/SubmissionListResource.php` — lightweight: id, form_version_id, status, submitted_at (no responses)
- [ ] 5.10 Update `SubmissionResource` — handle nullable lat/lng/responses for drafts; add `_photo_count` when loaded

## Phase 6: Photos (upload unchanged, list, delete, verify)

- [ ] 6.1 Create `app/Http/Controllers/Api/V1/PhotoController.php` with `index()` and `destroy()` methods
- [ ] 6.2 `PhotoController@index` — list photos for a submission (owner-or-admin check); return collection
- [ ] 6.3 `PhotoController@destroy` — owner + pending_photos status check; delete media; completed submission → 422 `INVALID_STATUS`
- [ ] 6.4 Create `app/Http/Resources/Api/V1/PhotoResource.php` — id, file_name, mime_type, size, created_at
- [ ] 6.5 Wire photo routes: `GET /submissions/{submission}/photos`, `DELETE /submissions/{submission}/photos/{media}`

## 13 Endpoints

| # | Method | Route | Purpose |
|---|--------|-------|---------|
| 1 | POST | /api/v1/{tenant}/auth/login | Operator login |
| 2 | POST | /api/v1/{tenant}/auth/refresh | Token refresh |
| 3 | POST | /api/v1/{tenant}/auth/logout | Revoke token |
| 4 | GET | /api/v1/{tenant}/me | User profile |
| 5 | GET | /api/v1/{tenant}/forms | Assigned forms list |
| 6 | GET | /api/v1/{tenant}/forms/{form} | Form detail + schema |
| 7 | POST | /api/v1/{tenant}/submissions | Create submission |
| 8 | GET | /api/v1/{tenant}/submissions | List submissions |
| 9 | GET | /api/v1/{tenant}/submissions/{submission} | Submission detail |
| 10 | PATCH | /api/v1/{tenant}/submissions/{submission} | Update draft |
| 11 | POST | /api/v1/{tenant}/submissions/{submission}/photos | Upload photo |
| 12 | GET | /api/v1/{tenant}/submissions/{submission}/photos | List photos |
| 13 | DELETE | /api/v1/{tenant}/submissions/{submission}/photos/{media} | Delete photo |

## Files: New (9)

1. `app/Http/Middleware/EnsureOperatorRole.php`
2. `app/Http/Controllers/Api/V1/MeController.php`
3. `app/Http/Controllers/Api/V1/PhotoController.php`
4. `app/Http/Resources/Api/V1/FormResource.php`
5. `app/Http/Resources/Api/V1/SubmissionResource.php`
6. `app/Http/Resources/Api/V1/PhotoResource.php`
7. `app/Http/Resources/Api/V1/UserResource.php`
8. `app/Http/Requests/Api/V1/StoreSubmissionRequest.php`
9. `app/Exceptions/ApiExceptionHandler.php`

## Files: Modified (9)

1. `routes/api.php`
2. `app/Http/Controllers/Api/AuthController.php`
3. `app/Http/Controllers/Api/FormController.php`
4. `app/Http/Controllers/Api/SubmissionController.php`
5. `app/Enums/SubmissionStatus.php`
6. `app/Services/SubmissionService.php`
7. `config/sanctum.php`
8. `app/Models/Tenant/Submission.php`
9. `database/migrations/tenant/` (new migration)