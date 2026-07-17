-- ============================================================
--  AttendQR — InstallFresh.sql
--  Instalación limpia desde cero (v2.2 definitiva)
--
--  Incluye:
--    ✔ Todas las tablas con estructura final
--    ✔ Todos los índices y constraints
--    ✔ Vistas docente y desarrollador
--    ✔ Datos base obligatorios (jornadas)
--
--  Ejecutar en phpMyAdmin o MySQL CLI:
--    mysql -u root -p < Database/InstallFresh.sql
--
--  NO requiere archivos previos. No usa DROP, ALTER ni DELETE.
--  Pensado para cualquier despliegue nuevo.
-- ============================================================

CREATE DATABASE IF NOT EXISTS attendqr
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE attendqr;


-- ============================================================
-- TABLA: docentes
-- Instructores SENA que crean y gestionan sesiones.
-- ============================================================
CREATE TABLE IF NOT EXISTS docentes (
  id_docente    INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  nombres       VARCHAR(80)   NOT NULL,
  apellidos     VARCHAR(80)   NOT NULL,
  correo        VARCHAR(120)  NOT NULL UNIQUE,
  password_hash VARCHAR(255)  NOT NULL,
  activo        TINYINT(1)    NOT NULL DEFAULT 1,
  creado_en     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci
  COMMENT='Instructores SENA';


-- ============================================================
-- TABLA: jornadas
-- Define franjas horarias. minutos_gracia no puede ser negativo.
-- ============================================================
CREATE TABLE IF NOT EXISTS jornadas (
  id_jornada     INT UNSIGNED       AUTO_INCREMENT PRIMARY KEY,
  nombre         VARCHAR(40)        NOT NULL UNIQUE
                                    COMMENT 'Ej: mañana, tarde, noche',
  hora_inicio    TIME               NOT NULL,
  hora_fin       TIME               NOT NULL,
  minutos_gracia SMALLINT UNSIGNED  NOT NULL DEFAULT 10
                                    COMMENT 'Tolerancia antes de marcar retardo'
) ENGINE=InnoDB
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci
  COMMENT='Jornadas disponibles (mañana, tarde, noche)';


-- ============================================================
-- TABLA: trimestres
-- Periodos académicos trimestrales SENA.
-- ============================================================
CREATE TABLE IF NOT EXISTS trimestres (
  id_trimestre INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  nombre       VARCHAR(60)   NOT NULL COMMENT 'Ej: Trimestre 2 - 2025',
  fecha_inicio DATE          NOT NULL,
  fecha_fin    DATE          NOT NULL,
  activo       TINYINT(1)    NOT NULL DEFAULT 1
) ENGINE=InnoDB
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci
  COMMENT='Periodos trimestrales SENA';


-- ============================================================
-- TABLA: fichas
-- Grupo de aprendices por programa, jornada y trimestre.
-- id_trimestre es nullable: los docentes pueden gestionar fichas
-- sin asignar trimestre (dato de coordinador, no del flujo principal).
-- nombre_materia: materia que imparte el docente en esta ficha.
-- ============================================================
CREATE TABLE IF NOT EXISTS fichas (
  id_ficha        INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  codigo_ficha    VARCHAR(20)   NOT NULL UNIQUE
                                COMMENT 'Código oficial SENA',
  nombre_programa VARCHAR(120)  NOT NULL,
  nombre_materia  VARCHAR(150)  NULL DEFAULT NULL
                                COMMENT 'Materia que imparte el docente en esta ficha',
  id_docente      INT UNSIGNED  NOT NULL,
  id_jornada      INT UNSIGNED  NOT NULL,
  id_trimestre    INT UNSIGNED  NULL DEFAULT NULL,
  activa          TINYINT(1)    NOT NULL DEFAULT 1,

  CONSTRAINT fk_ficha_docente
    FOREIGN KEY (id_docente)   REFERENCES docentes(id_docente),
  CONSTRAINT fk_ficha_jornada
    FOREIGN KEY (id_jornada)   REFERENCES jornadas(id_jornada),
  CONSTRAINT fk_ficha_trimestre
    FOREIGN KEY (id_trimestre) REFERENCES trimestres(id_trimestre)

) ENGINE=InnoDB
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci
  COMMENT='Grupos SENA por programa, jornada y trimestre';


-- ============================================================
-- TABLA: aprendices
-- Cada aprendiz pertenece a una sola ficha.
-- cuenta_activada:
--   0 = pre-registrado por docente (sin contraseña real)
--   1 = auto-registro completado (puede iniciar sesión)
-- ============================================================
CREATE TABLE IF NOT EXISTS aprendices (
  id_aprendiz      INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  numero_documento VARCHAR(20)   NOT NULL UNIQUE,
  nombres          VARCHAR(80)   NOT NULL,
  apellidos        VARCHAR(80)   NOT NULL,
  password_hash    VARCHAR(255)  NOT NULL,
  id_ficha         INT UNSIGNED  NOT NULL,
  activo           TINYINT(1)    NOT NULL DEFAULT 1
                                 COMMENT '0 = retirado',
  cuenta_activada  TINYINT(1)    NOT NULL DEFAULT 0
                                 COMMENT '0 = pre-registrado, 1 = auto-registro completado',

  CONSTRAINT fk_aprendiz_ficha
    FOREIGN KEY (id_ficha) REFERENCES fichas(id_ficha)

) ENGINE=InnoDB
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci
  COMMENT='Aprendices, uno por ficha';

CREATE INDEX IF NOT EXISTS idx_aprendiz_ficha
  ON aprendices(id_ficha);

CREATE INDEX IF NOT EXISTS idx_aprendiz_ficha_activo
  ON aprendices(id_ficha, activo);

CREATE INDEX IF NOT EXISTS idx_aprendiz_cuenta
  ON aprendices(cuenta_activada);


-- ============================================================
-- TABLA: sesiones_asistencia
-- Un día de clase abierto por el docente.
--
-- SEPARACIÓN DE CONCEPTOS:
--   hora_apertura     = cuándo el docente habilitó el sistema.
--                       No afecta ningún cálculo académico.
--   hora_inicio_clase = hora oficial de inicio. Base de todos
--                       los cálculos de retardo y cierre.
--
-- REGLA TEMPORAL:
--   PRESENTE → hora_registro ≤ hora_inicio_clase + limite_retardo_minutos
--   RETARDO  → hora_registro >  hora_inicio_clase + limite_retardo_minutos
--   RECHAZO  → hora_registro >  hora_inicio_clase + duracion_maxima_minutos
--
-- GEOLOCALIZACIÓN:
--   ubicacion_activa = 1 → la sesión exige validación de radio (30 m fijo).
--   lat_docente / lng_docente / accuracy_docente se registran al crear.
-- ============================================================
CREATE TABLE IF NOT EXISTS sesiones_asistencia (
  id_sesion                INT UNSIGNED       AUTO_INCREMENT PRIMARY KEY,

  id_ficha                 INT UNSIGNED       NOT NULL,

  nombre_materia           VARCHAR(120)       NULL
                                              COMMENT 'Nombre de la materia o tema de la sesión.',

  fecha_sesion             DATE               NOT NULL,

  estado_sesion            ENUM('abierta','cerrada','cancelada')
                                              NOT NULL DEFAULT 'abierta',

  -- ── CONTROL DE APERTURA ──────────────────────────────────
  hora_apertura            DATETIME(3)        NOT NULL DEFAULT CURRENT_TIMESTAMP(3)
                                              COMMENT 'Cuándo abrió el docente. No afecta cálculos académicos.',

  -- ── ANCLA ACADÉMICA ──────────────────────────────────────
  hora_inicio_clase        TIME               NOT NULL
                                              COMMENT 'Hora oficial de inicio. Base de cálculo de retardo y cierre.',

  -- ── CONTROL DE CIERRE ────────────────────────────────────
  hora_cierre              DATETIME(3)        NULL
                                              COMMENT 'Cierre real. NULL mientras esté abierta.',

  -- ── LÓGICA TEMPORAL ──────────────────────────────────────
  limite_retardo_minutos   SMALLINT UNSIGNED  NOT NULL DEFAULT 5
                                              COMMENT 'Minutos desde H en que se acepta PRESENTE. Superado → RETARDO.',

  duracion_maxima_minutos  SMALLINT UNSIGNED  NOT NULL DEFAULT 20
                                              COMMENT 'Minutos desde H que cierra la ventana. Superado → rechazado.',

  -- ── QR DINÁMICO ──────────────────────────────────────────
  rotacion_qr_segundos     SMALLINT UNSIGNED  NOT NULL DEFAULT 30
                                              COMMENT 'Cada cuántos segundos rota el token QR.',

  -- ── GEOLOCALIZACIÓN ──────────────────────────────────────
  ubicacion_activa         TINYINT(1)         NOT NULL DEFAULT 0
                                              COMMENT '1 = sesión exige validación de radio geográfico',
  lat_docente              DECIMAL(10,7)      NULL
                                              COMMENT 'Latitud del docente al abrir la sesión',
  lng_docente              DECIMAL(10,7)      NULL
                                              COMMENT 'Longitud del docente al abrir la sesión',
  accuracy_docente         DECIMAL(8,2)       NULL
                                              COMMENT 'Precisión GPS del docente en metros',

  CONSTRAINT fk_sesion_ficha
    FOREIGN KEY (id_ficha) REFERENCES fichas(id_ficha)

) ENGINE=InnoDB
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci
  COMMENT='Sesiones de clase. PRESENTE=H a H+5, RETARDO=H+6 a H+20, rechazo H+21.';

CREATE INDEX IF NOT EXISTS idx_sesion_ficha_fecha
  ON sesiones_asistencia(id_ficha, fecha_sesion);


-- ============================================================
-- TABLA: tokens_qr
-- Tokens dinámicos por sesión. Rotan cada N segundos.
--
-- GARANTÍA DE TOKEN ÚNICO ACTIVO POR SESIÓN:
--   activo = 1    → token vigente
--   activo = NULL → token vencido o rotado
--   UNIQUE(id_sesion, activo): MySQL permite múltiples NULL en
--   un índice UNIQUE, pero rechaza dos filas con el mismo valor
--   no-NULL en la misma combinación → BD garantiza un solo
--   activo=1 por sesión sin lógica extra en el backend.
-- ============================================================
CREATE TABLE IF NOT EXISTS tokens_qr (
  id_token    INT UNSIGNED       AUTO_INCREMENT PRIMARY KEY,
  id_sesion   INT UNSIGNED       NOT NULL,
  token_valor VARCHAR(64)        NOT NULL UNIQUE,
  creado_en   DATETIME(3)        NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  expira_en   DATETIME(3)        NOT NULL,
  activo      TINYINT(1)         NULL DEFAULT 1
              COMMENT '1 = vigente, NULL = vencido/rotado.',
  veces_usado SMALLINT UNSIGNED  NOT NULL DEFAULT 0
              COMMENT 'Cuántos aprendices escanearon este token. Solo auditoría.',

  CONSTRAINT fk_token_sesion
    FOREIGN KEY (id_sesion) REFERENCES sesiones_asistencia(id_sesion),

  CONSTRAINT uq_token_activo_por_sesion
    UNIQUE (id_sesion, activo)

) ENGINE=InnoDB
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci
  COMMENT='Tokens QR dinámicos por sesión';


-- ============================================================
-- TABLA: asistencias
-- Registro individual por aprendiz por sesión.
-- minutos_retardo se calcula al registrar y se persiste.
--
-- CHECK chk_hora_registro_qr:
--   Si metodo_registro = 'qr', hora_registro NO puede ser NULL.
--   Protege el dato base del cálculo PRESENTE/RETARDO.
-- ============================================================
CREATE TABLE IF NOT EXISTS asistencias (
  id_asistencia    INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
  id_sesion        INT UNSIGNED   NOT NULL,
  id_aprendiz      INT UNSIGNED   NOT NULL,
  id_token_usado   INT UNSIGNED   NULL
                                  COMMENT 'NULL si fue registro manual',
  estado           ENUM('presente','ausente','excusa','retardo')
                                  NOT NULL DEFAULT 'ausente',
  metodo_registro  ENUM('qr','manual')
                                  NOT NULL DEFAULT 'manual',
  hora_registro    DATETIME(3)    NULL
                                  COMMENT 'Hora exacta de entrada. Obligatorio si metodo = qr.',
  minutos_retardo  SMALLINT       NOT NULL DEFAULT 0,
  ubicacion_valida TINYINT(1)     NULL     COMMENT 'NULL = no verificado',
  latitud          DECIMAL(10,7)  NULL,
  longitud         DECIMAL(10,7)  NULL,
  observacion      VARCHAR(255)   NULL,
  registrado_en    DATETIME(3)    NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

  CONSTRAINT fk_asistencia_sesion
    FOREIGN KEY (id_sesion)     REFERENCES sesiones_asistencia(id_sesion),
  CONSTRAINT fk_asistencia_aprendiz
    FOREIGN KEY (id_aprendiz)   REFERENCES aprendices(id_aprendiz),
  CONSTRAINT fk_asistencia_token
    FOREIGN KEY (id_token_usado) REFERENCES tokens_qr(id_token),

  CONSTRAINT uq_sesion_aprendiz
    UNIQUE (id_sesion, id_aprendiz),

  CONSTRAINT chk_hora_registro_qr
    CHECK (
      metodo_registro = 'manual'
      OR hora_registro IS NOT NULL
    )

) ENGINE=InnoDB
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci
  COMMENT='Asistencia individual. QR/manual, retardos, geoloc futura.';

CREATE INDEX IF NOT EXISTS idx_asistencia_sesion
  ON asistencias(id_sesion);

CREATE INDEX IF NOT EXISTS idx_asistencia_aprendiz
  ON asistencias(id_aprendiz);


-- ============================================================
-- DATOS BASE: jornadas
-- Las tres jornadas estándar SENA.
-- INSERT IGNORE evita error si ya existen.
-- ============================================================
INSERT IGNORE INTO jornadas (nombre, hora_inicio, hora_fin, minutos_gracia) VALUES
  ('mañana', '06:00:00', '12:00:00', 10),
  ('tarde',  '14:00:00', '18:00:00', 10),
  ('noche',  '18:00:00', '22:00:00', 10);


-- ============================================================
-- VISTAS DOCENTE
-- Formato tipo Excel por ficha y mes.
-- A = asistencia, F = falla, E = excusa, R = retardo
-- ============================================================

CREATE OR REPLACE VIEW mensual_2879345_tarde AS
SELECT
  ap.nombres,
  ap.apellidos,

  MAX(CASE WHEN s.fecha_sesion = '2025-05-14' THEN
    CASE
      WHEN a.estado = 'presente' THEN 'A'
      WHEN a.estado = 'ausente'  THEN 'F'
      WHEN a.estado = 'excusa'   THEN 'E'
      WHEN a.estado = 'retardo'  THEN 'R'
      ELSE '—'
    END
  END) AS `lun-14`,

  MAX(CASE WHEN s.fecha_sesion = '2025-05-15' THEN
    CASE
      WHEN a.estado = 'presente' THEN 'A'
      WHEN a.estado = 'ausente'  THEN 'F'
      WHEN a.estado = 'excusa'   THEN 'E'
      WHEN a.estado = 'retardo'  THEN 'R'
      ELSE '—'
    END
  END) AS `mar-15`,

  MAX(CASE WHEN s.fecha_sesion = '2025-05-16' THEN
    CASE
      WHEN a.estado = 'presente' THEN 'A'
      WHEN a.estado = 'ausente'  THEN 'F'
      WHEN a.estado = 'excusa'   THEN 'E'
      WHEN a.estado = 'retardo'  THEN 'R'
      ELSE '—'
    END
  END) AS `mié-16`,

  MAX(CASE WHEN s.fecha_sesion = '2025-05-19' THEN
    CASE
      WHEN a.estado = 'presente' THEN 'A'
      WHEN a.estado = 'ausente'  THEN 'F'
      WHEN a.estado = 'excusa'   THEN 'E'
      WHEN a.estado = 'retardo'  THEN 'R'
      ELSE '—'
    END
  END) AS `sáb-19`,

  MAX(CASE WHEN s.fecha_sesion = '2025-05-20' THEN
    CASE
      WHEN a.estado = 'presente' THEN 'A'
      WHEN a.estado = 'ausente'  THEN 'F'
      WHEN a.estado = 'excusa'   THEN 'E'
      WHEN a.estado = 'retardo'  THEN 'R'
      ELSE '—'
    END
  END) AS `lun-20`,

  MAX(CASE WHEN s.fecha_sesion = '2025-05-21' THEN
    CASE
      WHEN a.estado = 'presente' THEN 'A'
      WHEN a.estado = 'ausente'  THEN 'F'
      WHEN a.estado = 'excusa'   THEN 'E'
      WHEN a.estado = 'retardo'  THEN 'R'
      ELSE '—'
    END
  END) AS `mar-21`,

  SUM(CASE WHEN a.estado = 'ausente' OR a.estado IS NULL THEN 1 ELSE 0 END) AS fallas,

  SUM(CASE WHEN a.estado IN ('presente', 'retardo') THEN 1 ELSE 0 END) AS asistencias,

  CONCAT(COALESCE(SUM(a.minutos_retardo), 0), 'min') AS tiempo_retardo

FROM aprendices ap
JOIN fichas f              ON f.id_ficha  = ap.id_ficha
JOIN sesiones_asistencia s ON s.id_ficha  = ap.id_ficha
                           AND s.estado_sesion IN ('abierta', 'cerrada')
LEFT JOIN asistencias a    ON a.id_sesion  = s.id_sesion
                           AND a.id_aprendiz = ap.id_aprendiz
WHERE
  ap.activo = 1
  AND f.codigo_ficha = '2879345'
  AND MONTH(s.fecha_sesion) = 5
  AND YEAR(s.fecha_sesion)  = 2025
GROUP BY ap.id_aprendiz, ap.nombres, ap.apellidos
ORDER BY ap.apellidos, ap.nombres;


-- ── V2: retardos suman asistencia, excusas no cuentan como falla ─

CREATE OR REPLACE VIEW mensual_2879345_tarde_V2 AS
SELECT
  ap.nombres,
  ap.apellidos,

  MAX(CASE WHEN s.fecha_sesion = '2025-05-12' THEN
    CASE
      WHEN a.estado = 'presente' THEN 'A'
      WHEN a.estado = 'ausente'  THEN 'F'
      WHEN a.estado = 'excusa'   THEN 'E'
      WHEN a.estado = 'retardo'  THEN 'R'
      ELSE '—'
    END
  END) AS `lun-12`,

  MAX(CASE WHEN s.fecha_sesion = '2025-05-13' THEN
    CASE
      WHEN a.estado = 'presente' THEN 'A'
      WHEN a.estado = 'ausente'  THEN 'F'
      WHEN a.estado = 'excusa'   THEN 'E'
      WHEN a.estado = 'retardo'  THEN 'R'
      ELSE '—'
    END
  END) AS `mar-13`,

  MAX(CASE WHEN s.fecha_sesion = '2025-05-14' THEN
    CASE
      WHEN a.estado = 'presente' THEN 'A'
      WHEN a.estado = 'ausente'  THEN 'F'
      WHEN a.estado = 'excusa'   THEN 'E'
      WHEN a.estado = 'retardo'  THEN 'R'
      ELSE '—'
    END
  END) AS `mié-14`,

  MAX(CASE WHEN s.fecha_sesion = '2025-05-16' THEN
    CASE
      WHEN a.estado = 'presente' THEN 'A'
      WHEN a.estado = 'ausente'  THEN 'F'
      WHEN a.estado = 'excusa'   THEN 'E'
      WHEN a.estado = 'retardo'  THEN 'R'
      ELSE '—'
    END
  END) AS `vie-16`,

  MAX(CASE WHEN s.fecha_sesion = '2025-05-19' THEN
    CASE
      WHEN a.estado = 'presente' THEN 'A'
      WHEN a.estado = 'ausente'  THEN 'F'
      WHEN a.estado = 'excusa'   THEN 'E'
      WHEN a.estado = 'retardo'  THEN 'R'
      ELSE '—'
    END
  END) AS `lun-19`,

  MAX(CASE WHEN s.fecha_sesion = '2025-05-20' THEN
    CASE
      WHEN a.estado = 'presente' THEN 'A'
      WHEN a.estado = 'ausente'  THEN 'F'
      WHEN a.estado = 'excusa'   THEN 'E'
      WHEN a.estado = 'retardo'  THEN 'R'
      ELSE '—'
    END
  END) AS `mar-20`,

  MAX(CASE WHEN s.fecha_sesion = '2025-05-21' THEN
    CASE
      WHEN a.estado = 'presente' THEN 'A'
      WHEN a.estado = 'ausente'  THEN 'F'
      WHEN a.estado = 'excusa'   THEN 'E'
      WHEN a.estado = 'retardo'  THEN 'R'
      ELSE '—'
    END
  END) AS `mié-21`,

  SUM(CASE WHEN a.estado = 'ausente' THEN 1 ELSE 0 END) AS fallas,

  SUM(CASE WHEN a.estado IN ('presente', 'retardo') THEN 1 ELSE 0 END) AS asistencias,

  CONCAT(
    COALESCE(SUM(CASE WHEN a.estado = 'retardo' THEN a.minutos_retardo ELSE 0 END), 0),
    'min'
  ) AS tiempo_retardo

FROM aprendices ap
JOIN fichas f              ON f.id_ficha  = ap.id_ficha
JOIN sesiones_asistencia s ON s.id_ficha  = ap.id_ficha
                           AND s.estado_sesion IN ('cerrada', 'abierta')
LEFT JOIN asistencias a    ON a.id_sesion  = s.id_sesion
                           AND a.id_aprendiz = ap.id_aprendiz
WHERE
  ap.activo = 1
  AND f.codigo_ficha = '2879345'
  AND MONTH(s.fecha_sesion) = 5
  AND YEAR(s.fecha_sesion)  = 2025
GROUP BY ap.id_aprendiz, ap.nombres, ap.apellidos
ORDER BY ap.apellidos, ap.nombres;


-- ============================================================
-- VISTA DESARROLLADOR
-- Igual que V2 pero agrega método de registro y ubicación.
-- Uso interno / debug. NO exponer al docente.
-- ============================================================

CREATE OR REPLACE VIEW Desarrollador_mensual_2879345_tarde AS
SELECT
  ap.nombres,
  ap.apellidos,

  MAX(CASE WHEN s.fecha_sesion = '2025-05-12' THEN
    CONCAT(
      CASE
        WHEN a.estado = 'presente' THEN 'A'
        WHEN a.estado = 'ausente'  THEN 'F'
        WHEN a.estado = 'excusa'   THEN 'E'
        WHEN a.estado = 'retardo'  THEN 'R'
        ELSE '—'
      END, ' | ', COALESCE(a.metodo_registro, '—')
    )
  END) AS `lun-12`,

  MAX(CASE WHEN s.fecha_sesion = '2025-05-13' THEN
    CONCAT(
      CASE
        WHEN a.estado = 'presente' THEN 'A'
        WHEN a.estado = 'ausente'  THEN 'F'
        WHEN a.estado = 'excusa'   THEN 'E'
        WHEN a.estado = 'retardo'  THEN 'R'
        ELSE '—'
      END, ' | ', COALESCE(a.metodo_registro, '—')
    )
  END) AS `mar-13`,

  MAX(CASE WHEN s.fecha_sesion = '2025-05-14' THEN
    CONCAT(
      CASE
        WHEN a.estado = 'presente' THEN 'A'
        WHEN a.estado = 'ausente'  THEN 'F'
        WHEN a.estado = 'excusa'   THEN 'E'
        WHEN a.estado = 'retardo'  THEN 'R'
        ELSE '—'
      END, ' | ', COALESCE(a.metodo_registro, '—')
    )
  END) AS `mié-14`,

  MAX(CASE WHEN s.fecha_sesion = '2025-05-16' THEN
    CONCAT(
      CASE
        WHEN a.estado = 'presente' THEN 'A'
        WHEN a.estado = 'ausente'  THEN 'F'
        WHEN a.estado = 'excusa'   THEN 'E'
        WHEN a.estado = 'retardo'  THEN 'R'
        ELSE '—'
      END, ' | ', COALESCE(a.metodo_registro, '—')
    )
  END) AS `vie-16`,

  MAX(CASE WHEN s.fecha_sesion = '2025-05-19' THEN
    CONCAT(
      CASE
        WHEN a.estado = 'presente' THEN 'A'
        WHEN a.estado = 'ausente'  THEN 'F'
        WHEN a.estado = 'excusa'   THEN 'E'
        WHEN a.estado = 'retardo'  THEN 'R'
        ELSE '—'
      END, ' | ', COALESCE(a.metodo_registro, '—')
    )
  END) AS `lun-19`,

  MAX(CASE WHEN s.fecha_sesion = '2025-05-20' THEN
    CONCAT(
      CASE
        WHEN a.estado = 'presente' THEN 'A'
        WHEN a.estado = 'ausente'  THEN 'F'
        WHEN a.estado = 'excusa'   THEN 'E'
        WHEN a.estado = 'retardo'  THEN 'R'
        ELSE '—'
      END, ' | ', COALESCE(a.metodo_registro, '—')
    )
  END) AS `mar-20`,

  MAX(CASE WHEN s.fecha_sesion = '2025-05-21' THEN
    CONCAT(
      CASE
        WHEN a.estado = 'presente' THEN 'A'
        WHEN a.estado = 'ausente'  THEN 'F'
        WHEN a.estado = 'excusa'   THEN 'E'
        WHEN a.estado = 'retardo'  THEN 'R'
        ELSE '—'
      END, ' | ', COALESCE(a.metodo_registro, '—')
    )
  END) AS `mié-21`,

  SUM(CASE WHEN a.estado = 'ausente' THEN 1 ELSE 0 END) AS fallas,

  SUM(CASE WHEN a.estado IN ('presente', 'retardo') THEN 1 ELSE 0 END) AS asistencias,

  CONCAT(
    COALESCE(SUM(CASE WHEN a.estado = 'retardo' THEN a.minutos_retardo ELSE 0 END), 0),
    'min'
  ) AS tiempo_retardo,

  CASE
    WHEN SUM(a.metodo_registro = 'qr')     > 0
     AND SUM(a.metodo_registro = 'manual') > 0 THEN 'mixto'
    WHEN SUM(a.metodo_registro = 'qr')     > 0  THEN 'qr'
    WHEN SUM(a.metodo_registro = 'manual') > 0  THEN 'manual'
    ELSE '—'
  END AS metodo_predominante,

  'pendiente' AS ubicacion_validada

FROM aprendices ap
JOIN fichas f              ON f.id_ficha  = ap.id_ficha
JOIN sesiones_asistencia s ON s.id_ficha  = ap.id_ficha
                           AND s.estado_sesion IN ('cerrada', 'abierta')
LEFT JOIN asistencias a    ON a.id_sesion  = s.id_sesion
                           AND a.id_aprendiz = ap.id_aprendiz
WHERE
  ap.activo = 1
  AND f.codigo_ficha = '2879345'
  AND MONTH(s.fecha_sesion) = 5
  AND YEAR(s.fecha_sesion)  = 2025
GROUP BY ap.id_aprendiz, ap.nombres, ap.apellidos
ORDER BY ap.apellidos, ap.nombres;


-- ============================================================
-- VERIFICACIÓN FINAL (informativa)
-- ============================================================
SELECT TABLE_NAME, TABLE_ROWS, TABLE_COMMENT
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'attendqr'
  AND TABLE_TYPE = 'BASE TABLE'
ORDER BY TABLE_NAME;

SELECT TABLE_NAME AS vista
FROM INFORMATION_SCHEMA.VIEWS
WHERE TABLE_SCHEMA = 'attendqr'
ORDER BY TABLE_NAME;
