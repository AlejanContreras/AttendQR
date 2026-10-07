-- ============================================================
--  AttendQR — Migracion_Recuperacion_Docente.sql
--  Fecha: 2026-10
--
--  [Recuperación de cuenta del docente por correo]
--  Agrega a docentes dos columnas para una contraseña TEMPORAL
--  que convive con la contraseña actual:
--
--    recuperacion_hash   → hash bcrypt de la contraseña temporal
--    recuperacion_expira → hasta cuándo sirve (30 minutos)
--
--  La contraseña actual NO se toca al pedir la recuperación: si
--  alguien escribe el correo de tu papá, él puede seguir entrando
--  con su contraseña de siempre. Solo cuando inicia sesión con la
--  temporal, esta pasa a ser su contraseña.
--
--  Cómo correrla: phpMyAdmin / Workbench → base de AttendQR → SQL.
--  UNA sola vez. "Duplicate column name" = ya estaba aplicada.
--
--  Reversa (solo si hiciera falta):
--    ALTER TABLE docentes DROP COLUMN recuperacion_hash, DROP COLUMN recuperacion_expira;
-- ============================================================

ALTER TABLE docentes
  ADD COLUMN recuperacion_hash   VARCHAR(255) NULL DEFAULT NULL
      COMMENT 'Hash de la contraseña temporal de recuperación',
  ADD COLUMN recuperacion_expira DATETIME     NULL DEFAULT NULL
      COMMENT 'Vencimiento de la contraseña temporal (hora de Colombia)';

-- Verificación
SHOW COLUMNS FROM docentes LIKE 'recuperacion%';
