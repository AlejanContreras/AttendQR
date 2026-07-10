# MAPEO.md — AttendQR PHP → Google Apps Script

Documento de referencia oficial para toda la migración.
Actualizar el campo **Estado** a medida que se complete cada archivo.

Estados válidos: `Pendiente` · `En progreso` · `Completado` · `Omitido`

---

## Backend — Controllers

| PHP (Actual)                              | GAS (Destino)                      | Estado    |
| ----------------------------------------- | ---------------------------------- | --------- |
| Src/Controllers/AuthController.php        | AUTH_AuthController.gs             | Completado |
| Src/Controllers/AprendizController.php    | APRENDIZ_AprendizController.gs     | Completado |
| Src/Controllers/AsistenciaController.php  | ASISTENCIA_AsistenciaController.gs | Completado |
| Src/Controllers/DocenteController.php     | DOCENTE_DocenteController.gs       | Completado |
| Src/Controllers/EstadisticaController.php | ESTADISTICA_EstadisticaController.gs | Completado |
| Src/Controllers/FichaController.php       | FICHA_FichaController.gs           | Completado |
| Src/Controllers/HealthController.php      | HEALTH_HealthController.gs         | Completado |
| Src/Controllers/JornadaController.php     | JORNADA_JornadaController.gs       | Completado |
| Src/Controllers/QrController.php          | QR_QrController.gs                 | Completado |
| Src/Controllers/SesionController.php      | SESION_SesionController.gs         | Completado |
| Src/Controllers/TokenController.php       | TOKEN_TokenController.gs           | Completado |
| Src/Controllers/TrimestreController.php   | TRIMESTRE_TrimestreController.gs   | Completado |

---

## Backend — Services

| PHP (Actual)                            | GAS (Destino)                    | Estado    |
| --------------------------------------- | -------------------------------- | --------- |
| Src/Services/AuthService.php            | AUTH_AuthService.gs              | Completado |
| Src/Services/AprendizService.php        | APRENDIZ_AprendizService.gs      | Completado |
| Src/Services/AsistenciaService.php      | ASISTENCIA_AsistenciaService.gs  | Completado |
| Src/Services/DocenteService.php         | DOCENTE_DocenteService.gs        | Completado |
| Src/Services/EstadisticaService.php     | ESTADISTICA_EstadisticaService.gs | Completado |
| Src/Services/FichaService.php           | FICHA_FichaService.gs            | Completado |
| Src/Services/HealthService.php          | HEALTH_HealthService.gs          | Completado |
| Src/Services/JornadaService.php         | JORNADA_JornadaService.gs        | Completado |
| Src/Services/QrService.php              | QR_QrService.gs                  | Completado |
| Src/Services/SesionService.gs          | SESION_SesionService.gs          | Completado |
| Src/Services/TokenService.php           | TOKEN_TokenService.gs            | Completado |
| Src/Services/TrimestreService.php       | TRIMESTRE_TrimestreService.gs    | Completado |

---

## Backend — Repositories

| PHP (Actual)                                | GAS (Destino)                      | Estado    |
| ------------------------------------------- | ---------------------------------- | --------- |
| Src/Repositories/AuthRepository.php         | AUTH_AuthRepository.gs             | Completado |
| Src/Repositories/AprendizRepository.php     | APRENDIZ_AprendizRepository.gs     | Completado |
| Src/Repositories/AsistenciaRepository.php   | ASISTENCIA_AsistenciaRepository.gs | Completado |
| Src/Repositories/DocenteRepository.php      | DOCENTE_DocenteRepository.gs       | Completado |
| Src/Repositories/FichaRepository.php        | FICHA_FichaRepository.gs           | Completado |
| Src/Repositories/JornadaRepository.php      | JORNADA_JornadaRepository.gs       | Completado |
| Src/Repositories/QrRepository.php           | QR_QrRepository.gs                 | Completado |
| Src/Repositories/SesionRepository.php       | SESION_SesionRepository.gs         | Completado |
| Src/Repositories/TokenRepository.php        | TOKEN_TokenRepository.gs           | Completado |
| Src/Repositories/TrimestreRepository.php    | TRIMESTRE_TrimestreRepository.gs   | Completado |
| Src/Repositories/BaseRepository.php         | UTIL_Utils.gs (base común)         | Omitido   |

---

## Backend — Config / Middleware / Utils

| PHP (Actual)                        | GAS (Destino)          | Estado    |
| ----------------------------------- | ---------------------- | --------- |
| Src/Config/database.php             | CONFIG_Constants.gs    | Completado |
| Src/Middleware/AuthMiddleware.php    | CONFIG_Middleware.gs   | Completado |
| Src/Middleware/RoleMiddleware.php    | CONFIG_Middleware.gs   | Completado |
| Src/Utils/XlsxWriter.php            | UTIL_XlsxExport.gs     | Completado |
| Public/api.php (router)             | 00_doGet.gs            | Completado |
| Public/index.php (entry point)      | 00_doGet.gs            | Completado |
| —                                   | 01_Include.gs          | Completado |

