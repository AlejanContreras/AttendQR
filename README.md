# AttendQR

Sistema web de control de asistencia mediante **códigos QR dinámicos**. El docente proyecta un QR que cambia cada 15 segundos y los aprendices lo escanean desde su celular para registrar su asistencia. Así se evita que alguien comparta una foto del código para marcar asistencia sin estar en clase.

> **Estado:** versión **2.0**, **en uso real** por un instructor con sus fichas desde 2026. La v2 agrega soporte para varios docentes y recuperación de cuenta por correo.

---

## Problema que resuelve

Llamar lista a mano toma tiempo de clase y es fácil de falsear. AttendQR automatiza el registro, clasifica cada llegada como *presente* o *retardo* según la hora de inicio, marca las fallas al cerrar la sesión y deja un historial exportable a Excel.

---

## Funcionalidades

**Docente**
- **Varios docentes en la misma instalación:** cada docente ve y gestiona solo sus fichas, aprendices, sesiones y asistencias
- Gestión de fichas, jornadas y trimestres
- Importación masiva de aprendices desde **CSV** (detecta el separador `;` o `,` y el BOM)
- Creación de sesiones de clase con hora de inicio, tiempo límite de retardo y **duración configurable de 20 a 60 minutos** (con valor por defecto por jornada, p. ej. 60 min en la noche)
- **QR dinámico** que rota automáticamente cada **15 segundos**
- Validación opcional por **geolocalización** (radio de 30 m)
- Cierre de sesión con registro automático de fallas para quien no marcó
- Corrección manual de estados desde el historial
- **Pase de trimestre:** continúa con la misma ficha en el nuevo trimestre retirando a quienes desertaron, sin perder el historial anterior
- Dashboard con estadísticas de asistencia
- **Exportación a Excel (.xlsx) por trimestre**, una hoja por ficha (`3234699_T4_2026`), generada con una clase propia (`XlsxWriter`) sin librerías externas
- **Recuperación de contraseña por correo:** contraseña temporal de un solo uso, válida 30 minutos

**Aprendiz**
- Activación de cuenta con correo electrónico obligatorio
- **Recuperación de contraseña por correo**; si el aprendiz no tiene correo registrado, la solicitud le llega a su instructor
- Escaneo del QR desde el celular
- Consulta de su propio historial de asistencia

---

## Cómo funciona el QR dinámico

1. El docente abre una sesión; el sistema genera un token aleatorio (`random_bytes`) con fecha de expiración y lo guarda en `tokens_qr`.
2. El frontend consulta el token activo; si expiró y la sesión sigue abierta, el backend **rota** el token y entrega uno nuevo (cada 15 s).
3. Al escanear, el backend valida que el token exista, siga activo, no haya expirado y pertenezca a una sesión abierta.
4. Valida que el aprendiz pertenezca a la ficha, que no tenga un registro previo en esa sesión y, si la sesión lo exige, la distancia al punto de clase.
5. Clasifica la asistencia: **PRESENTE** si llega dentro del margen de retardo, **RETARDO** si lo supera (y calcula los minutos de retardo).

La hora y el estado siempre se calculan en el servidor; nunca se confía en datos de tiempo enviados por el cliente.

---

## Arquitectura

Backend en **PHP sin framework**, organizado por capas con responsabilidades separadas:

```
Petición HTTP → Public/api.php (router central)
             → Middleware (AuthMiddleware: sesión · RoleMiddleware: docente / aprendiz)
             → Controller   (recibe la petición y valida la entrada)
             → Service      (reglas de negocio: QR, asistencia, clasificación, acceso)
             → Repository   (consultas SQL con PDO y sentencias preparadas)
             → MySQL
```

- **Router central** en `Public/api.php`: cada módulo se registra en una tabla de rutas (`/api/asistencias`, `/api/sesiones`, `/api/qr`, `/api/aprendices`, etc.) y todas las respuestas son JSON.
- **Aislamiento entre docentes:** el usuario se toma siempre de la sesión del servidor, nunca de parámetros del navegador. `AccesoService` verifica en cada petición que la ficha, el aprendiz, la sesión o la asistencia pertenezcan al docente (401 sin sesión, 403 si es de otro docente).
- **Seguridad:** contraseñas con `password_hash` / `password_verify`, consultas con sentencias preparadas (PDO), control de acceso por rol, contraseñas temporales guardadas como hash con vencimiento y límite de solicitudes, y credenciales de BD y de correo fuera del repositorio.
- **Correo:** envío por SMTP con PHPMailer (incluido en `Src/Libs`, sin Composer). La configuración vive en `Src/Config/correo.php`, que no se sube al repositorio.
- **Base de datos:** 9 tablas (docentes, jornadas, trimestres, fichas, aprendices, sesiones, tokens QR, asistencias y solicitudes de recuperación) más vistas SQL para reportes.

