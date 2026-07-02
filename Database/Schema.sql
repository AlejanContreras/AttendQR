-- ============================================================
--  AttendQR — SCHEMA MVP (instalación limpia desde cero)
--  Sin sobreingeniería. Solo el core necesario.
--  Ejecutar completo en phpMyAdmin o MySQL CLI
-- ------------------------------------------------------------
--  v1.2 — Schema definitivo post-auditoría
--
--  CAMBIOS APLICADOS SOBRE v1.1:
--
--  [CRÍTICO 1] tokens_qr.activo → NULL en vez de 0 al vencer.
--    UNIQUE(id_sesion, activo) garantiza un solo token activo
--    por sesión a nivel de BD. MySQL permite múltiples NULL
--    en un UNIQUE, pero solo un valor no-NULL por combinación.
--    El índice idx_token_sesion_activo fue eliminado porque
--    queda cubierto por el nuevo UNIQUE.
--
--  [CRÍTICO 2] asistencias: CHECK impide hora_registro NULL
--    cuando metodo_registro = 'qr'. Protege el dato base del
--    cálculo PRESENTE/RETARDO contra errores silenciosos.
--
--  [RECOMENDADO 1] sesiones_asistencia: eliminado el campo
--    duracion_sesion_minutos (DEPRECATED). Reemplazado
--    definitivamente por duracion_maxima_minutos.
--
--  [RECOMENDADO 2] jornadas.minutos_gracia → SMALLINT UNSIGNED.
--    Un valor negativo de gracia no tiene sentido semántico.
--
--  [RECOMENDADO 3] tokens_qr.veces_usado → SMALLINT UNSIGNED
--    + COMMENT actualizado: solo auditoría, no limita acceso.
--
--  [RECOMENDADO 4] Nuevo índice idx_aprendiz_ficha_activo
--    sobre aprendices(id_ficha, activo) para la consulta
--    más frecuente: aprendices activos de una ficha.
--
--  REGLA TEMPORAL OFICIAL (sin cambios):
--    PRESENTE  → hora_registro <= hora_inicio_clase
--                                 + limite_retardo_minutos
--    RETARDO   → hora_registro >  hora_inicio_clase
--                                 + limite_retardo_minutos
--    CIERRE    → hora_inicio_clase + duracion_maxima_minutos
--                (se guarda en hora_cierre al cerrar sesión)
--    hora_apertura puede ser anterior a hora_inicio_clase
--    sin afectar ningún cálculo académico.
-- ============================================================

CREATE DATABASE IF NOT EXISTS attendqr
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE attendqr;


