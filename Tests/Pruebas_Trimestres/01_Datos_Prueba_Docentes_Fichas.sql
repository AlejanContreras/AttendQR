-- ============================================================
--  AttendQR — PRUEBAS LOCALES (NO correr en producción)
--  Paso 1 de 3: docentes y fichas de prueba
--
--  Crea:
--    Docente 1 → docente1@attendqr.com   /  12345678
--    Docente 2 → docente2@attendqr.com   /  12345678
--
--    Ficha 3000001 · tarde · Docente 1   (la que pasa de T3 a T4)
--    Ficha 3000002 · noche · Docente 1   (para probar sesión de 60 min)
--    Ficha 3000003 · tarde · Docente 2   (para comprobar que el Docente 1
--                                         no ve ni toca fichas ajenas)
--
--  Se puede correr varias veces: no duplica nada.
--  Después de esto: importar Aprendices_Prueba_Trimestres.csv desde
--  Estudiantes → Importar CSV (con la sesión del Docente 1).
-- ============================================================

USE attendqr;
SET NAMES utf8mb4;

-- Hash bcrypt de "12345678"
INSERT INTO docentes (nombres, apellidos, correo, password_hash, activo)
VALUES
  ('Docente', 'Uno', 'docente1@attendqr.com', '$2y$12$4Q.Zd0Ts7pBpYFEYHk8tFeo7E8YFWdTFwepDTcruzwRYpaj/fuVLm', 1),
  ('Docente', 'Dos', 'docente2@attendqr.com', '$2y$12$4Q.Zd0Ts7pBpYFEYHk8tFeo7E8YFWdTFwepDTcruzwRYpaj/fuVLm', 1)
ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), activo = 1;

INSERT IGNORE INTO fichas (codigo_ficha, nombre_programa, nombre_materia, id_docente, id_jornada, activa)
SELECT '3000001', 'Programación de Software (Prueba)', 'Bases de Datos',
       d.id_docente, j.id_jornada, 1
  FROM docentes d, jornadas j
 WHERE d.correo = 'docente1@attendqr.com' AND j.nombre = 'tarde';

INSERT IGNORE INTO fichas (codigo_ficha, nombre_programa, nombre_materia, id_docente, id_jornada, activa)
SELECT '3000002', 'Programación de Software Noche (Prueba)', 'Algoritmos',
       d.id_docente, j.id_jornada, 1
  FROM docentes d, jornadas j
 WHERE d.correo = 'docente1@attendqr.com' AND j.nombre = 'noche';

INSERT IGNORE INTO fichas (codigo_ficha, nombre_programa, nombre_materia, id_docente, id_jornada, activa)
SELECT '3000003', 'Ficha del Docente 2 (Prueba)', 'Redes',
       d.id_docente, j.id_jornada, 1
  FROM docentes d, jornadas j
 WHERE d.correo = 'docente2@attendqr.com' AND j.nombre = 'tarde';

-- Verificación
SELECT f.codigo_ficha, f.nombre_programa, j.nombre AS jornada, d.correo AS docente
  FROM fichas f
  JOIN jornadas j ON j.id_jornada = f.id_jornada
  JOIN docentes d ON d.id_docente = f.id_docente
 WHERE f.codigo_ficha IN ('3000001','3000002','3000003');
