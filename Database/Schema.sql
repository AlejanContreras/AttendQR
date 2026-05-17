-- ============================================================
--  AttendQR — Sistema de Control de Asistencia por QR
--  ARCHIVO : schema.sql
--  MOTOR   : MySQL 8.0+  |  Motor de tablas: InnoDB
--  CHARSET : utf8mb4  →  soporta español, tildes y emojis
--  AUTOR   : generado para MVP AttendQR
-- ============================================================
--  ORDEN DE EJECUCIÓN:
--    1. Ejecutar schema.sql   (crea BD y tablas)
--    2. Ejecutar seeds.sql    (inserta datos de prueba)
-- ============================================================


-- ------------------------------------------------------------
--  BASE DE DATOS
-- ------------------------------------------------------------

CREATE DATABASE IF NOT EXISTS attendqr
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE attendqr;

-- Desactivar verificación de FK durante la creación
SET FOREIGN_KEY_CHECKS = 0;


-- ============================================================
--  TABLA: usuarios
-- ============================================================
--  Almacena todos los usuarios del sistema sin distinción.
--  El campo `rol` determina si es docente o alumno.
--  Un mismo modelo de autenticación sirve para ambos roles.
--  `activo` permite suspender cuentas sin borrar el historial.
-- ============================================================

CREATE TABLE IF NOT EXISTS usuarios (
    id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    nombre        VARCHAR(120)  NOT NULL                    COMMENT 'Nombre completo del usuario',
    correo        VARCHAR(180)  NOT NULL                    COMMENT 'Correo único, usado para iniciar sesión',
    password_hash VARCHAR(255)  NOT NULL                    COMMENT 'Hash bcrypt de la contraseña',
    rol           ENUM(
                      'docente',
                      'alumno'
                  )             NOT NULL                    COMMENT 'Tipo de usuario dentro del sistema',
    activo        TINYINT(1)    NOT NULL DEFAULT 1          COMMENT '1 = activo, 0 = suspendido',
    creado_en     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Llave primaria
    PRIMARY KEY (id),

    -- Restricciones
    UNIQUE KEY uq_usuarios_correo (correo),

    -- Índices de consulta frecuente
    INDEX idx_usuarios_rol    (rol),
    INDEX idx_usuarios_activo (activo)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Docentes y alumnos del sistema';


-- ============================================================
--  TABLA: cursos
-- ============================================================
--  Representa una materia o asignatura.
--  Cada curso tiene un único docente responsable.
--  `codigo` es el identificador legible (ej: "POO-301").
--  `activo` permite archivar cursos sin borrar su historial.
-- ============================================================

CREATE TABLE IF NOT EXISTS cursos (
    id         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    docente_id INT UNSIGNED  NOT NULL                    COMMENT 'Docente responsable del curso',
    nombre     VARCHAR(120)  NOT NULL                    COMMENT 'Nombre completo de la materia',
    codigo     VARCHAR(30)   NOT NULL                    COMMENT 'Código único legible (ej: MAT-101)',
    descripcion TEXT                                     COMMENT 'Descripción opcional del curso',
    activo     TINYINT(1)    NOT NULL DEFAULT 1          COMMENT '1 = activo, 0 = archivado',
    creado_en  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_cursos_codigo (codigo),

    INDEX idx_cursos_docente (docente_id),
    INDEX idx_cursos_activo  (activo),

    -- Un curso no puede existir sin su docente
    CONSTRAINT fk_cursos_docente
        FOREIGN KEY (docente_id)
        REFERENCES usuarios (id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Materias o asignaturas del sistema';


-- ============================================================
--  TABLA: matriculas
-- ============================================================
--  Tabla pivote entre alumnos y cursos (relación N:M).
--  Permite validar que el alumno pertenece al curso antes
--  de registrar su asistencia.
--  La restricción UNIQUE evita matrículas duplicadas.
-- ============================================================

CREATE TABLE IF NOT EXISTS matriculas (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    alumno_id       INT UNSIGNED  NOT NULL                COMMENT 'Alumno matriculado',
    curso_id        INT UNSIGNED  NOT NULL                COMMENT 'Curso en el que se matricula',
    fecha_matricula DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    -- Un alumno no puede estar matriculado dos veces en el mismo curso
    UNIQUE KEY uq_matricula (alumno_id, curso_id),

    INDEX idx_matriculas_curso (curso_id),

    CONSTRAINT fk_matriculas_alumno
        FOREIGN KEY (alumno_id)
        REFERENCES usuarios (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_matriculas_curso
        FOREIGN KEY (curso_id)
        REFERENCES cursos (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Inscripción de alumnos en cursos';


-- ============================================================
--  TABLA: sesiones
-- ============================================================
--  Una clase concreta de un curso en una fecha y hora.
--  El docente crea la sesión y luego genera el QR para ella.
--
--  CICLO DE VIDA DEL ESTADO:
--    programada → activa → cerrada
--
--  `fecha_inicio` y `fecha_fin` definen la ventana de tiempo
--  en que la sesión acepta registros de asistencia.
-- ============================================================

CREATE TABLE IF NOT EXISTS sesiones (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    curso_id     INT UNSIGNED  NOT NULL                   COMMENT 'Curso al que pertenece esta sesión',
    fecha_inicio DATETIME      NOT NULL                   COMMENT 'Inicio de la ventana de asistencia',
    fecha_fin    DATETIME      NOT NULL                   COMMENT 'Cierre de la ventana de asistencia',
    estado       ENUM(
                     'programada',
                     'activa',
                     'cerrada'
                 )             NOT NULL DEFAULT 'programada' COMMENT 'Estado actual de la sesión',
    notas        VARCHAR(255)            DEFAULT NULL     COMMENT 'Tema de la clase u observaciones',
    creado_en    TIMESTAMP     NOT NULL  DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    INDEX idx_sesiones_curso        (curso_id),
    INDEX idx_sesiones_estado       (estado),
    INDEX idx_sesiones_fecha_inicio (fecha_inicio),

    -- Índice compuesto: consulta principal del docente
    INDEX idx_sesiones_curso_estado (curso_id, estado, fecha_inicio),

    CONSTRAINT fk_sesiones_curso
        FOREIGN KEY (curso_id)
        REFERENCES cursos (id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Clases concretas programadas por el docente';


-- ============================================================
--  TABLA: tokens_qr
-- ============================================================
--  Tokens dinámicos asociados a una sesión.
--  Una sesión puede generar múltiples tokens a lo largo
--  de su duración (el docente rota el QR para evitar fraude),
--  pero solo UNO puede estar activo en cada momento.
--
--  `token` es el valor que viaja dentro del código QR.
--  Es UNIQUE globalmente: no puede repetirse en toda la tabla.
--
--  `generado_en` registra cuándo se creó cada token.
--  `expira_en`   define hasta cuándo es válido para escanear.
-- ============================================================

CREATE TABLE IF NOT EXISTS tokens_qr (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    sesion_id   INT UNSIGNED  NOT NULL                   COMMENT 'Sesión a la que pertenece este token',
    token       VARCHAR(128)  NOT NULL                   COMMENT 'Valor único del token dentro del QR',
    generado_en DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Momento de creación del token',
    expira_en   DATETIME      NOT NULL                   COMMENT 'Momento en que el token deja de ser válido',
    activo      TINYINT(1)    NOT NULL DEFAULT 1         COMMENT '1 = vigente, 0 = reemplazado o vencido',

    PRIMARY KEY (id),

    -- El valor del token no puede repetirse en ninguna sesión
    UNIQUE KEY uq_tokens_qr_valor (token),

    INDEX idx_tokens_qr_sesion (sesion_id),

    -- Índice compuesto: búsqueda del token activo vigente
    INDEX idx_tokens_qr_busqueda (sesion_id, activo, expira_en),

    CONSTRAINT fk_tokens_qr_sesion
        FOREIGN KEY (sesion_id)
        REFERENCES sesiones (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Tokens dinámicos generados por sesión para el QR';


-- ============================================================
--  TABLA: asistencias
-- ============================================================
--  Registro final e inamovible. Una fila = un alumno
--  presente en una sesión concreta.
--
--  RESTRICCIÓN CRÍTICA:
--    UNIQUE (alumno_id, sesion_id) → un alumno no puede
--    registrar asistencia dos veces en la misma sesión.
--    Esta restricción actúa como última línea de defensa,
--    incluso si la lógica PHP fallara.
--
--  `token_qr_id` guarda exactamente con qué token se
--  registró, permitiendo auditoría posterior.
--
--  `ip_origen` es dato de auditoría, no de validación.
-- ============================================================

CREATE TABLE IF NOT EXISTS asistencias (
    id             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    alumno_id      INT UNSIGNED  NOT NULL               COMMENT 'Alumno que registró asistencia',
    sesion_id      INT UNSIGNED  NOT NULL               COMMENT 'Sesión en la que se registró',
    token_qr_id    INT UNSIGNED  NOT NULL               COMMENT 'Token QR usado para el registro',
    fecha_registro DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_origen      VARCHAR(45)            DEFAULT NULL  COMMENT 'IP del dispositivo (IPv4/IPv6, auditoría)',

    PRIMARY KEY (id),

    -- Defensa principal contra doble registro
    UNIQUE KEY uq_asistencia (alumno_id, sesion_id),

    INDEX idx_asistencias_sesion    (sesion_id),
    INDEX idx_asistencias_token_qr  (token_qr_id),

    -- Índice compuesto: reporte de asistencia por alumno
    INDEX idx_asistencias_alumno_sesion (alumno_id, sesion_id),

    CONSTRAINT fk_asistencias_alumno
        FOREIGN KEY (alumno_id)
        REFERENCES usuarios (id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_asistencias_sesion
        FOREIGN KEY (sesion_id)
        REFERENCES sesiones (id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_asistencias_token_qr
        FOREIGN KEY (token_qr_id)
        REFERENCES tokens_qr (id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Registro definitivo de asistencia por alumno y sesión';


-- Reactivar verificación de llaves foráneas
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
--  FIN DE schema.sql
--  Siguiente paso: ejecutar seeds.sql
-- ============================================================