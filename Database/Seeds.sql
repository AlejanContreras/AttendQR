-- ============================================================
--  AttendQR — Sistema de Control de Asistencia por QR
--  ARCHIVO : seeds.sql
--  USO     : Datos de prueba para desarrollo local (XAMPP)
-- ============================================================
--  EJECUTAR DESPUÉS DE: schema.sql
-- ============================================================
--
--  CUENTAS DE PRUEBA (todas usan la contraseña: Test1234)
--  El hash corresponde a: password_hash('Test1234', PASSWORD_BCRYPT)
--
--  correo                          | rol     | contraseña
--  --------------------------------|---------|------------
--  ana.garcia@attendqr.edu         | docente | Test1234
--  carlos.perez@attendqr.edu       | alumno  | Test1234
--  laura.gomez@attendqr.edu        | alumno  | Test1234
--  miguel.torres@attendqr.edu      | alumno  | Test1234
--  valentina.cruz@attendqr.edu     | alumno  | Test1234
--  sebastian.lopez@attendqr.edu    | alumno  | Test1234
--
-- ============================================================

USE attendqr;

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;


-- ------------------------------------------------------------
--  1. USUARIOS
--     1 docente + 5 alumnos
-- ------------------------------------------------------------

INSERT INTO usuarios
    (id, nombre, correo, password_hash, rol, activo)
VALUES
    (1,
     'Ana García Mejía',
     'ana.garcia@attendqr.edu',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'docente', 1),

    (2,
     'Carlos Pérez Martínez',
     'carlos.perez@attendqr.edu',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'alumno', 1),

    (3,
     'Laura Gómez Sánchez',
     'laura.gomez@attendqr.edu',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'alumno', 1),

    (4,
     'Miguel Torres Vargas',
     'miguel.torres@attendqr.edu',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'alumno', 1),

    (5,
     'Valentina Cruz Herrera',
     'valentina.cruz@attendqr.edu',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'alumno', 1),

    (6,
     'Sebastián López Ríos',
     'sebastian.lopez@attendqr.edu',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'alumno', 1);


-- ------------------------------------------------------------
--  2. CURSOS
--     2 materias dictadas por la docente Ana (id=1)
-- ------------------------------------------------------------

INSERT INTO cursos
    (id, docente_id, nombre, codigo, descripcion, activo)
VALUES
    (1, 1,
     'Programación Orientada a Objetos',
     'POO-301',
     'Clases, herencia, polimorfismo y patrones de diseño básicos.',
     1),

    (2, 1,
     'Bases de Datos I',
     'BD-201',
     'Modelado relacional, SQL, normalización y diseño de esquemas.',
     1);


-- ------------------------------------------------------------
--  3. MATRÍCULAS
--     Los 5 alumnos matriculados en ambos cursos
-- ------------------------------------------------------------

INSERT INTO matriculas
    (alumno_id, curso_id)
VALUES
    -- Curso POO-301
    (2, 1), (3, 1), (4, 1), (5, 1), (6, 1),
    -- Curso BD-201
    (2, 2), (3, 2), (4, 2), (5, 2), (6, 2);


-- ------------------------------------------------------------
--  4. SESIONES
--
--  sesion_id=1 → POO-301, ocurrió hace 2 días, CERRADA
--                (tiene asistencias históricas)
--
--  sesion_id=2 → BD-201, está ocurriendo AHORA, ACTIVA
--                (tiene token vigente, alumnos escaneando)
-- ------------------------------------------------------------

INSERT INTO sesiones
    (id, curso_id, fecha_inicio, fecha_fin, estado, notas)
VALUES
    (1, 1,
     DATE_SUB(NOW(), INTERVAL 2 DAY),
     DATE_SUB(NOW(), INTERVAL 2 DAY) + INTERVAL 90 MINUTE,
     'cerrada',
     'Tema: Herencia y clases abstractas en Java'),

    (2, 2,
     NOW(),
     NOW() + INTERVAL 120 MINUTE,
     'activa',
     'Tema: Normalización — Tercera Forma Normal y FNBC');


