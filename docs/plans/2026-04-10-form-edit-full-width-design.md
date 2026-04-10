# Tenant Form Edit Full Width Design

**Fecha:** 2026-04-10

## Objetivo
Hacer que `app/forms/{record}/edit` se perciba como una pantalla de trabajo a vista completa, sin cambiar la lógica de negocio del recurso.

## Diagnóstico confirmado
La página ya usa `Width::Full`, pero el schema actual sigue viéndose angosto porque `EditRecord` usa un grid raíz de 2 columnas por defecto y el único `Section` principal no ocupa todo el ancho útil. El problema es de composición visual, no de activar ancho full-width.

## Enfoque aprobado
Adoptar un layout tipo workspace 8/4:
- columna principal 8/12 para edición de metadata
- rail lateral 4/12 para contexto y acciones rápidas
- header con acciones mejor jerarquizadas
- copy más operativo y menos "pedagógico"

## Estructura propuesta
### Header
- título basado en el nombre real del formulario
- subheading corto y operativo
- acciones: Abrir constructor, Vista previa, Volver al listado

### Main (8/12)
- Section “Datos básicos”
- `name` dominante
- `is_active` compacto
- `description` a ancho completo

### Aside (4/12)
- Section “Estado del formulario”
- Section “Versión actual”
- Section “Siguientes pasos” con CTAs

## Tradeoffs
- **Pros:** la pantalla se siente realmente full-width y aprovecha el espacio disponible sin estirar inputs innecesariamente.
- **Contras:** requiere modelar mejor el schema y agregar bloques de contexto; no es solo cambiar una propiedad.