---

## Vistas (Views)

| PHP (Actual)                              | GAS (Destino)              | Estado    |
| ----------------------------------------- | -------------------------- | --------- |
| Public/Views/login.php                    | login.html                 | Completado |
| Public/Views/registro.php                 | registro.html              | Completado |
| Public/Views/dashboard-docente.php        | dashboard-docente.html     | Completado |
| Public/Views/dashboard-aprendiz.php       | dashboard-aprendiz.html    | Completado |
| Public/Views/perfil.php                   | perfil.html                | Completado |
| Public/Views/historial.php                | historial.html             | Completado |
| Public/Views/aprendices.php               | aprendices.html            | Completado |
| Public/Views/crear-sesion.php             | crear-sesion.html          | Completado |
| Public/Views/qr.php                       | qr.html                    | Completado |
| Public/Views/registrar-asistencia.php     | registrar-asistencia.html  | Completado |
| Public/Views/404.php                      | 404.html                   | Completado |

---

## Componentes (Components)

| PHP (Actual)                   | GAS (Destino)      | Estado    |
| ------------------------------ | ------------------ | --------- |
| Public/Components/footer.php   | COMP_footer.html   | Completado |
| Public/Components/header.php   | COMP_header.html   | Completado |
| Public/Components/loader.php   | COMP_loader.html   | Completado |
| Public/Components/modal.php    | COMP_modal.html    | Completado |
| Public/Components/navbar.php   | COMP_navbar.html   | Completado |
| Public/Components/sidebar.php  | COMP_sidebar.html  | Completado |

---

## CSS

| PHP (Actual)                         | GAS (Destino)              | Estado    |
| ------------------------------------ | -------------------------- | --------- |
| Public/Assets/CSS/layout.css         | CSS_LAYOUT_layout.html     | Completado |
| Public/Assets/CSS/reset.css          | CSS_LAYOUT_reset.html      | Completado |
| Public/Assets/CSS/variables.css      | CSS_LAYOUT_variables.html  | Completado |
| Public/Assets/CSS/style.css          | CSS_LAYOUT_style.html      | Completado |
| Public/Assets/CSS/components.css     | CSS_LAYOUT_components.html | Completado |
| Public/Assets/CSS/login.css          | CSS_LOGIN_login.html       | Completado |
| Public/Assets/CSS/dashboard.css      | CSS_DASHBOARD_dashboard.html | Completado |
| Public/Assets/CSS/historial.css      | CSS_HISTORIAL_historial.html | Completado |
| Public/Assets/CSS/qr.css             | CSS_QR_qr.html             | Completado |
| Public/Assets/CSS/perfil.css         | CSS_PERFIL_perfil.html     | Completado |
| —                                    | CSS_LOGIN_registro.html    | Completado |

---

## JavaScript

| PHP (Actual)                              | GAS (Destino)              | Estado    |
| ----------------------------------------- | -------------------------- | --------- |
| Public/Assets/JS/api/api.js               | JS_API_api.html            | Completado |
| Public/Assets/JS/utils/utils.js           | JS_UTIL_utils.html         | Completado |
| Public/Assets/JS/auth/auth.js             | JS_AUTH_auth.html          | Completado |
| Public/Assets/JS/auth/login.js            | JS_AUTH_login.html         | Completado |
| Public/Assets/JS/auth/registro.js         | JS_AUTH_registro.html      | Completado |
| Public/Assets/JS/dashboard/dashboard.js   | JS_DASHBOARD_dashboard.html | Completado |
| Public/Assets/JS/historial/historial.js   | JS_HISTORIAL_historial.html | Completado |
| Public/Assets/JS/qr/qr.js                 | JS_QR_qr.html              | Completado |
| Public/Assets/JS/perfil/perfil.js         | JS_PERFIL_perfil.html      | Completado |
| Public/Assets/JS/aprendices/aprendices.js | JS_APRENDIZ_aprendices.html | Completado |
| Public/Assets/JS/asistencia/asistencia.js | JS_ASISTENCIA_asistencia.html | Completado |
| Public/Assets/JS/sesiones/sesiones.js     | JS_SESION_sesiones.html    | Completado |

---

## Convención de nomenclatura definitiva

```
Módulo GAS    Prefijo
────────────  ───────
Auth          AUTH_
Aprendiz      APRENDIZ_
Asistencia    ASISTENCIA_
Docente       DOCENTE_
Estadística   ESTADISTICA_
Ficha         FICHA_
Health        HEALTH_
Jornada       JORNADA_
QR            QR_
Sesión        SESION_
Token         TOKEN_
Trimestre     TRIMESTRE_
Utilidades    UTIL_
Config        CONFIG_
Componentes   COMP_
CSS           CSS_MODULO_
JavaScript    JS_MODULO_
```

**Prohibido usar:** `final`, `nuevo`, `prueba`, `v2`, `copy`, `temp`