-- ------------------------------------------------------------
--  5. TOKENS QR
--
--  token_id=1 → sesión 1, INACTIVO y expirado (sesión cerrada)
--  token_id=2 → sesión 2, INACTIVO, fue el primer token
--               (ya fue rotado por el docente)
--  token_id=3 → sesión 2, ACTIVO y VIGENTE
--               (es el que está proyectado en pantalla ahora)
-- ------------------------------------------------------------

INSERT INTO tokens_qr
    (id, sesion_id, token, generado_en, expira_en, activo)
VALUES
    -- Token histórico de la sesión cerrada
    (1, 1,
     'tok_a3f9c2b1e8d047a65c91b234f0e7d5a82c3f6b19',
     DATE_SUB(NOW(), INTERVAL 2 DAY),
     DATE_SUB(NOW(), INTERVAL 2 DAY) + INTERVAL 5 MINUTE,
     0),

    -- Primer token de la sesión activa (ya rotado, inactivo)
    (2, 2,
     'tok_b7e4d1a09f2c58e3b6d9a4c71f0e2b5d83a7c1e6',
     DATE_SUB(NOW(), INTERVAL 10 MINUTE),
     DATE_SUB(NOW(), INTERVAL 5 MINUTE),
     0),

    -- Token ACTUAL de la sesión activa (vigente por 5 minutos más)
    (3, 2,
     'tok_c1f8b3e6a2d94c07b5e1a8f3d6c09b2e7a4f1d8c',
     DATE_SUB(NOW(), INTERVAL 2 MINUTE),
     DATE_ADD(NOW(), INTERVAL 5 MINUTE),
     1);


-- ------------------------------------------------------------
--  6. ASISTENCIAS
--
--  Sesión 1 (cerrada): 4 de 5 alumnos asistieron
--                      Sebastián (id=6) estuvo ausente
--
--  Sesión 2 (activa):  2 alumnos ya escanearon el QR
--                      Los otros 3 aún no han registrado
-- ------------------------------------------------------------

INSERT INTO asistencias
    (alumno_id, sesion_id, token_qr_id, fecha_registro, ip_origen)
VALUES
    -- Sesión 1 — cuatro asistentes registrados
    (2, 1, 1,
     DATE_SUB(NOW(), INTERVAL 2 DAY) + INTERVAL 4 MINUTE,
     '192.168.1.102'),

    (3, 1, 1,
     DATE_SUB(NOW(), INTERVAL 2 DAY) + INTERVAL 7 MINUTE,
     '192.168.1.103'),

    (4, 1, 1,
     DATE_SUB(NOW(), INTERVAL 2 DAY) + INTERVAL 11 MINUTE,
     '192.168.1.104'),

    (5, 1, 1,
     DATE_SUB(NOW(), INTERVAL 2 DAY) + INTERVAL 14 MINUTE,
     '192.168.1.105'),

    -- Sesión 2 — dos alumnos ya escanearon el token activo (id=3)
    (2, 2, 3,
     DATE_SUB(NOW(), INTERVAL 90 SECOND),
     '192.168.1.102'),

    (3, 2, 3,
     DATE_SUB(NOW(), INTERVAL 45 SECOND),
     '192.168.1.103');


SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
--  FIN DE seeds.sql
-- ============================================================
--
--  CONSULTAS DE VERIFICACIÓN RÁPIDA:
--
--  -- Ver todos los usuarios cargados
--  SELECT id, nombre, rol, activo FROM usuarios;
--
--  -- Ver matrículas con nombres
--  SELECT u.nombre AS alumno, c.nombre AS curso
--  FROM matriculas m
--  JOIN usuarios u ON m.alumno_id = u.id
--  JOIN cursos   c ON m.curso_id  = c.id
--  ORDER BY c.nombre, u.nombre;
--
--  -- Ver token activo de la sesión 2
--  SELECT token, generado_en, expira_en, activo
--  FROM tokens_qr
--  WHERE sesion_id = 2 AND activo = 1;
--
--  -- Ver asistencias con nombres de alumno y sesión
--  SELECT u.nombre AS alumno, s.notas AS sesion, a.fecha_registro
--  FROM asistencias a
--  JOIN usuarios u ON a.alumno_id = u.id
--  JOIN sesiones s ON a.sesion_id = s.id
--  ORDER BY a.sesion_id, a.fecha_registro;
--
-- ============================================================