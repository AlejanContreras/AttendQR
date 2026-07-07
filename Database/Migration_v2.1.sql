-- AttendQR — Migración v2.1  (2026-07-06)
--
-- Cambios:
--   1. fichas.nombre_materia VARCHAR(150) NULL
--      Permite que el docente registre la materia como dato permanente de la clase.
--   2. fichas.id_trimestre   → nullable (NULL DEFAULT NULL)
--      Los docentes auto-gestionan sus fichas sin necesidad de asignar trimestre
--      (metadata de coordinador, no requerido en el flujo de Mis Clases).
--
-- Migración IDEMPOTENTE: puede ejecutarse múltiples veces sin error.
-- Aplicar contra la BD de AttendQR:
--   mysql -u root attendqr < Database/Migration_v2.1.sql

DROP PROCEDURE IF EXISTS _attendqr_migrate_v21;

DELIMITER $$
CREATE PROCEDURE _attendqr_migrate_v21()
BEGIN
  -- ADD COLUMN solo si no existe
  IF NOT EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'fichas'
      AND COLUMN_NAME  = 'nombre_materia'
  ) THEN
    ALTER TABLE fichas
      ADD COLUMN nombre_materia VARCHAR(150) NULL DEFAULT NULL AFTER nombre_programa;
  END IF;

  -- MODIFY COLUMN es idempotente por sí mismo
  ALTER TABLE fichas
    MODIFY COLUMN id_trimestre INT UNSIGNED NULL DEFAULT NULL;
END$$
DELIMITER ;

CALL _attendqr_migrate_v21();
DROP PROCEDURE IF EXISTS _attendqr_migrate_v21;
