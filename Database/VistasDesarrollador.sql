-- ============================================================
-- AttendQR
-- VIEW DESARROLLADOR FINAL
--
-- Desarrollador_mensual_2879345_tarde
--
-- Basada en:
-- mensual_2879345_tarde_V2
--
-- Agrega:
-- ✔ método de registro
-- ✔ ubicación (fase futura)
-- ✔ vista técnica para validación
-- ✔ mantiene formato visual tipo Excel
--
-- NO es para el docente.
-- Uso interno/desarrollo/debug.
-- ============================================================

USE attendqr;

CREATE OR REPLACE VIEW Desarrollador_mensual_2879345_tarde AS

SELECT
    ap.nombres,
    ap.apellidos,

    -- ========================================================
    -- DÍAS DEL MES
    -- ========================================================

    MAX(
        CASE WHEN s.fecha_sesion = '2025-05-12'
        THEN CONCAT(
            CASE
                WHEN a.estado = 'presente' THEN 'A'
                WHEN a.estado = 'ausente'  THEN 'F'
                WHEN a.estado = 'excusa'   THEN 'E'
                WHEN a.estado = 'retardo'  THEN 'R'
                ELSE '—'
            END,
            ' | ',
            COALESCE(a.metodo_registro, '—')
        )
        END
    ) AS `lun-12`,

    MAX(
        CASE WHEN s.fecha_sesion = '2025-05-13'
        THEN CONCAT(
            CASE
                WHEN a.estado = 'presente' THEN 'A'
                WHEN a.estado = 'ausente'  THEN 'F'
                WHEN a.estado = 'excusa'   THEN 'E'
                WHEN a.estado = 'retardo'  THEN 'R'
                ELSE '—'
            END,
            ' | ',
            COALESCE(a.metodo_registro, '—')
        )
        END
    ) AS `mar-13`,

    MAX(
        CASE WHEN s.fecha_sesion = '2025-05-14'
        THEN CONCAT(
            CASE
                WHEN a.estado = 'presente' THEN 'A'
                WHEN a.estado = 'ausente'  THEN 'F'
                WHEN a.estado = 'excusa'   THEN 'E'
                WHEN a.estado = 'retardo'  THEN 'R'
                ELSE '—'
            END,
            ' | ',
            COALESCE(a.metodo_registro, '—')
        )
        END
    ) AS `mié-14`,

    MAX(
        CASE WHEN s.fecha_sesion = '2025-05-16'
        THEN CONCAT(
            CASE
                WHEN a.estado = 'presente' THEN 'A'
                WHEN a.estado = 'ausente'  THEN 'F'
                WHEN a.estado = 'excusa'   THEN 'E'
                WHEN a.estado = 'retardo'  THEN 'R'
                ELSE '—'
            END,
            ' | ',
            COALESCE(a.metodo_registro, '—')
        )
        END
    ) AS `vie-16`,

    MAX(
        CASE WHEN s.fecha_sesion = '2025-05-19'
        THEN CONCAT(
            CASE
                WHEN a.estado = 'presente' THEN 'A'
                WHEN a.estado = 'ausente'  THEN 'F'
                WHEN a.estado = 'excusa'   THEN 'E'
                WHEN a.estado = 'retardo'  THEN 'R'
                ELSE '—'
            END,
            ' | ',
            COALESCE(a.metodo_registro, '—')
        )
        END
    ) AS `lun-19`,

    MAX(
        CASE WHEN s.fecha_sesion = '2025-05-20'
        THEN CONCAT(
            CASE
                WHEN a.estado = 'presente' THEN 'A'
                WHEN a.estado = 'ausente'  THEN 'F'
                WHEN a.estado = 'excusa'   THEN 'E'
                WHEN a.estado = 'retardo'  THEN 'R'
                ELSE '—'
            END,
            ' | ',
            COALESCE(a.metodo_registro, '—')
        )
        END
    ) AS `mar-20`,

    MAX(
        CASE WHEN s.fecha_sesion = '2025-05-21'
        THEN CONCAT(
            CASE
                WHEN a.estado = 'presente' THEN 'A'
                WHEN a.estado = 'ausente'  THEN 'F'
                WHEN a.estado = 'excusa'   THEN 'E'
                WHEN a.estado = 'retardo'  THEN 'R'
                ELSE '—'
            END,
            ' | ',
            COALESCE(a.metodo_registro, '—')
        )
        END
    ) AS `mié-21`,

    -- ========================================================
    -- TOTALES
    -- ========================================================

    SUM(
        CASE
            WHEN a.estado = 'ausente'
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
    ) AS tiempo_retardo,

    -- ========================================================
    -- MÉTODO MÁS USADO EN EL MES
    -- ========================================================

    CASE
        WHEN SUM(a.metodo_registro = 'qr') > 0
         AND SUM(a.metodo_registro = 'manual') > 0
            THEN 'mixto'

        WHEN SUM(a.metodo_registro = 'qr') > 0
            THEN 'qr'

        WHEN SUM(a.metodo_registro = 'manual') > 0
            THEN 'manual'

        ELSE '—'
    END AS metodo_predominante,

    -- ========================================================
    -- UBICACIÓN (FASE FUTURA)
    -- ========================================================

    'pendiente' AS ubicacion_validada

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
    
    
SELECT * FROM Desarrollador_mensual_2879345_tarde;
