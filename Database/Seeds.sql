-- ============================================================
--  AttendQR — SEEDS MVP
--  Datos mínimos realistas para probar relaciones, vistas y QR
-- ============================================================

USE attendqr;

-- ============================================================
-- 1. DOCENTES
-- ============================================================
INSERT INTO docentes (id_docente, nombres, apellidos, correo, activo) VALUES
(1, 'Carlos Alberto', 'Ramírez Torres', 'carlos.ramirez@sena.edu.co', 1);


-- ============================================================
-- 2. JORNADAS
-- ============================================================
INSERT INTO jornadas (id_jornada, nombre, hora_inicio, hora_fin, minutos_gracia) VALUES
(1, 'tarde', '14:00:00', '18:00:00', 10),
(2, 'noche', '18:00:00', '22:00:00', 15);


-- ============================================================
-- 3. TRIMESTRES
-- ============================================================
INSERT INTO trimestres (id_trimestre, nombre, fecha_inicio, fecha_fin, activo) VALUES
(1, 'Trimestre 2 - 2025', '2025-04-14', '2025-07-11', 1);


-- ============================================================
-- 4. FICHAS
-- ============================================================
INSERT INTO fichas (id_ficha, codigo_ficha, nombre_programa, id_docente, id_jornada, id_trimestre, activa) VALUES
(1, '2879345', 'Técnico en Programación de Software', 1, 1, 1, 1);


-- ============================================================
-- 5. APRENDICES (8 activos, 1 retirado)
-- ============================================================
INSERT INTO aprendices (id_aprendiz, numero_documento, nombres, apellidos, id_ficha, activo) VALUES
(1, '1020456789', 'Sebastián',  'García López',    1, 1),
(2, '1045678901', 'Valentina',  'Martínez Ruiz',   1, 1),
(3, '1067890123', 'Mateo',      'Hernández Peña',  1, 1),
(4, '1089012345', 'Salomé',     'Vargas Díaz',     1, 1),
(5, '1001234567', 'Tomás',      'Castro Moreno',   1, 1),
(6, '1023456789', 'Isabella',   'Rodríguez Silva', 1, 1),
(7, '1056789012', 'Daniel',     'Jiménez Torres',  1, 1),
(8, '1078901234', 'Laura',      'Gómez Restrepo',  1, 0); -- retirada


-- ============================================================
-- 6. SESIONES — mayo 2025 (lun-mar-mié-vie-sáb)
--    Sesiones cerradas: 12, 13, 14, 16, 19, 20
--    Sesión abierta:   21 (hoy)
--    Sesión cancelada: 23
-- ============================================================
INSERT INTO sesiones_asistencia (id_sesion, id_ficha, fecha_sesion, estado_sesion, hora_apertura, hora_cierre) VALUES
(1, 1, '2025-05-12', 'cerrada',   '2025-05-12 14:01:10.000', '2025-05-12 18:03:44.000'),
(2, 1, '2025-05-13', 'cerrada',   '2025-05-13 14:00:55.000', '2025-05-13 18:02:20.000'),
(3, 1, '2025-05-14', 'cerrada',   '2025-05-14 14:02:03.000', '2025-05-14 18:05:11.000'),
(4, 1, '2025-05-16', 'cerrada',   '2025-05-16 14:00:30.000', '2025-05-16 18:04:00.000'),
(5, 1, '2025-05-19', 'cerrada',   '2025-05-19 14:01:45.000', '2025-05-19 18:06:22.000'),
(6, 1, '2025-05-20', 'cerrada',   '2025-05-20 14:00:58.000', '2025-05-20 18:01:33.000'),
(7, 1, '2025-05-21', 'abierta',   '2025-05-21 14:01:08.320', NULL),
(8, 1, '2025-05-23', 'cancelada', '2025-05-23 00:00:00.000', NULL);


-- ============================================================
-- 7. TOKENS QR
-- Sesiones cerradas: tokens expirados
-- Sesión 7 (abierta): token activo
-- ============================================================
INSERT INTO tokens_qr (id_token, id_sesion, token_valor, creado_en, expira_en, activo, veces_usado) VALUES
(1, 1, 'tok_1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d', '2025-05-12 14:01:10.000', '2025-05-12 14:31:10.000', 0, 5),
(2, 2, 'tok_2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e', '2025-05-13 14:00:55.000', '2025-05-13 14:30:55.000', 0, 4),
(3, 3, 'tok_3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f', '2025-05-14 14:02:03.000', '2025-05-14 14:32:03.000', 0, 6),
(4, 7, 'tok_4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9g', '2025-05-21 14:01:08.320', '2025-05-21 14:31:08.320', 1, 3);


-- ============================================================
-- 8. ASISTENCIAS
-- Variedad: A/F/E/R, qr y manual, retardos reales
-- Solo aprendices activos (1-7), Laura(8) está retirada
-- ============================================================

-- SESIÓN 1 — lun-12
INSERT INTO asistencias (id_sesion, id_aprendiz, id_token_usado, estado, metodo_registro, hora_registro, minutos_retardo) VALUES
(1, 1, 1, 'presente', 'qr',     '2025-05-12 14:03:22.114', 0),
(1, 2, 1, 'presente', 'qr',     '2025-05-12 14:05:41.330', 0),
(1, 3, 1, 'retardo',  'qr',     '2025-05-12 14:17:55.882', 17),
(1, 4, 1, 'presente', 'qr',     '2025-05-12 14:06:12.441', 0),
(1, 5, NULL,'ausente','manual',  NULL,                      0),
(1, 6, 1, 'presente', 'qr',     '2025-05-12 14:08:04.901', 0),
(1, 7, NULL,'excusa', 'manual',  NULL,                      0);

