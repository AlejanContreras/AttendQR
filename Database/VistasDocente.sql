USE attendqr;

-- ====================================================
-- AttendQR — VIEW DOCENTE FINAL
-- Formato estilo Excel REAL del instructor
--
-- Vista específica para:
--   ficha 2879345
--   jornada tarde
--
-- El nombre de la vista YA contiene:
--   ficha + jornada
-- por lo tanto NO se muestran como columnas.
--
-- Visual limpia:
-- nombres | apellidos | lun-14 | mar-15 | ... | fallas | asistencias | tiempo_retardo
-- ============================================================

CREATE OR REPLACE VIEW mensual_2879345_tarde AS

SELECT
  ap.nombres,
  ap.apellidos,

  -- ========================================================
  -- COLUMNAS DEL MES
  -- ========================================================

  MAX(CASE WHEN s.fecha_sesion = '2025-05-14'
    THEN
      CASE
        WHEN a.estado = 'presente' THEN 'A'
        WHEN a.estado = 'ausente'  THEN 'F'
        WHEN a.estado = 'excusa'   THEN 'E'
        WHEN a.estado = 'retardo'  THEN 'R'
        ELSE '—'
      END
  END) AS `lun-14`,

  MAX(CASE WHEN s.fecha_sesion = '2025-05-15'
    THEN
      CASE
        WHEN a.estado = 'presente' THEN 'A'
        WHEN a.estado = 'ausente'  THEN 'F'
        WHEN a.estado = 'excusa'   THEN 'E'
        WHEN a.estado = 'retardo'  THEN 'R'
        ELSE '—'
      END
  END) AS `mar-15`,

  MAX(CASE WHEN s.fecha_sesion = '2025-05-16'
    THEN
      CASE
        WHEN a.estado = 'presente' THEN 'A'
        WHEN a.estado = 'ausente'  THEN 'F'
        WHEN a.estado = 'excusa'   THEN 'E'
        WHEN a.estado = 'retardo'  THEN 'R'
        ELSE '—'
      END
  END) AS `mié-16`,

  MAX(CASE WHEN s.fecha_sesion = '2025-05-19'
    THEN
      CASE
        WHEN a.estado = 'presente' THEN 'A'
        WHEN a.estado = 'ausente'  THEN 'F'
        WHEN a.estado = 'excusa'   THEN 'E'
        WHEN a.estado = 'retardo'  THEN 'R'
        ELSE '—'
      END
  END) AS `sáb-19`,

  MAX(CASE WHEN s.fecha_sesion = '2025-05-20'
    THEN
      CASE
        WHEN a.estado = 'presente' THEN 'A'
        WHEN a.estado = 'ausente'  THEN 'F'
        WHEN a.estado = 'excusa'   THEN 'E'
        WHEN a.estado = 'retardo'  THEN 'R'
        ELSE '—'
      END
  END) AS `lun-20`,

  MAX(CASE WHEN s.fecha_sesion = '2025-05-21'
    THEN
      CASE
        WHEN a.estado = 'presente' THEN 'A'
        WHEN a.estado = 'ausente'  THEN 'F'
        WHEN a.estado = 'excusa'   THEN 'E'
        WHEN a.estado = 'retardo'  THEN 'R'
        ELSE '—'
      END
  END) AS `mar-21`,

  -- ========================================================
  -- TOTALES
  -- ========================================================

  SUM(
    CASE
      WHEN a.estado = 'ausente'
        OR a.estado IS NULL
      THEN 1
      ELSE 0
    END
  ) AS fallas,

  SUM(
    CASE
      WHEN a.estado IN ('presente', 'retardo')
      THEN 1
      ELSE 0
    END
  ) AS asistencias,

  CONCAT(
    COALESCE(SUM(a.minutos_retardo), 0),
    'min'
  ) AS tiempo_retardo

FROM aprendices ap

JOIN fichas f
  ON f.id_ficha = ap.id_ficha

JOIN sesiones_asistencia s
  ON s.id_ficha = ap.id_ficha
  AND s.estado_sesion IN ('abierta', 'cerrada')

LEFT JOIN asistencias a
  ON a.id_sesion = s.id_sesion
  AND a.id_aprendiz = ap.id_aprendiz

WHERE
  ap.activo = 1
  AND f.codigo_ficha = '2879345'
  AND MONTH(s.fecha_sesion) = 5
  AND YEAR(s.fecha_sesion) = 2025

GROUP BY
  ap.id_aprendiz,
  ap.nombres,
  ap.apellidos

ORDER BY
  ap.apellidos,
  ap.nombres;
  
 
 
 
 
 
 -- ============================================================
-- AttendQR
-- VIEW DOCENTE FINAL V2
--
-- mensual_2879345_tarde_V2
--
-- ✔ estilo Excel del instructor
-- ✔ A = asistencia
-- ✔ F = falla
-- ✔ E = excusa
-- ✔ R = retardo
-- ✔ retardos suman asistencia
-- ✔ SOLO retardos cuentan en tiempo_retardo
-- ✔ limpia y lista para Google Sheets
-- ============================================================

