# API v1 - appseguimiento

> **Base URL:** `https://sitech.appseguimiento.test`
> **Prefijo:** `/api/v1/{tenant}/`
> **Autenticación:** Bearer Token (Sanctum)

---

## Autenticación

### Login
- **Método:** `POST`
- **Ruta:** `/api/v1/{tenant}/auth/login`
- **Headers:** `Content-Type: application/json`
- **Body:**
  ```json
  {
    "email": "operador@example.com",
    "password": "password"
  }
  ```
- **Response (200):**
  ```json
  {
    "token": "2|abc...",
    "user": {
      "id": "01k...",
      "name": "Nombre",
      "email": "operador@...",
      "role": "operator",
      "assignments_count": 2
    }
  }
  ```
- **Errores:**
  | Código | Significado |
  |--------|-------------|
  | 422 | Credenciales inválidas |
  | 403 | No es operador |

### Refresh Token
- **Método:** `POST`
- **Ruta:** `/api/v1/{tenant}/auth/refresh`
- **Headers:** `Authorization: Bearer {token}`
- **Body:** Ninguno
- **Response (200):**
  ```json
  { "token": "nuevo-token..." }
  ```
- **Rate limit:** 30/min
- **Errores:** 401 si el token es inválido/expirado

### Logout
- **Método:** `POST`
- **Ruta:** `/api/v1/{tenant}/auth/logout`
- **Headers:** `Authorization: Bearer {token}`
- **Body:** Ninguno
- **Response:** `204 No Content`
- **Nota:** Revoca el token actual permanentemente

---

## Perfil

### Me
- **Método:** `GET`
- **Ruta:** `/api/v1/{tenant}/me`
- **Headers:** `Authorization: Bearer {token}`
- **Response (200):**
  ```json
  {
    "data": {
      "id": "01k...",
      "name": "Nombre",
      "email": "user@...",
      "role": "operator",
      "assignments_count": 2
    }
  }
  ```

---

## Formularios

### Listar Formularios
- **Método:** `GET`
- **Ruta:** `/api/v1/{tenant}/forms`
- **Headers:** `Authorization: Bearer {token}`
- **Query params:** Ninguno
- **Response (200):**
  ```json
  {
    "data": [
      {
        "id": "01k...",
        "name": "Checklist Diaria",
        "description": "...",
        "is_active": true,
        "current_version": {
          "id": "01k...",
          "version_number": 1,
          "published_at": "2026-04-...",
          "schema": [
            {
              "name": "nombre",
              "type": "text",
              "label": "Nombre",
              "is_required": true,
              "validation_rules": []
            }
          ]
        },
        "assignment": {
          "assigned_at": "2026-04-...",
          "status": "active"
        }
      }
    ]
  }
  ```
- **Nota:** Operadores solo ven formularios con asignación activa. Admin/Supervisor ven todos.

### Detalle Formulario
- **Método:** `GET`
- **Ruta:** `/api/v1/{tenant}/forms/{form_id}`
- **Headers:** `Authorization: Bearer {token}`
- **Response (200):** Mismo formato que lista, un solo objeto.
- **Error 403:** Operador sin asignación activa (`ASSIGNMENT_REQUIRED`)

---

## Submissions

### Listar Submissions
- **Método:** `GET`
- **Ruta:** `/api/v1/{tenant}/submissions`
- **Headers:** `Authorization: Bearer {token}`
- **Query params (opcionales):**
  | Param | Valores | Ejemplo |
  |-------|---------|---------|
  | `status` | `draft`, `pending_photos`, `complete` | `?status=draft` |
  | `form_id` | ID de formulario | `?form_id=01k...` |
- **Paginado:** 15 por defecto, `?page=N`
- **Response (200):**
  ```json
  {
    "data": [
      {
        "id": "01k...",
        "form_version_id": "01k...",
        "status": "pending_photos",
        "submitted_at": "2026-04-...",
        "created_at": "2026-04-..."
      }
    ],
    "links": { ... },
    "meta": {
      "current_page": 1,
      "per_page": 15,
      "total": 1
    }
  }
  ```
- **Nota:** Operadores ven solo sus submissions. Admin/Supervisor ven todas.

### Crear Submission (Borrador)
- **Método:** `POST`
- **Ruta:** `/api/v1/{tenant}/submissions`
- **Headers:**
  ```
  Content-Type: application/json
  Authorization: Bearer {token}
  ```
- **Body:**
  ```json
  {
    "form_version_id": "01k...",
    "idempotency_key": "unico-key-001",
    "status": "draft",
    "responses": {
      "nombre": "Juan",
      "direccion": "Calle 123"
    }
  }
  ```
- **Campos para draft:** `latitude`, `longitude`, `responses` son **opcionales**.

### Crear Submission (Completa)
- **Método:** `POST`
- **Ruta:** `/api/v1/{tenant}/submissions`
- **Body:**
  ```json
  {
    "form_version_id": "01k...",
    "idempotency_key": "unico-key-002",
    "status": "pending_photos",
    "latitude": -12.046374,
    "longitude": -77.042793,
    "responses": {
      "nombre": "Juan",
      "direccion": "Calle 123"
    }
  }
  ```
