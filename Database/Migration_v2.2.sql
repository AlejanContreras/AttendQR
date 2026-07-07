-- AttendQR — Migración v2.2  (2026-07-06)
--
-- Cambios:
--   1. Agrega jornada "mañana" a la tabla jornadas.
--      Estándar SENA: mañana 06:00-12:00, gracia 10 min.
--      Seeds originales tenían solo Tarde (ID 1) y Noche (ID 2).
--
-- Migración IDEMPOTENTE.
-- Aplicar contra la BD de AttendQR:
--   mysql -u root attendqr < Database/Migration_v2.2.sql

INSERT INTO jornadas (id_jornada, nombre, hora_inicio, hora_fin, minutos_gracia)
SELECT 3, 'mañana', '06:00:00', '12:00:00', 10
WHERE NOT EXISTS (
  SELECT 1 FROM jornadas WHERE id_jornada = 3
);
