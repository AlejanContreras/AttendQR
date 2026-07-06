-- ============================================================
--  AttendQR — Migration v2.0
--  P4: Gestión de Aprendices (Registro + Importación)
--
--  CAMBIOS:
--    [NUEVO] aprendices.cuenta_activada
--      0 = pre-registrado (admin/importación), sin contraseña real
--      1 = cuenta activa (aprendiz completó su registro)
--
--    Los aprendices pre-registrados no pueden iniciar sesión hasta
--    completar su auto-registro vía /Views/registro.php
--
--  EJECUTAR:
--    mysql -u root attendqr < Database/Migration_v2.0.sql
--    O pegar en phpMyAdmin con base de datos attendqr seleccionada.
-- ============================================================

USE attendqr;

-- Agregar columna cuenta_activada
ALTER TABLE aprendices
  ADD COLUMN cuenta_activada TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '0 = pre-registrado (sin contraseña), 1 = auto-registro completado'
  AFTER activo;

-- Todos los aprendices existentes ya tienen contraseña válida → activar
UPDATE aprendices SET cuenta_activada = 1;

-- Índice para consultas de pre-registrados (dashboard de gestión)
CREATE INDEX idx_aprendiz_cuenta ON aprendices(cuenta_activada);