USE attendqr;

CREATE OR REPLACE VIEW mensual_2879345_tarde_V2 AS

SELECT
    ap.nombres,
    ap.apellidos,

    -- ========================================================
    -- COLUMNAS DEL MES
    -- ========================================================

    MAX(
        CASE WHEN s.fecha_sesion = '2025-05-12' THEN
            CASE
                WHEN a.estado = 'presente' THEN 'A'
                WHEN a.estado = 'ausente'  THEN 'F'
                WHEN a.estado = 'excusa'   THEN 'E'
                WHEN a.estado = 'retardo'  THEN 'R'
                ELSE '—'
            END
        END
    ) AS `lun-12`,

    MAX(
        CASE WHEN s.fecha_sesion = '2025-05-13' THEN
            CASE
                WHEN a.estado = 'presente' THEN 'A'
                WHEN a.estado = 'ausente'  THEN 'F'
                WHEN a.estado = 'excusa'   THEN 'E'
                WHEN a.estado = 'retardo'  THEN 'R'
                ELSE '—'
            END
        END
    ) AS `mar-13`,

    MAX(
        CASE WHEN s.fecha_sesion = '2025-05-14' THEN
            CASE
                WHEN a.estado = 'presente' THEN 'A'
                WHEN a.estado = 'ausente'  THEN 'F'
                WHEN a.estado = 'excusa'   THEN 'E'
                WHEN a.estado = 'retardo'  THEN 'R'
                ELSE '—'
            END
        END
    ) AS `mié-14`,

    MAX(
        CASE WHEN s.fecha_sesion = '2025-05-16' THEN
            CASE
                WHEN a.estado = 'presente' THEN 'A'
                WHEN a.estado = 'ausente'  THEN 'F'
                WHEN a.estado = 'excusa'   THEN 'E'
                WHEN a.estado = 'retardo'  THEN 'R'
                ELSE '—'
            END
        END
    ) AS `vie-16`,

    MAX(
        CASE WHEN s.fecha_sesion = '2025-05-19' THEN
            CASE
                WHEN a.estado = 'presente' THEN 'A'
                WHEN a.estado = 'ausente'  THEN 'F'
                WHEN a.estado = 'excusa'   THEN 'E'
                WHEN a.estado = 'retardo'  THEN 'R'
                ELSE '—'
            END
        END
    ) AS `lun-19`,

    MAX(
        CASE WHEN s.fecha_sesion = '2025-05-20' THEN
            CASE
                WHEN a.estado = 'presente' THEN 'A'
                WHEN a.estado = 'ausente'  THEN 'F'
                WHEN a.estado = 'excusa'   THEN 'E'
                WHEN a.estado = 'retardo'  THEN 'R'
                ELSE '—'
            END
        END
    ) AS `mar-20`,

    MAX(
        CASE WHEN s.fecha_sesion = '2025-05-21' THEN
            CASE
                WHEN a.estado = 'presente' THEN 'A'
                WHEN a.estado = 'ausente'  THEN 'F'
                WHEN a.estado = 'excusa'   THEN 'E'
                WHEN a.estado = 'retardo'  THEN 'R'
                ELSE '—'
            END
        END
    ) AS `mié-21`,

    -- ========================================================
    -- TOTALES
    -- ========================================================

    -- FALLAS
    SUM(
        CASE
            WHEN a.estado = 'ausente'
            THEN 1
            ELSE 0
        END
    ) AS fallas,

    -- ASISTENCIAS
    -- presente + retardo
    SUM(
        CASE
            WHEN a.estado IN ('presente', 'retardo')
            THEN 1
            ELSE 0
        END
    ) AS asistencias,

    -- TIEMPO TOTAL RETARDO
    -- SOLO retardos
    CONCAT(
        COALESCE(
            SUM(
                CASE
                    WHEN a.estado = 'retardo'
                    THEN a.minutos_retardo
                    ELSE 0
                END
            ),
            0
        ),
        'min'
    ) AS tiempo_retardo

FROM aprendices ap

JOIN fichas f
    ON f.id_ficha = ap.id_ficha

JOIN sesiones_asistencia s
    ON s.id_ficha = ap.id_ficha
    AND s.estado_sesion IN ('cerrada', 'abierta')

LEFT JOIN asistencias a
    ON a.id_sesion = s.id_sesion
    AND a.id_aprendiz = ap.id_aprendiz

WHERE
    ap.activo = 1
    AND f.codigo_ficha = '2879345'
    AND MONTH(s.fecha_sesion) = 5
    AND YEAR(s.fecha_sesion) = 2025

GROUP BY
    ap.id_aprendiz,
    ap.nombres,
    ap.apellidos

ORDER BY
    ap.apellidos,
    ap.nombres;
 
 
 
 
 
SELECT * FROM mensual_2879345_tarde;
  
SELECT * FROM mensual_2879345_tarde_V2;