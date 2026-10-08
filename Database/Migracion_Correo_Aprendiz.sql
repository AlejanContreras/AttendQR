-- ============================================================
--  AttendQR — Migracion_Correo_Aprendiz.sql
--  Fecha: 2026-10
--
--  [Correo y recuperación de cuenta del aprendiz]
--    correo              → correo del aprendiz (obligatorio al activar
--                          la cuenta; a los que ya tienen cuenta se les
--                          pide al entrar).
--    recuperacion_hash   → hash de la contraseña temporal enviada por correo
--    recuperacion_expira → hasta cuándo sirve (30 minutos)
--
--  Si un aprendiz no tiene correo, la recuperación sigue funcionando
--  como antes: la solicitud le llega al instructor.
--
--  Cómo correrla: phpMyAdmin / Workbench → base de AttendQR → SQL.
--  UNA sola vez. "Duplicate column name" = ya estaba aplicada.
--
--  Reversa (solo si hiciera falta):
--    ALTER TABLE aprendices DROP COLUMN correo,
--      DROP COLUMN recuperacion_hash, DROP COLUMN recuperacion_expira;
-- ============================================================

ALTER TABLE aprendices
  ADD COLUMN correo              VARCHAR(120) NULL DEFAULT NULL
      COMMENT 'Correo del aprendiz para recuperar su cuenta',
  ADD COLUMN recuperacion_hash   VARCHAR(255) NULL DEFAULT NULL
      COMMENT 'Hash de la contraseña temporal de recuperación',
  ADD COLUMN recuperacion_expira DATETIME     NULL DEFAULT NULL
      COMMENT 'Vencimiento de la contraseña temporal (hora de Colombia)';

-- Verificación
SHOW COLUMNS FROM aprendices WHERE Field IN ('correo','recuperacion_hash','recuperacion_expira');
