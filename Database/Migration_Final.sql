-- ============================================================
--  AttendQR — Migration_Final.sql
--  Migración consolidada y definitiva (todas las versiones)
--
--  FLUJO DE INSTALACIÓN LIMPIA:
--    1. Crear base de datos:  CREATE DATABASE attendqr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--    2. Importar estructura:  mysql -u root attendqr < Database/Schema.sql
--    3. Aplicar esta migración: mysql -u root attendqr < Database/Migration_Final.sql
--    4. (Opcional) Datos de prueba: mysql -u root attendqr < Database/Seeds.sql
--
--  IDEMPOTENCIA: puede ejecutarse múltiples veces sobre la misma BD
--  sin errores. Verifica existencia de columnas, índices y datos
--  antes de modificar usando INFORMATION_SCHEMA.
--
--  CAMBIOS CONSOLIDADOS (v1.3 → v2.0 → v2.1 → v2.2):
--    [v1.3] sesiones_asistencia.nombre_materia      VARCHAR(120) NULL
--    [v1.3] sesiones_asistencia sin uq_ficha_fecha  (múltiples sesiones/día)
--    [v1.3] limite_retardo_minutos  DEFAULT 5
--    [v1.3] duracion_maxima_minutos DEFAULT 20
--    [v2.0] aprendices.cuenta_activada              TINYINT(1) DEFAULT 0
--    [v2.0] idx_aprendiz_cuenta
--    [v2.1] fichas.nombre_materia                   VARCHAR(150) NULL
--    [v2.1] fichas.id_trimestre                     → NULL permitido
--    [v2.2] jornadas: INSERT mañana (id=3)
-- ============================================================

USE attendqr;

-- ─────────────────────────────────────────────────────────────
--  Usamos un stored procedure para poder usar IF/THEN/END IF
--  dentro de SQL puro (fuera de stored routines no es válido
--  en MySQL). El procedure se crea, ejecuta y elimina en un
--  solo bloque.
-- ─────────────────────────────────────────────────────────────

DROP PROCEDURE IF EXISTS _attendqr_migrate_final;

DELIMITER $$

