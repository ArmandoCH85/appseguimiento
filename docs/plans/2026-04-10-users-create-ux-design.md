# Tenant users create UX design

**Fecha:** 2026-04-10

## Objetivo
Hacer visible la creación de usuarios en `/app/users` y mejorar la experiencia de alta/edición sin cambiar el modelo de permisos ni la semántica de roles existente.

## Enfoque recomendado
1. Agregar una header action `CreateAction` en la página `ListUsers` para exponer la ruta ya existente de creación.
2. Mantener la lógica actual del recurso `UserResource`, pero rediseñar el formulario con secciones, labels y ayudas más humanas.
3. Dar contexto claro en `CreateUser` y `EditUser` con títulos, subtítulos y notificaciones en español.

## Tradeoffs
- **Pros:** resuelve el problema visible ya mismo, mantiene consistencia con `/app/forms`, reduce carga cognitiva al crear/editar usuarios.
- **Contras:** no cambia el modelo de permisos ni fuerza rol único; si más adelante se quisiera un wizard o invitaciones por email, eso sería otro cambio.

## Testing
- Test unitario para verificar que `ListUsers` expone la acción `create`.
- Test unitario para verificar títulos/subtítulos y redirección/notificación amigables en create/edit.
