# Manual de Usuario — AppSeguimiento

Este manual explica el uso de todos los módulos disponibles en el sistema para un usuario final. El sistema está organizado en “paneles” (según el tipo de acceso) y, dentro del panel del tenant (empresa), en módulos por menú.

## Contenido

- [1. Conceptos clave](#1-conceptos-clave)
- [2. Acceso al sistema](#2-acceso-al-sistema)
  - [2.1 URLs y redirección](#21-urls-y-redirección)
  - [2.2 Inicio de sesión](#22-inicio-de-sesión)
  - [2.3 Menú y visibilidad por permisos](#23-menú-y-visibilidad-por-permisos)
- [3. Roles y permisos](#3-roles-y-permisos)
  - [3.1 Roles del tenant](#31-roles-del-tenant)
  - [3.2 Permisos disponibles (catálogo)](#32-permisos-disponibles-catálogo)
- [4. Panel del Tenant (Empresa) — /app](#4-panel-del-tenant-empresa--app)
  - [4.1 Monitoreo GPS](#41-monitoreo-gps)
    - [4.1.1 Dispositivos](#411-dispositivos)
    - [4.1.2 Mapa GPS](#412-mapa-gps)
    - [4.1.3 Reporte de Recorrido](#413-reporte-de-recorrido)
    - [4.1.4 Rastreo GPS (tabla y detalle)](#414-rastreo-gps-tabla-y-detalle)
  - [4.2 Formularios](#42-formularios)
    - [4.2.1 Formularios (administración)](#421-formularios-administración)
    - [4.2.2 Constructor del formulario (Diseñar)](#422-constructor-del-formulario-diseñar)
    - [4.2.3 Vista previa del formulario](#423-vista-previa-del-formulario)
    - [4.2.4 Responder (Completar formulario)](#424-responder-completar-formulario)
    - [4.2.5 Asignaciones](#425-asignaciones)
    - [4.2.6 Respuestas (Submissions)](#426-respuestas-submissions)
  - [4.3 Administración](#43-administración)
    - [4.3.1 Usuarios](#431-usuarios)
    - [4.3.2 Permisos por rol](#432-permisos-por-rol)
- [5. Panel Central — /central](#5-panel-central--central)
  - [5.1 Empresas (Tenants)](#51-empresas-tenants)
  - [5.2 Usuarios Centrales](#52-usuarios-centrales)
- [6. Panel Admin — /admin](#6-panel-admin--admin)
- [7. Flujos completos (paso a paso)](#7-flujos-completos-paso-a-paso)
  - [7.1 Preparar monitoreo GPS](#71-preparar-monitoreo-gps)
  - [7.2 Crear y publicar un formulario](#72-crear-y-publicar-un-formulario)
  - [7.3 Asignar formularios a operadores](#73-asignar-formularios-a-operadores)
  - [7.4 Operador: responder un formulario](#74-operador-responder-un-formulario)
  - [7.5 Supervisión: revisar respuestas y adjuntos](#75-supervisión-revisar-respuestas-y-adjuntos)
  - [7.6 Generar y exportar un reporte de recorrido](#76-generar-y-exportar-un-reporte-de-recorrido)
- [8. App móvil / API (referencia funcional)](#8-app-móvil--api-referencia-funcional)
  - [8.1 Validación de dominio](#81-validación-de-dominio)
  - [8.2 Login y sesión](#82-login-y-sesión)
  - [8.3 Formularios (lista y detalle)](#83-formularios-lista-y-detalle)
  - [8.4 Envíos (submissions)](#84-envíos-submissions)
  - [8.5 Fotos](#85-fotos)
  - [8.6 GPS Tracking (entrada de puntos)](#86-gps-tracking-entrada-de-puntos)

---

## 1. Conceptos clave

- **Tenant / Empresa**: una “empresa” dentro del sistema. Cada tenant tiene su propio dominio/subdominio y sus propios datos (usuarios, dispositivos, formularios, respuestas y GPS).
- **Usuario (tenant)**: persona que ingresa al panel del tenant (`/app`). Puede ser admin, supervisor u operador. Se controla con roles/permisos.
- **Dispositivo**: equipo identificado por un **IMEI** de 15 dígitos, asociado a un usuario operador. Sirve para vincular puntos GPS y contexto.
- **Punto GPS (Rastreo)**: registro con latitud/longitud, precisión y hora del GPS; alimenta el mapa, la tabla y los reportes.
- **Formulario**: plantilla con preguntas. Puede estar activo o inactivo.
- **Versión publicada**: un formulario puede tener muchas versiones; solo la versión publicada es la que se entrega a operadores (y es requerida para “Responder”).
- **Asignación**: relación entre un formulario y un operador. Controla qué formularios ve el operador.
- **Respuesta / Submission**: envío de un formulario (con respuestas por pregunta) realizado por un usuario (normalmente un operador).

---

## 2. Acceso al sistema

### 2.1 URLs y redirección

- **Entrar por la raíz**: si abrís la web en `/`, el sistema redirige según el dominio:
  - Si el dominio es “central”, te envía a: `/central`
  - Si es dominio de un tenant, te envía a: `/app`
- **Panel del tenant (empresa)**: `https://<dominio-del-tenant>/app`
- **Panel central**: `https://<dominio-central>/central`
- **Panel admin (central)**: `https://<dominio-central>/admin`

Notas:
- El dominio “central” puede estar configurado y también se consideran dominios como `localhost`/`127.0.0.1` para entorno local.
- Un tenant suele acceder como subdominio: `empresa.tudominio.com`.

### 2.2 Inicio de sesión

Panel del tenant (`/app`):
- El login usa **email + contraseña** del usuario del tenant.
- Si el usuario está marcado como **inactivo**, no debería poder operar normalmente (según restricciones de acceso).

Panel central (`/central` y `/admin`):
- Usa usuarios del “sistema central” (usuarios centrales).

### 2.3 Menú y visibilidad por permisos

En el panel del tenant, el menú lateral muestra u oculta módulos según permisos. Si no ves una opción:
- Tu rol no tiene ese permiso, o
- Tu usuario no tiene el permiso asignado, o
- Estás intentando entrar a una pantalla que requiere permisos adicionales.

---

## 3. Roles y permisos

### 3.1 Roles del tenant

El sistema define estos roles base:
- **admin**: acceso completo a todo lo del tenant.
- **supervisor**: acceso de supervisión (ver, revisar y reportes, sin cambios críticos).
- **operator**: acceso operativo (principalmente responder formularios y enviar evidencia).

### 3.2 Permisos disponibles (catálogo)

Los permisos se agrupan así:

**Formularios**
- `forms.view`: ver formularios
- `forms.create`: crear formularios
- `forms.update`: editar formularios y su diseño
- `forms.delete`: eliminar formularios
- `forms.publish`: publicar versiones

**Asignaciones**
- `assignments.view`: ver asignaciones
- `assignments.manage`: gestionar asignaciones (crear, revocar, reactivar)

**Respuestas**
- `submissions.view`: ver respuestas enviadas
- `submissions.create`: crear respuestas (operación)
- `submissions.upload_photos`: subir fotos (evidencia)

**Usuarios**
- `users.view`: ver usuarios
- `users.manage`: gestionar usuarios y permisos/roles

**Dispositivos**
- `devices.view`: ver dispositivos y monitoreo
- `devices.manage`: gestionar dispositivos

**Reportes**
- `reports.view`: ver reportes

Regla práctica:
- Si sos **operador**, normalmente solo vas a ver **Formularios** (para responder) y tus funciones asociadas.
- Si sos **admin/supervisor**, vas a ver también módulos de gestión y monitoreo.

---

## 4. Panel del Tenant (Empresa) — /app

Este es el panel principal del día a día para una empresa (tenant).

### 4.1 Monitoreo GPS

#### 4.1.1 Dispositivos

Objetivo: registrar dispositivos por IMEI y asignarlos a un usuario operador.

**Pantalla: Lista de Dispositivos**
- Muestra:
  - Usuario asignado (y su email como descripción)
  - IMEI (con opción de copiar)
  - Fecha de registro
- Acciones típicas:
  - **Editar**: cambiar usuario o IMEI
  - **Eliminar**: borrar el dispositivo (si corresponde)
- Detalle útil: el IMEI es copiable desde la tabla, para evitar errores de tipeo.

**Pantalla: Crear Dispositivo**
- Campos:
  - **Usuario**: solo aparecen usuarios activos **que no tienen un dispositivo asignado**.
  - **IMEI**: obligatorio, exactamente 15 dígitos numéricos.
- Función opcional: **Generar IMEI**
  - Botón **Generar**: crea un IMEI válido de 15 dígitos que **no se repite** en la base del tenant.
  - Al generarlo:
    - Se llena el campo IMEI automáticamente.
    - Se copia al portapapeles para pegarlo en otro lugar si lo necesitás.
  - También queda el botón de **copiar** para volver a copiar el IMEI cuando quieras.

**Pantalla: Editar Dispositivo**
- Permite:
  - Cambiar el usuario (incluye el actual aunque ya tenga dispositivo).
  - Cambiar el IMEI (15 dígitos).

Buenas prácticas:
- Evitá reutilizar IMEIs (el sistema impone unicidad).
- Asigná cada dispositivo a un operador real para que el monitoreo sea claro.

#### 4.1.2 Mapa GPS

Objetivo: ver en un mapa los últimos puntos reportados por un dispositivo.

**Pantalla: Mapa GPS**
- Selector:
  - **Dispositivo** (por IMEI y, si existe, nombre del usuario).
- Estado visible:
  - “En vivo” cuando hay un dispositivo seleccionado.
  - Hora de actualización del panel.
  - Hora del último punto GPS del dispositivo (si existe).
- Actualización:
  - Se actualiza automáticamente cada ~30 segundos cuando hay un dispositivo seleccionado.
- Acciones superiores:
  - **Actualizar ahora**: fuerza un refresco inmediato.
  - **Ver rastreo tabular**: abre la vista en tabla del rastreo (para análisis por filas).

Interpretación típica:
- **Puntos**: el sistema muestra una cantidad acotada de puntos recientes (para performance y lectura rápida).
- **Precisión**: se expresa en metros; valores bajos indican mejor precisión.

#### 4.1.3 Reporte de Recorrido

Objetivo: generar un reporte de ruta/recorrido del dispositivo en un período y exportarlo.

**Pantalla: Reporte de Recorrido**
- Filtros:
  - **Dispositivo**
  - **Período**: Hoy / Ayer / Personalizado
  - **Fecha inicio** y **Fecha fin** (solo si Período = Personalizado)
- Acciones:
  - **Generar Reporte**: calcula y presenta el reporte del período elegido.
  - **Exportar Excel**: descarga un Excel con los puntos del reporte (solo aparece cuando el reporte ya fue generado y hay puntos).
- Resultados típicos:
  - Cantidad de puntos
  - Distancia total aproximada
  - Duración estimada
  - Primer/último timestamp del tramo
  - Mapa con recorrido y controles de reproducción (según la interfaz)

Recomendación de uso:
- Si un recorrido “se corta” en el mapa, puede deberse a pausas o saltos grandes de tiempo entre puntos.
- Para auditoría, exportá Excel y revisá coordenadas/horas.

#### 4.1.4 Rastreo GPS (tabla y detalle)

Objetivo: auditar punto por punto, con filtros y detalle de cada registro.

**Pantalla: Rastreo GPS (lista)**
- Columnas principales:
  - **Dispositivo** (IMEI; muestra usuario como descripción)
  - **Coordenadas** (latitud + longitud en descripción; copiable como “lat,lng”)
  - **Precisión** (en metros, con color semáforo)
  - **Hora GPS** (en zona América/Lima)
  - **Registrado** (momento en que el sistema guardó el punto)
- Filtros:
  - Por **dispositivo**
  - Por **rango de fechas** (desde/hasta)

**Pantalla: Ver Punto GPS (detalle)**
- Secciones:
  - **Dispositivo**: IMEI, usuario asignado y email (copiable).
  - **Ubicación**: latitud, longitud, precisión.
  - **Tiempos**:
    - Hora GPS
    - Tiempo desde boot (si aplica)
    - Fecha de registro en sistema

---

### 4.2 Formularios

#### 4.2.1 Formularios (administración)

Objetivo: crear formularios, activarlos/desactivarlos y navegar a su diseño, vista previa o respuesta.

**Pantalla: Lista de Formularios**
- Columnas:
  - Nombre
  - Activo (toggle)
  - Versión actual (si no hay, aparece como “Borrador”)
  - Última modificación
- Acciones por formulario:
  - **Vista previa**: muestra cómo lo verá el operador (solo lectura).
  - **Diseñar**: abre el constructor del formulario.
  - **Editar**: cambia datos básicos (nombre, descripción, activo).
  - **Responder**: permite completar el formulario (solo si:
    - el formulario está activo y
    - hay versión publicada y
    - el usuario tiene permiso para crear submissions).

**Pantalla: Crear Formulario**
- Campos:
  - **Nombre del formulario**
  - **Descripción**
  - **Formulario activo**
- Importante:
  - Crear el formulario no lo “publica”: primero se diseña, luego se publica una versión.

**Pantalla: Editar Formulario (datos básicos)**
- Permite ajustar:
  - Nombre
  - Descripción operativa (más extensa)
  - Activación/desactivación
- Muestra:
  - Estado y disponibilidad (activo/inactivo)
  - Versión publicada actual
  - Cantidad de preguntas configuradas

#### 4.2.2 Constructor del formulario (Diseñar)

Objetivo: definir preguntas (campos) y opciones de respuesta.

**Pantalla: Constructor del formulario**
- Acciones de cabecera:
  - **Editar datos básicos**: vuelve a la edición del formulario.
  - **Vista previa**: revisa el formulario como lo verá el operador.
  - **Publicar versión**:
    - Crea una nueva versión con las preguntas actuales.
    - Es lo que habilita a los operadores a ver el formulario actualizado.

**Cómo se compone una pregunta**
Cada pregunta tiene:
- **Pregunta visible (label)**: texto que verá el usuario.
- **Tipo de respuesta** (obligatorio): define el control (texto, número, selección, etc.).
- **Obligatoria**: si está marcada, se exige respuesta.
- **Campo activo**: si se desactiva, no aparece al operador.

**Tipos de respuesta disponibles**
- Texto corto
- Texto largo
- Número
- Lista desplegable
- Opción única (radio)
- Selección múltiple (checkbox)
- Fecha
- Hora
- Archivo

**Configuraciones por tipo (según corresponda)**
- Texto corto:
  - Longitud máxima
  - Placeholder
- Texto largo:
  - Filas visibles
  - Longitud máxima
  - Placeholder
- Número:
  - Mínimo / Máximo
  - Incremento
  - Unidad (sufijo)
- Lista desplegable:
  - Permitir búsqueda
  - Placeholder
- Radio:
  - Opciones en línea
- Checkbox:
  - Opciones en línea (presentación)
- Fecha:
  - Fecha mínima / máxima
  - Placeholder
- Hora:
  - Hora mínima / máxima
  - Intervalo (minutos)
- Archivo:
  - Tipos aceptados (extensiones)
  - Tamaño máximo (KB)
  - Permitir múltiples archivos

**Opciones (para Select/Radio/Checkbox)**
- Cada opción tiene:
  - Texto visible
  - Valor interno
  - Opción activa

#### 4.2.3 Vista previa del formulario

Objetivo: ver el formulario en modo “solo lectura”, tal como lo verá el operador.

Características:
- No permite guardar ni enviar (es solo visual).
- Muestra solamente campos **activos**.
- Respeta reglas como requerido, placeholders, opciones, etc.
- Incluye accesos rápidos:
  - Ir al constructor
  - Editar datos básicos

#### 4.2.4 Responder (Completar formulario)

Objetivo: completar y enviar respuestas.

**Reglas para poder responder**
- Debés tener permiso `submissions.create`.
- El formulario debe:
  - Estar **activo**
  - Tener una **versión publicada**
  - Tener preguntas activas

**Pantalla: Completar — <Nombre del formulario>**
- Se muestra:
  - Nombre del formulario
  - Descripción (si existe)
  - Campos a completar
- Botones:
  - **Cancelar**: vuelve a la lista de formularios.
  - **Enviar formulario**: registra el envío (submission).

Qué pasa al enviar:
- El sistema crea una “respuesta” (submission) asociada a tu usuario.
- Guarda cada respuesta por pregunta.
- Si subís archivos, se almacenan como adjuntos de esa submission.
- Se notifica “Formulario enviado”.

Casos especiales:
- Si el formulario no tiene versión publicada: te muestra un aviso y no permite enviar.
- Si no hay preguntas activas: te muestra un aviso.

#### 4.2.5 Asignaciones

Objetivo: definir qué formularios ve cada operador.

**Pantalla: Lista de Asignaciones**
- Muestra:
  - Formulario
  - Usuario
  - Fecha de asignación
  - Estado (Activo / Revocado)
- Acciones:
  - **Editar**: cambiar valores de la asignación (según permisos).
  - **Revocar**: corta el acceso del usuario a ese formulario.
  - **Reactivar**: vuelve a habilitar la asignación.

**Pantalla: Crear Asignación**
Se compone de 3 secciones:
- **Formulario a asignar**
  - Solo aparecen formularios activos con versión publicada.
  - Se muestra la descripción del formulario como apoyo.
- **Usuarios**
  - Podés seleccionar uno o varios usuarios.
  - Si algunos ya tienen asignación activa, el sistema los ignora automáticamente al crear.
- **Vigencia**
  - Fecha/hora desde la que rige la asignación.

#### 4.2.6 Respuestas (Submissions)

Objetivo: revisar envíos de formularios (auditoría, control, evidencia).

**Pantalla: Lista de Respuestas**
- Columnas:
  - Formulario
  - Usuario (o “Anónimo” si aplica)
  - Estado
  - Fecha de envío
  - Cantidad de respuestas
- Filtros:
  - Por fecha de envío (desde/hasta)

**Pantalla: Ver Respuesta (detalle)**
Secciones típicas:
- Datos del envío (formulario, usuario, estado, fecha/hora).
- Ubicación (latitud y longitud si existen).
  - Acción: **Ver en mapa** (abre un modal con mapa si hay coordenadas).
- Respuestas: listado de cada pregunta con su valor:
  - Si es opción, muestra la etiqueta (si existe).
  - Si es archivo, se presenta como descarga/visualización según corresponda.

**Descarga de archivos adjuntos (si corresponde)**
- Los archivos se sirven con autorización y requieren permiso `submissions.view`.
- El sistema usa un enlace del estilo:
  - `/app/submissions/{submission}/files/{filename}`

---

### 4.3 Administración

#### 4.3.1 Usuarios

Objetivo: administrar usuarios que acceden al panel del tenant.

**Pantalla: Lista de Usuarios**
- Muestra:
  - Nombre
  - Correo
  - Activo (sí/no)
  - Roles (badges)

**Pantalla: Crear/Editar Usuario**
Secciones:
- Datos personales
  - Nombre completo
  - Correo (se usa para login)
- Acceso
  - Contraseña:
    - En creación es obligatoria.
    - En edición, si la dejás vacía, se conserva la actual.
  - Usuario activo:
    - Si se desactiva, el usuario no debería poder ingresar.
- Permisos (roles)
  - Podés asignar uno o varios roles (según política de tu empresa).

Recomendaciones:
- Para operadores móviles, asignar rol **operator**.
- Para gestión, usar **admin** o **supervisor** según nivel.

#### 4.3.2 Permisos por rol

Objetivo: configurar qué permisos tiene cada rol en el tenant.

**Pantalla: Permisos por rol**
- Seleccioná un rol (por defecto intenta cargar “admin”).
- Se muestran permisos agrupados por dominio (Formularios, Asignaciones, etc.).
- Podés marcar/desmarcar permisos y guardar.

Protección especial:
- El rol **admin** está protegido en modo solo lectura para evitar dejar el tenant sin acceso.

Acción principal:
- **Guardar permisos**: aplica cambios al rol seleccionado y actualiza la cache de permisos.

---

## 5. Panel Central — /central

Este panel es para administración central del sistema (super admin / equipo central).

### 5.1 Empresas (Tenants)

Objetivo: crear y administrar empresas (tenants) del sistema.

**Pantalla: Lista de Empresas**
- Muestra: ID, nombre, identificador, dominio, fecha de creación.

**Pantalla: Crear Empresa**
Secciones:
- Empresa
  - Nombre de empresa
  - Identificador (slug): se usa internamente y no se cambia luego.
- Acceso por dominio
  - Subdominio: genera el dominio final como `<subdominio>.<dominio-base>`.
  - Vista previa del dominio.
  - Validación: no permite repetir subdominios ya usados.
- Cuenta de Administrador Inicial
  - Email y contraseña del primer admin del tenant.

**Pantalla: Editar Empresa**
- Permite ver datos y el dominio principal (no editable).

### 5.2 Usuarios Centrales

Objetivo: gestionar usuarios del panel central.

**Pantalla: Lista de Usuarios Centrales**
- Muestra: nombre, email, si es super admin.

**Pantalla: Crear Usuario Central**
- Campos: nombre, email, contraseña, super admin (sí/no).
- Nota: el email puede quedar bloqueado en edición (según regla).

---

## 6. Panel Admin — /admin

Este panel existe como panel “default” del sistema y también usa el guard central.

En la configuración actual:
- Incluye un **Dashboard** y widgets estándar (cuenta e info de Filament).
- No se detectaron módulos específicos adicionales en este panel (más allá de lo que provee el framework).

Si tu organización usa `/admin`, confirmá con el equipo central qué funciones operativas se gestionan ahí.

---

## 7. Flujos completos (paso a paso)

### 7.1 Preparar monitoreo GPS

1. Entrá al panel del tenant: `/app`.
2. Andá a **Administración → Usuarios**.
3. Creá (o verificá) un usuario operador:
   - Activo = sí
   - Rol = operator (si aplica)
4. Andá a **Monitoreo GPS → Dispositivos**.
5. Click en **Crear**:
   - Elegí el usuario operador.
   - En IMEI:
     - Pegá el IMEI real del equipo, o
     - Usá **Generar** (opcional) si solo necesitás un IMEI único para registrar.
6. Verificá que el dispositivo aparezca en la lista.
7. Andá a **Monitoreo GPS → Mapa GPS**, seleccioná el dispositivo y verificá puntos.

### 7.2 Crear y publicar un formulario

1. Andá a **Formularios → Formularios**.
2. Click en **Crear** y completá:
   - Nombre
   - Descripción (opcional pero recomendada)
   - Activo = sí
3. Guardá.
4. En la tabla, buscá el formulario y entrá a **Diseñar**.
5. En el **Constructor**:
   - Agregá preguntas.
   - Configurá tipo, requerido, opciones, etc.
6. Click en **Vista previa** para validar cómo se verá.
7. Click en **Publicar versión** y confirmá.
8. Volvé a la lista y verificá que “Versión actual” ya no figure como borrador.

### 7.3 Asignar formularios a operadores

1. Andá a **Formularios → Asignaciones**.
2. Click en **Crear**.
3. Elegí el formulario (debe estar activo y publicado).
4. Seleccioná uno o varios usuarios operadores.
5. Guardá.

### 7.4 Operador: responder un formulario

1. Entrá a **Formularios → Formularios**.
2. En el formulario asignado, click **Responder**.
3. Completá campos (los obligatorios son requeridos).
4. Si hay archivo, subilo donde corresponda.
5. Click **Enviar formulario**.
6. Confirmá que aparezca la notificación de envío exitoso.

### 7.5 Supervisión: revisar respuestas y adjuntos

1. Entrá a **Formularios → Respuestas**.
2. Usá filtros por fecha si necesitás.
3. Abrí una respuesta.
4. Revisá:
   - Datos del envío (quién, cuándo, estado).
   - Ubicación (si existe) y “Ver en mapa”.
   - Respuestas por pregunta.
   - Adjuntos (descargar/visualizar según el caso).

### 7.6 Generar y exportar un reporte de recorrido

1. Entrá a **Monitoreo GPS → Reporte de Recorrido**.
2. Seleccioná un dispositivo.
3. Elegí período (Hoy/Ayer/Personalizado).
4. Click **Generar Reporte**.
5. Si necesitás archivo:
   - Click **Exportar Excel** (solo aparece si hay puntos).

---

## 8. App móvil / API (referencia funcional)

Esta sección describe, en términos funcionales, lo que soporta la app móvil (o integración) vía API. La API está bajo:

`/api/v1/{tenant}/...`

Donde `{tenant}` identifica el tenant por ruta (para inicializar tenancy por path).

### 8.1 Validación de dominio

Endpoint (utilidad):
- `GET /api/verify-domain?domain=<dominio>`

Uso funcional:
- Verificar si un dominio/subdominio existe y corresponde a un tenant.

### 8.2 Login y sesión

- `POST /api/v1/{tenant}/auth/login`
  - Permite login de usuarios **operator**.
  - Devuelve token para usar en siguientes requests.
- `POST /api/v1/{tenant}/auth/refresh`
  - Renueva token (revoca el actual y crea uno nuevo).
- `POST /api/v1/{tenant}/auth/logout`
  - Cierra sesión (revoca token actual).
- `GET /api/v1/{tenant}/me`
  - Devuelve datos del usuario autenticado.

Restricción funcional:
- Solo **operadores** acceden al login móvil (si un usuario no es operator, se bloquea).

### 8.3 Formularios (lista y detalle)

- `GET /api/v1/{tenant}/forms`
  - Devuelve formularios activos y publicados.
  - Si el usuario es operator, solo devuelve los que tiene **asignados**.
- `GET /api/v1/{tenant}/forms/{form}`
  - Devuelve detalle del formulario y su versión actual.
  - Para operator: exige asignación activa a ese formulario.

### 8.4 Envíos (submissions)

- `GET /api/v1/{tenant}/submissions`
  - Lista envíos; operator ve solo los suyos.
  - Admite filtros como `status` y `form_id`.
- `GET /api/v1/{tenant}/submissions/{submission}`
  - Muestra un envío con sus respuestas.
  - Operator solo puede ver los propios.
- `POST /api/v1/{tenant}/submissions`
  - Crea (o recupera) un envío asociado a un `form_version_id`.
  - Para operator: exige asignación activa al formulario.
- `PATCH /api/v1/{tenant}/submissions/{submission}`
  - Actualiza un envío (solo el propio del operator).

### 8.5 Fotos

- `POST /api/v1/{tenant}/submissions/{submission}/photos`
  - Sube una foto y la procesa como evidencia del envío.
- `GET /api/v1/{tenant}/submissions/{submission}/photos`
  - Lista fotos asociadas a un envío.
- `DELETE /api/v1/{tenant}/submissions/{submission}/photos/{media}`
  - Elimina una foto.

### 8.6 GPS Tracking (entrada de puntos)

Objetivo funcional:
- Permitir que un dispositivo (o integración) envíe puntos GPS usando IMEI.

Endpoints:
- `POST /api/v1/{tenant}/gps/track`
  - Recibe:
    - `imei`
    - `points` (batch de puntos)
  - Valida y encola el guardado.
  - Responde con cantidad de puntos aceptados.
- `GET /api/v1/{tenant}/gps/track`
  - Lista puntos (con filtros por `device_id`, `from`, `to`).
- `GET /api/v1/{tenant}/gps/track/{track}`
  - Detalle de un punto.

Relación con el panel:
- Estos puntos alimentan:
  - Mapa GPS
  - Rastreo GPS (tabla/detalle)
  - Reporte de Recorrido

