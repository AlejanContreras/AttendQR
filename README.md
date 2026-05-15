# AttendQR

Sistema web de control de asistencia mediante QR dinámico desarrollado como MVP modular y escalable.

---

## Objetivo

AttendQR busca optimizar el registro de asistencia académica mediante códigos QR dinámicos, reduciendo fraude, tiempo operativo y procesos manuales.

---

## Características principales

- Generación de QR dinámico temporal
- Registro automático de asistencia
- Validación de sesión y token
- Prevención de registros duplicados
- Historial de asistencia
- Arquitectura modular y escalable
- Preparado para futura migración a Google Apps Script + Google Sheets

---

## Stack tecnológico inicial

### Frontend
- HTML
- CSS
- JavaScript

### Backend
- PHP

### Base de datos
- MySQL

---

## Arquitectura del proyecto

El sistema sigue una arquitectura desacoplada basada en:

- Services
- Repositories
- API Endpoints
- Separación entre lógica y persistencia

Esto permite futura migración parcial hacia:

- Google Apps Script
- Google Sheets

sin necesidad de reestructurar completamente el sistema.

---

## Estado actual

Fase actual del proyecto:

- Documentación avanzada
- Diagramación técnica
- Organización de arquitectura
- Preparación del entorno de desarrollo

---

## Estructura inicial

```plaintext
AttendQR/
├── public/
├── src/
├── api/
├── database/
├── docs/
```

---

## Objetivo MVP

Desarrollar una primera versión funcional enfocada en:

- gestión de asistencia mediante QR dinámico,
- validación básica antifraude,
- historial de asistencia,
- soporte inicial para un docente y múltiples fichas.

---

## Autor

John Contreras