![Diagrama de arquitectura](Docs/Diagrams/Diagrama%20Arquitectura%20General.png)

---

## Tecnologías

| Capa | Tecnología |
|---|---|
| Frontend | HTML5, CSS3 (nativo, con variables), JavaScript vanilla |
| Librerías QR | `qrcodejs` (generación) y `html5-qrcode` (escaneo con la cámara) |
| Backend | PHP 8 sin framework, PDO |
| Correo | PHPMailer (SMTP) |
| Base de datos | MySQL / MariaDB |
| Servidor | Apache (`.htaccess` con URLs amigables) |

---

## Estructura del proyecto

```
AttendQR/
├── Public/              # Punto de entrada web
│   ├── api.php          # Router central de la API
│   ├── index.php        # Enrutador de vistas
│   ├── Views/           # Páginas (login, dashboards, QR, historial…)
│   ├── Components/      # Header, sidebar, modal, etc.
│   └── Assets/          # CSS y JS por módulo
├── Src/
│   ├── Config/          # Conexión a la base de datos y configuración de correo
│   ├── Controllers/     # Un controlador por recurso
│   ├── Middleware/      # Autenticación y roles
│   ├── Services/        # Lógica de negocio (incluye AccesoService y CorreoService)
│   ├── Repositories/    # Acceso a datos
│   ├── Models/          # Representación de entidades
│   ├── Libs/            # PHPMailer
│   └── Utils/           # XlsxWriter (exportación a Excel)
├── Database/            # Instalación limpia, esquema, migraciones, seeds y vistas
├── Docs/                # Documentación técnica, diagramas y lógica de negocio
└── Tests/               # Archivos CSV y datos de prueba
```

---

## Instalación local

**Requisitos:** PHP 8+, MySQL o MariaDB y Apache con `mod_rewrite` (por ejemplo, XAMPP).

1. Clonar el repositorio:
   ```bash
   git clone https://github.com/AlejanContreras/AttendQR.git
   ```
2. Crear la base de datos con el script de instalación limpia:
   ```bash
   mysql -u root -p < Database/InstallFresh.sql
   ```
3. Copiar `.env.example` como `.env` y completar los datos de conexión.
4. Copiar `Src/Config/correo.example.php` como `Src/Config/correo.php` y configurar el SMTP (o dejar el modo `archivo` en localhost para que los correos se guarden en `Storage/logs`).
5. Apuntar el servidor a la carpeta `Public/` y abrir el proyecto en el navegador.

**Actualizar una instalación v1.0:** ejecutar en orden los scripts `Database/Migracion_Trimestres_Duracion.sql`, `Database/Migracion_Recuperacion_Docente.sql` y `Database/Migracion_Correo_Aprendiz.sql`.

---

## Documentación

En la carpeta [`Docs/`](Docs/) están la documentación técnica del MVP (PDF), los diagramas (arquitectura, casos de uso, flujo y modelo entidad-relación) y los documentos de lógica de negocio: generación del QR dinámico, lógica temporal y registro de asistencia.

---

## Historial de versiones

**v2.0 — octubre 2026**
- Varios docentes en la misma instalación, con aislamiento de datos entre ellos
- Recuperación de contraseña por correo para docentes y aprendices (con respaldo por el instructor)
- Correo obligatorio al activar la cuenta del aprendiz
- Duración de sesión configurable de 20 a 60 minutos, con valor por jornada
- Pase de trimestre y exportación a Excel separada por trimestre
- Rotación del QR cada 15 segundos (antes 30)

**v1.0 — 2026**
- MVP para un solo instructor: fichas, sesiones, QR dinámico, registro de asistencia con validación por geolocalización, historial y exportación a Excel

### Intento de migración a Google Apps Script

En julio de 2026 se intentó migrar el sistema a Google Apps Script + Google Sheets para alojarlo sin servidor propio. La migración se descartó por limitaciones de la plataforma (por ejemplo, el acceso a la cámara para escanear el QR dentro de Apps Script) y el proyecto continuó en PHP + MySQL. El código de ese intento se conserva en el historial del repositorio: [ver carpeta `Migration_GAS`](https://github.com/AlejanContreras/AttendQR/tree/cb44483/Migration_GAS).

---

## Próximos pasos

- Pruebas automatizadas del backend (PHPUnit)
- Rol de administrador para gestionar docentes y catálogos desde la aplicación

---

## Autor

**John Alejandro Contreras** · [GitHub](https://github.com/AlejanContreras) · alejan241107@gmail.com
