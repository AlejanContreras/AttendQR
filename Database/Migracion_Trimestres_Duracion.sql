-- ============================================================
--  AttendQR — Migracion_Trimestres_Duracion.sql
--  Fecha: 2026-10
--
--  Qué hace:
--    1. Agrega jornadas.duracion_defecto_minutos: duración por
--       defecto de una sesión según la jornada.
--         mañana / tarde → 20 min  (comportamiento de siempre)
--         noche          → 60 min  (aprendices que llegan del trabajo)
--       El docente puede cambiarla al iniciar la sesión (20–60).
--       La rotación del QR NO cambia: sigue cada 30 segundos.
--
--  Qué NO hace (a propósito):
--    - No toca sesiones, asistencias ni aprendices.
--    - No toca la tabla trimestres. Los trimestres ahora se
--      calculan desde fecha_sesion (T1 ene–mar, T2 abr–jun,
--      T3 jul–sep, T4 oct–dic), así que el historial del T3
--      queda fijo aunque la ficha continúe en T4.
--
--  Cómo correrla: phpMyAdmin → base de datos de AttendQR →
--  pestaña SQL → pegar y ejecutar. UNA sola vez.
--  Si sale "Duplicate column name", ya estaba aplicada: no pasa nada.
--
--  Reversa (solo si hiciera falta):
--    ALTER TABLE jornadas DROP COLUMN duracion_defecto_minutos;
-- ============================================================

ALTER TABLE jornadas
  ADD COLUMN duracion_defecto_minutos SMALLINT UNSIGNED NOT NULL DEFAULT 20
  COMMENT 'Duración por defecto de la sesión en minutos (20–60). El docente puede cambiarla al iniciar.';

-- WHERE sobre la columna nombre (UNIQUE) para que funcione también con el
-- "safe update mode" de MySQL Workbench. La collation _ci ya ignora mayúsculas.
UPDATE jornadas
   SET duracion_defecto_minutos = 60
 WHERE nombre = 'noche';

-- Verificación: debe mostrar noche = 60 y las demás = 20
SELECT id_jornada, nombre, hora_inicio, hora_fin, duracion_defecto_minutos
  FROM jornadas;
