// =============================================================
// AttendQR — UTIL_Utils
// =============================================================
// Equivalente conceptual de Src/Repositories/BaseRepository.php
//
// ── Por qué este archivo está vacío ─────────────────────────
// En PHP, BaseRepository es una clase abstracta que centraliza
// los helpers de acceso PDO (consultar, consultarUno, insertar,
// ejecutar, contar, existe). Todos los Repositories concretos
// heredan de ella.
//
// En GAS con patrón IIFE no existe herencia de clases. Cada
// Repository es un módulo autocontenido con sus propios helpers
// privados (_getSheet, _nextId, _rowToX, etc.).
// La funcionalidad de BaseRepository está distribuida en cada
// módulo Repository y no necesita un punto central.
//
// Estado en MAPEO.md: Omitido (por diseño — no aplica en GAS).
// =============================================================
