-- ============================================================
--  AttendQR — PRUEBAS LOCALES (NO correr en producción)
--  Paso 2 de 3: historial del TRIMESTRE 3 (jul–sep 2026)
--
--  Correr DESPUÉS de importar Aprendices_Prueba_Trimestres.csv.
--  Simula que la ficha 3000001 tuvo clases en agosto y septiembre:
--    4 sesiones cerradas con asistencia de los 5 aprendices.
--  Así hay un T3 "real" para comprobar que no se mezcla con el T4.
--
--  Se puede correr varias veces: no duplica sesiones ni asistencias.
-- ============================================================

USE attendqr;
SET NAMES utf8mb4;

-- ── Sesiones de agosto y septiembre (T3) ───────────────────────
INSERT INTO sesiones_asistencia
  (id_ficha, nombre_materia, fecha_sesion, estado_sesion,
   hora_inicio_clase, hora_cierre, limite_retardo_minutos, duracion_maxima_minutos)
SELECT f.id_ficha, 'Bases de Datos', x.fecha, 'cerrada',
       '14:00:00', CONCAT(x.fecha, ' 14:20:00'), 5, 20
  FROM fichas f
  JOIN (SELECT '2026-08-05' AS fecha UNION ALL SELECT '2026-08-19'
        UNION ALL SELECT '2026-09-02' UNION ALL SELECT '2026-09-16') x
 WHERE f.codigo_ficha = '3000001'
   AND NOT EXISTS (SELECT 1 FROM sesiones_asistencia s
                    WHERE s.id_ficha = f.id_ficha AND s.fecha_sesion = x.fecha);

-- ── Asistencias: todos presentes salvo algunos casos ────────────
--   ...90 Ana     → siempre presente
--   ...91 Bruno   → siempre presente
--   ...92 Camila  → falla el 19-ago y el 16-sep   (la que se retira)
--   ...93 Diego   → retardo el 02-sep
--   ...94 Elena   → presente
INSERT IGNORE INTO asistencias (id_sesion, id_aprendiz, estado, metodo_registro, observacion)
SELECT s.id_sesion, a.id_aprendiz,
       CASE
         WHEN a.numero_documento = '1234567892' AND s.fecha_sesion IN ('2026-08-19','2026-09-16') THEN 'ausente'
         WHEN a.numero_documento = '1234567893' AND s.fecha_sesion = '2026-09-02'               THEN 'retardo'
         ELSE 'presente'
       END,
       'manual',
       'Dato de prueba T3'
  FROM sesiones_asistencia s
  JOIN fichas f     ON f.id_ficha = s.id_ficha
  JOIN aprendices a ON a.id_ficha = f.id_ficha
 WHERE f.codigo_ficha = '3000001'
   AND s.fecha_sesion BETWEEN '2026-07-01' AND '2026-09-30'
   AND a.numero_documento IN ('1234567890','1234567891','1234567892','1234567893','1234567894');

-- Verificación: debe dar 4 sesiones y 20 asistencias
SELECT COUNT(DISTINCT s.id_sesion) AS sesiones_T3, COUNT(a.id_asistencia) AS asistencias_T3
  FROM sesiones_asistencia s
  JOIN fichas f ON f.id_ficha = s.id_ficha
  LEFT JOIN asistencias a ON a.id_sesion = s.id_sesion
 WHERE f.codigo_ficha = '3000001'
   AND s.fecha_sesion BETWEEN '2026-07-01' AND '2026-09-30';
