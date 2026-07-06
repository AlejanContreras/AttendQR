<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar sesión — AttendQR</title>
  <link rel="stylesheet" href="../Assets/CSS/variables.css">
  <link rel="stylesheet" href="../Assets/CSS/reset.css">
  <link rel="stylesheet" href="../Assets/CSS/style.css">
  <link rel="stylesheet" href="../Assets/CSS/components.css">
  <link rel="stylesheet" href="../Assets/CSS/login.css">
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='6' fill='%2339A900'/><text y='24' x='5' font-size='22' font-family='Arial' fill='white' font-weight='bold'>A</text></svg>">
</head>
<body>

<div class="login-page">
<div class="login-panel">

  <!-- ─── Left branding panel ─────────────────────────────── -->
  <div class="login-brand">
    <div class="login-brand__logo">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
      </svg>
    </div>
    <h1 class="login-brand__title">Attend<span>QR</span></h1>
    <p class="login-brand__sub">
      Sistema Inteligente de Control de Asistencia mediante QR Dinámico para el SENA.
    </p>
    <div class="login-brand__features">
      <div class="login-brand__feature">
        <div class="login-brand__feature-icon">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        Registro de asistencia en tiempo real
      </div>
      <div class="login-brand__feature">
        <div class="login-brand__feature-icon">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
          </svg>
        </div>
        Tokens QR dinámicos con rotación automática
      </div>
      <div class="login-brand__feature">
        <div class="login-brand__feature-icon">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
          </svg>
        </div>
        Estadísticas e historial completo de asistencia
      </div>
      <div class="login-brand__feature">
        <div class="login-brand__feature-icon">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
          </svg>
        </div>
        Anti-fraude por token único por sesión
      </div>
    </div>
    <div class="login-brand__footer">SENA · Sistema de Control de Asistencia</div>
  </div>

  <!-- ─── Right form panel ─────────────────────────────────── -->
  <div class="login-form-panel">
    <div class="login-form-container">

      <!-- Mobile logo -->
      <div class="login-mobile-logo">
        <div class="login-mobile-logo__icon">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:#fff">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
          </svg>
        </div>
        <span class="login-mobile-logo__name">AttendQR</span>
      </div>

      <div class="login-form-header">
        <h2 class="login-form-title">Bienvenido</h2>
        <p class="login-form-subtitle">Ingresa tus credenciales para continuar</p>
      </div>

      <!-- Role tabs -->
      <div class="login-tabs" id="loginTabs">
        <div class="login-tab is-active" data-role="docente" onclick="switchRole(this, 'docente')">
          Docente
        </div>
        <div class="login-tab" data-role="aprendiz" onclick="switchRole(this, 'aprendiz')">
          Aprendiz
        </div>
      </div>

      <!-- Form — Docente -->
      <form class="login-form" id="formDocente" onsubmit="handleLogin(event, 'docente')">

        <div class="form-group">
          <label class="form-label" for="docenteCorreo">
            Correo electrónico <span class="required">*</span>
          </label>
          <div class="input-group">
            <span class="input-group__icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
              </svg>
            </span>
            <input type="email" id="docenteCorreo" class="form-control"
                   placeholder="correo@sena.edu.co" autocomplete="email">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="docentePassword">
            Contraseña <span class="required">*</span>
          </label>
          <div class="input-group">
            <span class="input-group__icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
              </svg>
            </span>
            <input type="password" id="docentePassword" class="form-control"
                   placeholder="••••••••" autocomplete="current-password">
            <span class="input-group__append" onclick="togglePassword('docentePassword', this)">
              <svg id="eyeIconDoc" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
            </span>
          </div>
        </div>

        <div class="login-submit">
          <button type="submit" class="btn btn-primary btn-full btn-lg">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:18px;height:18px">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
            Ingresar como Docente
          </button>
        </div>

      </form>

      <!-- Form — Aprendiz (hidden initially) -->
      <form class="login-form" id="formAprendiz" style="display:none"
            onsubmit="handleLogin(event, 'aprendiz')">

        <div class="form-group">
          <label class="form-label" for="aprendizDoc">
            Número de documento <span class="required">*</span>
          </label>
          <div class="input-group">
            <span class="input-group__icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2"/>
              </svg>
            </span>
            <input type="text" id="aprendizDoc" class="form-control"
                   placeholder="Ej. 1098765432" autocomplete="username"
                   inputmode="numeric" pattern="[0-9]{5,15}">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="aprendizPassword">
            Contraseña <span class="required">*</span>
          </label>
          <div class="input-group">
            <span class="input-group__icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
              </svg>
            </span>
            <input type="password" id="aprendizPassword" class="form-control"
                   placeholder="••••••••" autocomplete="current-password">
          </div>
        </div>

        <div class="login-submit">
          <button type="submit" class="btn btn-primary btn-full btn-lg">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:18px;height:18px">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
            Ingresar como Aprendiz
          </button>
        </div>

      </form>

      <p id="registroLink" style="display:none;text-align:center;font-size:var(--text-sm);color:var(--text-muted);margin-top:var(--sp-4)">
        ¿Primera vez?
        <a href="registro.php" style="color:var(--green-primary);text-decoration:none;font-weight:var(--fw-semibold)">Crear mi cuenta</a>
      </p>

      <p class="login-footer-text" style="text-align:center;font-size:var(--text-xs);color:var(--text-muted);margin-top:var(--sp-5)">
        ¿Problemas para ingresar? Contacta al administrador del sistema.
      </p>

    </div><!-- /login-form-container -->
  </div><!-- /login-form-panel -->

</div><!-- /login-panel -->
</div><!-- /login-page -->

<script src="../Assets/JS/api/api.js"></script>
<script src="../Assets/JS/utils/utils.js"></script>
<script src="../Assets/JS/auth/auth.js"></script>
<script src="../Assets/JS/auth/login.js"></script>

</body>
</html>