CREATE PROCEDURE _attendqr_migrate_final()
BEGIN

  -- ──────────────────────────────────────────────────────────
  --  [v1.3] sesiones_asistencia.nombre_materia
  --  Schema.sql (v1.2+) ya lo incluye. Esta guarda es por si
  --  alguien usa un Schema.sql anterior sin la columna.
  -- ──────────────────────────────────────────────────────────
  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'sesiones_asistencia'
      AND COLUMN_NAME  = 'nombre_materia'
  ) THEN
    ALTER TABLE sesiones_asistencia
      ADD COLUMN nombre_materia VARCHAR(120) NULL
        COMMENT 'Nombre de la materia o tema de la sesión'
      AFTER id_ficha;
  END IF;

  -- ──────────────────────────────────────────────────────────
  --  [v1.3] Eliminar UNIQUE(id_ficha, fecha_sesion)
  --  Permite múltiples sesiones por ficha en el mismo día.
  --  Control de sesiones ABIERTAS simultáneas está en PHP.
  -- ──────────────────────────────────────────────────────────
  IF EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA    = DATABASE()
      AND TABLE_NAME      = 'sesiones_asistencia'
      AND CONSTRAINT_NAME = 'uq_ficha_fecha'
  ) THEN
    ALTER TABLE sesiones_asistencia DROP INDEX uq_ficha_fecha;
  END IF;

  -- ──────────────────────────────────────────────────────────
  --  [v1.3] Ajustar defaults de la regla temporal.
  --  PRESENTE: hora_inicio_clase + 0..5 min
  --  RETARDO:  hora_inicio_clase + 6..20 min
  --  Rechaza:  hora_inicio_clase + 21+ min
  --  MODIFY COLUMN es idempotente por diseño.
  -- ──────────────────────────────────────────────────────────
  ALTER TABLE sesiones_asistencia
    MODIFY COLUMN limite_retardo_minutos  SMALLINT UNSIGNED NOT NULL DEFAULT 5
      COMMENT 'Minutos desde H en que se acepta PRESENTE. Superado → RETARDO.',
    MODIFY COLUMN duracion_maxima_minutos SMALLINT UNSIGNED NOT NULL DEFAULT 20
      COMMENT 'Minutos desde H que cierra la ventana. Superado → rechazado.';

  -- ──────────────────────────────────────────────────────────
  --  [v2.0] aprendices.cuenta_activada
  --  0 = pre-registrado (sin contraseña real)
  --  1 = auto-registro completado (puede iniciar sesión)
  -- ──────────────────────────────────────────────────────────
  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'aprendices'
      AND COLUMN_NAME  = 'cuenta_activada'
  ) THEN
    ALTER TABLE aprendices
      ADD COLUMN cuenta_activada TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '0 = pre-registrado, 1 = auto-registro completado'
      AFTER activo;

    -- Los aprendices ya existentes tienen contraseña válida → activar
    UPDATE aprendices SET cuenta_activada = 1;
  END IF;

  -- ──────────────────────────────────────────────────────────
  --  [v2.0] Índice para filtrar aprendices por estado de cuenta
  -- ──────────────────────────────────────────────────────────
  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'aprendices'
      AND INDEX_NAME   = 'idx_aprendiz_cuenta'
  ) THEN
    CREATE INDEX idx_aprendiz_cuenta ON aprendices(cuenta_activada);
  END IF;

  -- ──────────────────────────────────────────────────────────
  --  [v2.1] fichas.nombre_materia
  --  Permite al docente registrar la materia como dato permanente
  --  de la clase (Clase = ficha con materia, no solo sesión).
  -- ──────────────────────────────────────────────────────────
  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'fichas'
      AND COLUMN_NAME  = 'nombre_materia'
  ) THEN
    ALTER TABLE fichas
      ADD COLUMN nombre_materia VARCHAR(150) NULL DEFAULT NULL
        COMMENT 'Materia que imparte el docente en esta ficha'
      AFTER nombre_programa;
  END IF;

  -- ──────────────────────────────────────────────────────────
  --  [v2.1] fichas.id_trimestre → nullable
  --  Los docentes auto-gestionan sus fichas sin asignar trimestre.
  --  El trimestre es metadata del coordinador, no del flujo principal.
  -- ──────────────────────────────────────────────────────────
  ALTER TABLE fichas
    MODIFY COLUMN id_trimestre INT UNSIGNED NULL DEFAULT NULL;

  -- ──────────────────────────────────────────────────────────
  --  [v2.2] Jornada "mañana"
  --  Completa las tres jornadas estándar SENA:
  --    mañana (06:00-12:00), tarde (14:00-18:00), noche (18:00-22:00)
  -- ──────────────────────────────────────────────────────────
  IF NOT EXISTS (
    SELECT 1 FROM jornadas WHERE nombre = 'mañana'
  ) THEN
    INSERT INTO jornadas (nombre, hora_inicio, hora_fin, minutos_gracia)
    VALUES ('mañana', '06:00:00', '12:00:00', 10);
  END IF;

  -- ──────────────────────────────────────────────────────────
  --  [P8] sesiones_asistencia: columnas de geolocalización
  --  Permite al docente activar validación de radio al iniciar
  --  una sesión. RADIO fijo: 30 m (constante en backend PHP).
  -- ──────────────────────────────────────────────────────────
  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'sesiones_asistencia'
      AND COLUMN_NAME  = 'ubicacion_activa'
  ) THEN
    ALTER TABLE sesiones_asistencia
      ADD COLUMN ubicacion_activa  TINYINT(1)    NOT NULL DEFAULT 0
        COMMENT '1 = sesión exige validación de radio geográfico'
      AFTER duracion_maxima_minutos,
      ADD COLUMN lat_docente       DECIMAL(10,7) NULL
        COMMENT 'Latitud del docente al abrir la sesión'
      AFTER ubicacion_activa,
      ADD COLUMN lng_docente       DECIMAL(10,7) NULL
        COMMENT 'Longitud del docente al abrir la sesión'
      AFTER lat_docente,
      ADD COLUMN accuracy_docente  DECIMAL(8,2)  NULL
        COMMENT 'Precisión GPS del docente en metros'
      AFTER lng_docente;
  END IF;

END$$

DELIMITER ;

CALL _attendqr_migrate_final();
DROP PROCEDURE IF EXISTS _attendqr_migrate_final;

-- ─────────────────────────────────────────────────────────────
--  Verificación final (solo informativa, no falla si falta algo)
-- ─────────────────────────────────────────────────────────────
SELECT
  'fichas.nombre_materia'       AS cambio,
  IF(COUNT(*) > 0, 'OK ✓', 'FALTA ✗') AS estado
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fichas' AND COLUMN_NAME = 'nombre_materia'

UNION ALL

SELECT
  'fichas.id_trimestre nullable',
  IF(IS_NULLABLE = 'YES', 'OK ✓', 'FALTA ✗')
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fichas' AND COLUMN_NAME = 'id_trimestre'

UNION ALL

SELECT
  'aprendices.cuenta_activada',
  IF(COUNT(*) > 0, 'OK ✓', 'FALTA ✗')
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'aprendices' AND COLUMN_NAME = 'cuenta_activada'

UNION ALL

SELECT
  'jornada mañana',
  IF(COUNT(*) > 0, 'OK ✓', 'FALTA ✗')
FROM jornadas WHERE nombre = 'mañana'

UNION ALL

SELECT
  'sesiones.nombre_materia',
  IF(COUNT(*) > 0, 'OK ✓', 'FALTA ✗')
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sesiones_asistencia' AND COLUMN_NAME = 'nombre_materia'

UNION ALL

SELECT
  'sesiones.ubicacion_activa (P8)',
  IF(COUNT(*) > 0, 'OK ✓', 'FALTA ✗')
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sesiones_asistencia' AND COLUMN_NAME = 'ubicacion_activa';
