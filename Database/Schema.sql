-- ============================================================
--  AttendQR — SCHEMA MVP (instalación limpia desde cero)
--  Sin sobreingeniería. Solo el core necesario.
--  Ejecutar completo en phpMyAdmin o MySQL CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS attendqr
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE attendqr;

-- ============================================================
-- TABLA: docentes
-- ============================================================
CREATE TABLE docentes (
  id_docente   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombres      VARCHAR(80)  NOT NULL,
  apellidos    VARCHAR(80)  NOT NULL,
  correo       VARCHAR(120) NOT NULL UNIQUE,
  activo       TINYINT(1)   NOT NULL DEFAULT 1,
  creado_en    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Instructores SENA';


-- ============================================================
-- TABLA: jornadas
-- La jornada define hora de inicio/fin y minutos de gracia.
-- El retardo se calcula dinámicamente desde hora_registro.
-- ============================================================
CREATE TABLE jornadas (
  id_jornada     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre         VARCHAR(40) NOT NULL UNIQUE  COMMENT 'tarde, noche',
  hora_inicio    TIME        NOT NULL,
  hora_fin       TIME        NOT NULL,
  minutos_gracia SMALLINT    NOT NULL DEFAULT 10
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
  id_aprendiz      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  numero_documento VARCHAR(20)  NOT NULL UNIQUE,
  nombres          VARCHAR(80)  NOT NULL,
  apellidos        VARCHAR(80)  NOT NULL,
  id_ficha         INT UNSIGNED NOT NULL,
  activo           TINYINT(1)   NOT NULL DEFAULT 1
                   COMMENT '0 = retirado',
  CONSTRAINT fk_aprendiz_ficha FOREIGN KEY (id_ficha) REFERENCES fichas(id_ficha)
) ENGINE=InnoDB COMMENT='Aprendices, uno por ficha';

CREATE INDEX idx_aprendiz_ficha ON aprendices(id_ficha);


-- ============================================================
-- TABLA: sesiones_asistencia
-- Un día de clase abierto por el docente.
-- La etiqueta visual (lun-18) se genera en las vistas desde fecha_sesion.
-- ============================================================
CREATE TABLE sesiones_asistencia (
  id_sesion     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_ficha      INT UNSIGNED NOT NULL,
  fecha_sesion  DATE         NOT NULL,
  estado_sesion ENUM('abierta','cerrada','cancelada') NOT NULL DEFAULT 'abierta',
  hora_apertura DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  hora_cierre   DATETIME(3)  NULL,
  CONSTRAINT fk_sesion_ficha FOREIGN KEY (id_ficha) REFERENCES fichas(id_ficha),
  CONSTRAINT uq_ficha_fecha  UNIQUE (id_ficha, fecha_sesion)
) ENGINE=InnoDB COMMENT='Sesiones diarias de clase por ficha';

CREATE INDEX idx_sesion_ficha_fecha ON sesiones_asistencia(id_ficha, fecha_sesion);


-- ============================================================
-- TABLA: tokens_qr
-- Tokens dinámicos por sesión. Rotan cada N minutos.
-- ============================================================
CREATE TABLE tokens_qr (
  id_token    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_sesion   INT UNSIGNED NOT NULL,
  token_valor VARCHAR(64)  NOT NULL UNIQUE,
  creado_en   DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  expira_en   DATETIME(3)  NOT NULL,
  activo      TINYINT(1)   NOT NULL DEFAULT 1,
  veces_usado SMALLINT     NOT NULL DEFAULT 0,
  CONSTRAINT fk_token_sesion FOREIGN KEY (id_sesion) REFERENCES sesiones_asistencia(id_sesion)
) ENGINE=InnoDB COMMENT='Tokens QR dinámicos por sesión';

CREATE INDEX idx_token_sesion_activo ON tokens_qr(id_sesion, activo);


-- ============================================================
-- TABLA: asistencias
-- Registro individual por aprendiz por sesión.
-- minutos_retardo se calcula al momento del registro y se guarda.
-- Columnas de geolocalización preparadas para fase futura (NULL).
-- ============================================================
CREATE TABLE asistencias (
  id_asistencia    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_sesion        INT UNSIGNED NOT NULL,
  id_aprendiz      INT UNSIGNED NOT NULL,
  id_token_usado   INT UNSIGNED NULL        COMMENT 'NULL si fue manual',
  estado           ENUM('presente','ausente','excusa','retardo') NOT NULL DEFAULT 'ausente',
  metodo_registro  ENUM('qr','manual')      NOT NULL DEFAULT 'manual',
  hora_registro    DATETIME(3)  NULL        COMMENT 'Hora exacta de entrada',
  minutos_retardo  SMALLINT     NOT NULL DEFAULT 0,
  -- Geolocalización — fase futura
  ubicacion_valida TINYINT(1)   NULL        COMMENT 'NULL=no verificado',
  latitud          DECIMAL(10,7) NULL,
  longitud         DECIMAL(10,7) NULL,
  observacion      VARCHAR(255) NULL,
  registrado_en    DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  CONSTRAINT fk_asistencia_sesion   FOREIGN KEY (id_sesion)      REFERENCES sesiones_asistencia(id_sesion),
  CONSTRAINT fk_asistencia_aprendiz FOREIGN KEY (id_aprendiz)    REFERENCES aprendices(id_aprendiz),
  CONSTRAINT fk_asistencia_token    FOREIGN KEY (id_token_usado) REFERENCES tokens_qr(id_token),
  CONSTRAINT uq_sesion_aprendiz     UNIQUE (id_sesion, id_aprendiz)
) ENGINE=InnoDB COMMENT='Asistencia individual. QR/manual, retardos, geoloc futura';

CREATE INDEX idx_asistencia_sesion   ON asistencias(id_sesion);
CREATE INDEX idx_asistencia_aprendiz ON asistencias(id_aprendiz);