-- SESIÓN 2 — mar-13
INSERT INTO asistencias (id_sesion, id_aprendiz, id_token_usado, estado, metodo_registro, hora_registro, minutos_retardo) VALUES
(2, 1, NULL,'presente','manual', '2025-05-13 14:02:10.000', 0),
(2, 2, NULL,'presente','manual', '2025-05-13 14:04:33.000', 0),
(2, 3, NULL,'retardo', 'manual', '2025-05-13 14:22:18.000', 22),
(2, 4, NULL,'ausente', 'manual', NULL,                      0),
(2, 5, NULL,'presente','manual', '2025-05-13 14:01:55.000', 0),
(2, 6, NULL,'presente','manual', '2025-05-13 14:07:44.000', 0),
(2, 7, NULL,'presente','manual', '2025-05-13 14:09:01.000', 0);

-- SESIÓN 3 — mié-14
INSERT INTO asistencias (id_sesion, id_aprendiz, id_token_usado, estado, metodo_registro, hora_registro, minutos_retardo) VALUES
(3, 1, 3, 'presente', 'qr',     '2025-05-14 14:03:11.220', 0),
(3, 2, 3, 'excusa',   'qr',     NULL,                      0),
(3, 3, 3, 'presente', 'qr',     '2025-05-14 14:04:09.001', 0),
(3, 4, 3, 'presente', 'qr',     '2025-05-14 14:02:55.340', 0),
(3, 5, NULL,'ausente','manual',  NULL,                      0),
(3, 6, 3, 'presente', 'qr',     '2025-05-14 14:05:33.110', 0),
(3, 7, 3, 'retardo',  'qr',     '2025-05-14 14:31:07.445', 31);

-- SESIÓN 4 — vie-16
INSERT INTO asistencias (id_sesion, id_aprendiz, id_token_usado, estado, metodo_registro, hora_registro, minutos_retardo) VALUES
(4, 1, NULL,'presente','manual', '2025-05-16 14:01:22.000', 0),
(4, 2, NULL,'presente','manual', '2025-05-16 14:03:44.000', 0),
(4, 3, NULL,'ausente', 'manual', NULL,                      0),
(4, 4, NULL,'presente','manual', '2025-05-16 14:00:59.000', 0),
(4, 5, NULL,'presente','manual', '2025-05-16 14:08:11.000', 0),
(4, 6, NULL,'retardo', 'manual', '2025-05-16 14:14:38.000', 14),
(4, 7, NULL,'presente','manual', '2025-05-16 14:02:01.000', 0);

-- SESIÓN 5 — lun-19
INSERT INTO asistencias (id_sesion, id_aprendiz, id_token_usado, estado, metodo_registro, hora_registro, minutos_retardo) VALUES
(5, 1, NULL,'presente','manual', '2025-05-19 14:00:33.000', 0),
(5, 2, NULL,'presente','manual', '2025-05-19 14:02:17.000', 0),
(5, 3, NULL,'presente','manual', '2025-05-19 14:04:55.000', 0),
(5, 4, NULL,'excusa',  'manual', NULL,                      0),
(5, 5, NULL,'ausente', 'manual', NULL,                      0),
(5, 6, NULL,'presente','manual', '2025-05-19 14:01:48.000', 0),
(5, 7, NULL,'presente','manual', '2025-05-19 14:03:22.000', 0);

-- SESIÓN 6 — mar-20
INSERT INTO asistencias (id_sesion, id_aprendiz, id_token_usado, estado, metodo_registro, hora_registro, minutos_retardo) VALUES
(6, 1, NULL,'presente','manual', '2025-05-20 14:01:10.000', 0),
(6, 2, NULL,'retardo', 'manual', '2025-05-20 14:13:44.000', 13),
(6, 3, NULL,'presente','manual', '2025-05-20 14:02:30.000', 0),
(6, 4, NULL,'presente','manual', '2025-05-20 14:00:55.000', 0),
(6, 5, NULL,'ausente', 'manual', NULL,                      0),
(6, 6, NULL,'excusa',  'manual', NULL,                      0),
(6, 7, NULL,'presente','manual', '2025-05-20 14:04:11.000', 0);

-- SESIÓN 7 — mar-21 (ABIERTA — parcial, algunos aún no llegan)
INSERT INTO asistencias (id_sesion, id_aprendiz, id_token_usado, estado, metodo_registro, hora_registro, minutos_retardo) VALUES
(7, 1, 4, 'presente', 'qr',     '2025-05-21 14:02:44.512', 0),
(7, 2, 4, 'presente', 'qr',     '2025-05-21 14:04:19.230', 0),
(7, 3, 4, 'retardo',  'qr',     '2025-05-21 14:19:08.771', 19),
(7, 4, NULL,'ausente','manual',  NULL,                      0),
(7, 5, NULL,'ausente','manual',  NULL,                      0),
(7, 6, NULL,'ausente','manual',  NULL,                      0),
(7, 7, NULL,'ausente','manual',  NULL,                      0);