-- ============================================================
-- TABLA: docentes
-- ============================================================
CREATE TABLE docentes (
  id_docente    INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  nombres       VARCHAR(80)   NOT NULL,
  apellidos     VARCHAR(80)   NOT NULL,
  correo        VARCHAR(120)  NOT NULL UNIQUE,
  password_hash VARCHAR(255)  NOT NULL,
  activo        TINYINT(1)    NOT NULL DEFAULT 1,
  creado_en     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Instructores SENA';


-- ============================================================
-- TABLA: jornadas
-- La jornada define hora de inicio/fin y minutos de gracia.
-- El retardo se calcula dinámicamente desde hora_registro.
-- [v1.2] minutos_gracia → SMALLINT UNSIGNED (no puede ser negativo).
-- ============================================================
CREATE TABLE jornadas (
  id_jornada     INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  nombre         VARCHAR(40)   NOT NULL UNIQUE COMMENT 'Ej: tarde, noche',
  hora_inicio    TIME          NOT NULL,
  hora_fin       TIME          NOT NULL,
  minutos_gracia SMALLINT UNSIGNED NOT NULL DEFAULT 10
                               COMMENT 'Tolerancia antes de marcar retardo'
) ENGINE=InnoDB COMMENT='Jornadas disponibles (tarde, noche)';


-- ============================================================
-- TABLA: trimestres
-- ============================================================
CREATE TABLE trimestres (
  id_trimestre INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre       VARCHAR(60)  NOT NULL COMMENT 'Ej: Trimestre 2 - 2025',
  fecha_inicio DATE         NOT NULL,
  fecha_fin    DATE         NOT NULL,
  activo       TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB COMMENT='Periodos trimestrales SENA';


-- ============================================================
-- TABLA: fichas
-- Grupo de aprendices por programa, jornada y trimestre.
-- ============================================================
CREATE TABLE fichas (
  id_ficha        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo_ficha    VARCHAR(20)  NOT NULL UNIQUE COMMENT 'Código oficial SENA',
  nombre_programa VARCHAR(120) NOT NULL,
  id_docente      INT UNSIGNED NOT NULL,
  id_jornada      INT UNSIGNED NOT NULL,
  id_trimestre    INT UNSIGNED NOT NULL,
  activa          TINYINT(1)   NOT NULL DEFAULT 1,
  CONSTRAINT fk_ficha_docente   FOREIGN KEY (id_docente)   REFERENCES docentes(id_docente),
  CONSTRAINT fk_ficha_jornada   FOREIGN KEY (id_jornada)   REFERENCES jornadas(id_jornada),
  CONSTRAINT fk_ficha_trimestre FOREIGN KEY (id_trimestre) REFERENCES trimestres(id_trimestre)
) ENGINE=InnoDB COMMENT='Grupos SENA por programa, jornada y trimestre';


-- ============================================================
-- TABLA: aprendices
-- Cada aprendiz pertenece solo a una ficha.
-- ============================================================
CREATE TABLE aprendices (
  id_aprendiz      INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  numero_documento VARCHAR(20)   NOT NULL UNIQUE,
  nombres          VARCHAR(80)   NOT NULL,
  apellidos        VARCHAR(80)   NOT NULL,
  password_hash    VARCHAR(255)  NOT NULL,
  id_ficha         INT UNSIGNED  NOT NULL,
  activo           TINYINT(1)    NOT NULL DEFAULT 1
                   COMMENT '0 = retirado',
  CONSTRAINT fk_aprendiz_ficha FOREIGN KEY (id_ficha) REFERENCES fichas(id_ficha)
) ENGINE=InnoDB COMMENT='Aprendices, uno por ficha';

-- Índice simple (búsqueda por ficha)
CREATE INDEX idx_aprendiz_ficha        ON aprendices(id_ficha);
-- [v1.2] Índice compuesto para la consulta más frecuente:
--        aprendices activos de una ficha (reportes de asistencia)
CREATE INDEX idx_aprendiz_ficha_activo ON aprendices(id_ficha, activo);


-- ============================================================
-- TABLA: sesiones_asistencia
-- Un día de clase abierto por el docente.
-- Controla:
--   ✔ ventana total de asistencia
--   ✔ retardo automático (calculado desde hora_inicio_clase)
--   ✔ cierre automático  (calculado desde hora_inicio_clase)
--   ✔ rotación QR dinámica
-- La etiqueta visual (lun-18) se genera desde fecha_sesion.
--
-- SEPARACIÓN DE CONCEPTOS:
--   hora_apertura     = cuándo el docente habilitó el sistema.
--                       Puede ser antes de la clase.
--                       NO afecta ningún cálculo académico.
--   hora_inicio_clase = cuándo académicamente empieza la clase.
--                       ANCLA de todos los cálculos de tiempo.
--                       Se copia desde jornadas.hora_inicio
--                       al crear la sesión para que quede
--                       históricamente autocontenida.
--
-- FÓRMULAS OFICIALES:
--   PRESENTE → hora_registro <= hora_inicio_clase
--                               + limite_retardo_minutos
--   RETARDO  → hora_registro >  hora_inicio_clase
--                               + limite_retardo_minutos
--   CIERRE   → DATETIME(fecha_sesion, hora_inicio_clase)
--              + INTERVAL duracion_maxima_minutos MINUTE
--              (PHP calcula este valor y lo guarda en hora_cierre)
-- ============================================================
CREATE TABLE sesiones_asistencia (
  id_sesion                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  id_ficha                 INT UNSIGNED NOT NULL,

  fecha_sesion             DATE         NOT NULL,

  estado_sesion            ENUM('abierta','cerrada','cancelada')
                            NOT NULL DEFAULT 'abierta',

  -- ── CONTROL DE APERTURA ──────────────────────────────────
  -- Momento real en que el docente abrió la sesión.
  -- Puede ser anterior a hora_inicio_clase.
  -- No se usa para calcular retardo ni cierre.
  hora_apertura            DATETIME(3)  NOT NULL
                            DEFAULT CURRENT_TIMESTAMP(3)
                            COMMENT 'Cuándo abrió el docente. No afecta cálculos académicos.',

  -- ── ANCLA ACADÉMICA ──────────────────────────────────────
  -- Hora oficial de inicio de la clase según el horario.
  -- Todos los cálculos de retardo y cierre parten de aquí.
  -- Se copia desde jornadas.hora_inicio al crear la sesión.
  hora_inicio_clase        TIME         NOT NULL
                            COMMENT 'Hora oficial de inicio. Base de cálculo de retardo y cierre.',

  -- ── CONTROL DE CIERRE ────────────────────────────────────
  -- Hora real en que se cerró la sesión (manual o automático).
  -- NULL mientras la sesión siga abierta.
  -- Valor al cerrar = DATETIME(fecha_sesion, hora_inicio_clase)
  --                   + INTERVAL duracion_maxima_minutos MINUTE
  hora_cierre              DATETIME(3)  NULL
                            COMMENT 'Cierre real. NULL mientras esté abierta.',

  -- ── LÓGICA TEMPORAL ──────────────────────────────────────
  -- Minutos desde hora_inicio_clase que se aceptan como PRESENTE.
  -- Superado este límite el estado pasa a retardo.
  -- Se copia desde jornadas.minutos_gracia al crear la sesión.
  limite_retardo_minutos   SMALLINT UNSIGNED NOT NULL DEFAULT 10
                            COMMENT 'Desde hora_inicio_clase. Superado → retardo.',

  -- Minutos desde hora_inicio_clase hasta el cierre automático.
  duracion_maxima_minutos  SMALLINT UNSIGNED NOT NULL DEFAULT 240
                            COMMENT 'Desde hora_inicio_clase hasta cierre automático.',

  -- ── QR DINÁMICO ──────────────────────────────────────────
  rotacion_qr_segundos     SMALLINT UNSIGNED NOT NULL DEFAULT 30
                            COMMENT 'Cada cuántos segundos rota el token QR.',

  CONSTRAINT fk_sesion_ficha
    FOREIGN KEY (id_ficha)
    REFERENCES fichas(id_ficha),

  -- Una ficha solo puede tener una sesión por día
  CONSTRAINT uq_ficha_fecha
    UNIQUE (id_ficha, fecha_sesion)

) ENGINE=InnoDB
  COMMENT='Sesiones diarias. Retardo y cierre calculados desde hora_inicio_clase.';

CREATE INDEX idx_sesion_ficha_fecha ON sesiones_asistencia(id_ficha, fecha_sesion);


-- ============================================================
-- TABLA: tokens_qr
-- Tokens dinámicos por sesión. Rotan cada N segundos.
--
-- [v1.2] GARANTÍA DE TOKEN ÚNICO ACTIVO POR SESIÓN:
--   activo = 1    → token vigente
--   activo = NULL → token vencido o rotado
--
--   UNIQUE(id_sesion, activo): MySQL permite múltiples NULL
--   en un índice UNIQUE, pero rechaza dos filas con el mismo
--   valor no-NULL en la misma combinación. Resultado: la BD
--   rechaza INSERT de un segundo token activo para la misma
--   sesión sin necesidad de lógica extra en PHP.
--
--   Al rotar: UPDATE tokens_qr SET activo = NULL WHERE id_token = ?
--   Al crear: INSERT ... activo = 1  (la BD valida la unicidad)
-- ============================================================
CREATE TABLE tokens_qr (
  id_token    INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
  id_sesion   INT UNSIGNED  NOT NULL,
  token_valor VARCHAR(64)   NOT NULL UNIQUE,
  creado_en   DATETIME(3)   NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  expira_en   DATETIME(3)   NOT NULL,

  -- [v1.2] NULL = vencido/rotado. Junto con el UNIQUE abajo,
  --        garantiza un solo token activo por sesión en la BD.
  activo      TINYINT(1)    NULL     DEFAULT 1
              COMMENT '1 = vigente, NULL = vencido/rotado.',

  -- Solo auditoría. No limita el acceso de ningún aprendiz.
  veces_usado SMALLINT UNSIGNED NOT NULL DEFAULT 0
              COMMENT 'Cuántos aprendices escanearon este token. Solo auditoría.',

  CONSTRAINT fk_token_sesion
    FOREIGN KEY (id_sesion)
    REFERENCES sesiones_asistencia(id_sesion),

  -- [v1.2] Reemplaza idx_token_sesion_activo.
  --        Garantía estructural: un solo activo=1 por sesión.
  CONSTRAINT uq_token_activo_por_sesion
    UNIQUE (id_sesion, activo)

) ENGINE=InnoDB COMMENT='Tokens QR dinámicos por sesión';


-- ============================================================
-- TABLA: asistencias
-- Registro individual por aprendiz por sesión.
-- minutos_retardo se calcula al registrar y se persiste.
-- Columnas de geolocalización preparadas para fase futura.
--
-- [v1.2] CHECK chk_hora_registro_qr:
--   Si metodo_registro = 'qr', hora_registro NO puede ser NULL.
--   Protege el dato base del cálculo PRESENTE/RETARDO.
--   Para registros manuales, hora_registro puede ser NULL.
-- ============================================================
CREATE TABLE asistencias (
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
  -- Geolocalización — fase futura
  ubicacion_valida TINYINT(1)     NULL     COMMENT 'NULL = no verificado',
  latitud          DECIMAL(10,7)  NULL,
  longitud         DECIMAL(10,7)  NULL,
  observacion      VARCHAR(255)   NULL,
  registrado_en    DATETIME(3)    NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

  CONSTRAINT fk_asistencia_sesion
    FOREIGN KEY (id_sesion)   REFERENCES sesiones_asistencia(id_sesion),
  CONSTRAINT fk_asistencia_aprendiz
    FOREIGN KEY (id_aprendiz) REFERENCES aprendices(id_aprendiz),
  CONSTRAINT fk_asistencia_token
    FOREIGN KEY (id_token_usado) REFERENCES tokens_qr(id_token),

  -- Defensa principal contra doble registro
  CONSTRAINT uq_sesion_aprendiz
    UNIQUE (id_sesion, id_aprendiz),

  -- [v1.2] Registro QR siempre debe traer hora_registro.
  --        Registro manual puede omitirla.
  CONSTRAINT chk_hora_registro_qr
    CHECK (
      metodo_registro = 'manual'
      OR hora_registro IS NOT NULL
    )

) ENGINE=InnoDB
  COMMENT='Asistencia individual. QR/manual, retardos, geoloc futura.';

CREATE INDEX idx_asistencia_sesion   ON asistencias(id_sesion);
CREATE INDEX idx_asistencia_aprendiz ON asistencias(id_aprendiz);