- **Campos obligatorios:** `form_version_id`, `idempotency_key`, `status`, `latitude`, `longitude`, `responses`.
- **Response (201):**
  ```json
  {
    "data": {
      "id": "01k...",
      "form_version_id": "01k...",
      "user_id": "01k...",
      "idempotency_key": "unico-key-002",
      "latitude": -12.046374,
      "longitude": -77.042793,
      "status": "pending_photos",
      "submitted_at": "2026-04-...",
      "responses": [
        { "field_name": "nombre", "field_type": "text", "value": "Juan" }
      ]
    }
  }
  ```
- **Error 403:** Operador sin asignación activa (`ASSIGNMENT_REQUIRED`)
- **Idempotencia:** Si envías el mismo `idempotency_key`, devuelve la submission existente sin duplicar.

### Detalle Submission
- **Método:** `GET`
- **Ruta:** `/api/v1/{tenant}/submissions/{submission_id}`
- **Headers:** `Authorization: Bearer {token}`
- **Response (200):** Mismo formato que creación.
- **Error 403:** Si no eres el owner ni admin/supervisor.

### Actualizar Submission
- **Método:** `PATCH`
- **Ruta:** `/api/v1/{tenant}/submissions/{submission_id}`
- **Headers:**
  ```
  Content-Type: application/json
  Authorization: Bearer {token}
  ```
- **Body (actualizar coordenadas):**
  ```json
  {
    "latitude": -12.100000,
    "longitude": -77.100000
  }
  ```
- **Body (completar draft):**
  ```json
  {
    "status": "pending_photos"
  }
  ```
- **Body (actualizar respuestas):**
  ```json
  {
    "responses": {
      "nombre": "Pedro",
      "direccion": "Av. Principal 456"
    }
  }
  ```
- **Transiciones válidas:**
  | De | A |
  |----|---|
  | `draft` | `pending_photos` |
  | `pending_photos` | `draft` |
- **Error 422:** Transición no permitida (`INVALID_TRANSITION`)
- **Nota:** Solo el owner puede actualizar.

---

## Fotos

### Subir Foto
- **Método:** `POST`
- **Ruta:** `/api/v1/{tenant}/submissions/{submission_id}/photos`
- **Headers:** `Authorization: Bearer {token}`
- **Body → Form Data:**
  | Key | Type | Value |
  |-----|------|-------|
  | `photo` | File | Archivo de imagen (.jpg, .png) |
- **Response:** `202 Accepted`
- **Nota:** El procesamiento es asíncrono. La foto puede no aparecer inmediatamente.

### Listar Fotos
- **Método:** `GET`
- **Ruta:** `/api/v1/{tenant}/submissions/{submission_id}/photos`
- **Headers:** `Authorization: Bearer {token}`
- **Response (200):**
  ```json
  {
    "data": [
      {
        "id": "1",
        "file_name": "evidencia.jpg",
        "mime_type": "image/jpeg",
        "size": 12345,
        "created_at": "2026-04-..."
      }
    ]
  }
  ```

### Eliminar Foto
- **Método:** `DELETE`
- **Ruta:** `/api/v1/{tenant}/submissions/{submission_id}/photos/{media_id}`
- **Headers:** `Authorization: Bearer {token}`
- **Body:** Ninguno
- **Response:** `204 No Content`
- **Error 422:** Submission completa (`INVALID_STATUS`)
- **Error 403:** No eres el owner
- **Error 404:** Foto no encontrada

---

## Admin

### Ping
- **Método:** `GET`
- **Ruta:** `/api/v1/{tenant}/admin/ping`
- **Headers:** `Authorization: Bearer {token}`
- **Response (200):**
  ```json
  { "ok": true }
  ```
- **Error 403:** Solo usuarios con rol `admin`.

---

## Errores

Todos los errores siguen este formato:

```json
{
  "error": {
    "code": "ERROR_CODE",
    "message": "Descripción legible",
    "details": {} // Opcional, solo en validaciones
  }
}
```

### Códigos de error

| Código | HTTP | Significado |
|--------|------|-------------|
| `UNAUTHORIZED` | 401 | Token inválido, expirado o ausente |
| `NOT_AUTHORIZED` | 403 | Sin permiso para esta acción |
| `ROLE_NOT_ALLOWED` | 403 | Rol no permitido (solo operadores en login) |
| `VALIDATION_ERROR` | 422 | Datos de entrada inválidos |
| `NOT_FOUND` | 404 | Recurso no encontrado |
| `RATE_LIMIT_EXCEEDED` | 429 | Demasiadas solicitudes |
| `ASSIGNMENT_REQUIRED` | 403 | Operador sin asignación activa |
| `INVALID_STATUS` | 422 | Estado inválido para la operación |
| `INVALID_TRANSITION` | 422 | Transición de estado no permitida |

### Rate Limits

| Endpoint | Límite |
|----------|--------|
| Login | 5/min |
| Refresh | 30/min |
| Todos los demás | 60/min |

---

## Variables de entorno

Para configurar la API en producción:

```env
SANCTUM_EXPIRATION=60
API_RATE_LIMIT_USER=60
API_RATE_LIMIT_LOGIN=5
API_RATE_LIMIT_REFRESH=30
```

## Tenant

El `{tenant}` en las rutas debe coincidir con el **ID** del tenant en la base de datos (normalmente el slug). Se resuelve vía middleware `InitializeTenancyByPath`.

El subdominio del navegador no afecta la API — el tenant se determina por el path.

Ejemplo:
- `sitech.appseguimiento.test/api/v1/sitech/...` → tenant `sitech`
- `demo.appseguimiento.test/api/v1/demo/...` → tenant `demo`
