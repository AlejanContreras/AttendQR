-- ============================================================
--  AttendQR — MIGRACIÓN v1.3
--  Ejecutar sobre una base de datos attendqr ya existente.
--
--  CAMBIOS:
--  1. Agrega nombre_materia a sesiones_asistencia
--  2. Elimina restricción UNIQUE(id_ficha, fecha_sesion)
--     → permite múltiples sesiones por ficha en el mismo día
--     → el sistema sigue impidiendo dos sesiones ABIERTAS
--       simultáneas para la misma ficha (control en PHP)
--  3. Ajusta valores por defecto de la regla temporal:
--       limite_retardo_minutos  → 5  (PRESENTE: apertura – H+5 min)
--       duracion_maxima_minutos → 20 (RETARDO: H+6 – H+20 min)
--                                    cierre a partir de H+21 min
-- ============================================================

USE attendqr;

-- 1. Columna para el nombre de la materia/tema de la sesión
ALTER TABLE sesiones_asistencia
  ADD COLUMN nombre_materia VARCHAR(120) NULL
    COMMENT 'Nombre de la materia o tema. Lo registra el docente al crear la sesión.'
  AFTER id_ficha;

-- 2. Eliminar la restricción que impide múltiples sesiones en el mismo día
ALTER TABLE sesiones_asistencia
  DROP INDEX uq_ficha_fecha;

-- 3. Nuevos defaults de la regla temporal
ALTER TABLE sesiones_asistencia
  MODIFY COLUMN limite_retardo_minutos  SMALLINT UNSIGNED NOT NULL DEFAULT 5
    COMMENT 'Minutos desde H en que se acepta PRESENTE (H+0 a H+limite). Superado → RETARDO.',
  MODIFY COLUMN duracion_maxima_minutos SMALLINT UNSIGNED NOT NULL DEFAULT 20
    COMMENT 'Minutos desde H que cierra la ventana de retardo. Superado → no acepta asistencias.';
