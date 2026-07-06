<?php /* Mi Perfil — Vista parcial (Fase 2) */ ?>

<div class="page-header">
  <div>
    <h1 class="page-header__title">Mi Perfil</h1>
    <p class="page-header__sub">Gestiona tu información personal y credenciales</p>
  </div>
</div>

<div class="perfil-layout">

  <!-- ─── Left: Profile Card ──────────────────────────────────────── -->
  <div style="display:flex;flex-direction:column;gap:var(--sp-4)">

    <div class="profile-card">
      <div class="profile-card__banner"></div>
      <div class="profile-card__body">
        <div class="profile-card__avatar-wrap">
          <div class="profile-card__avatar" id="perfilAvatar" data-usuario-iniciales>
            <?= htmlspecialchars($userInitials ?? 'U') ?>
          </div>
        </div>
        <h2 class="profile-card__name" id="perfilNombreCard">—</h2>
        <p class="profile-card__role" id="perfilRolCard">—</p>
        <div class="profile-card__meta">
          <span class="badge badge-green">Activo</span>
        </div>
      </div>
    </div>

    <!-- Stats card -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Estadísticas</h3>
      </div>
      <div class="card-body">
        <div style="display:flex;flex-direction:column;gap:var(--sp-3)">
          <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:var(--text-sm);color:var(--text-secondary)">Sesiones totales</span>
            <strong id="statPerfilSesiones">—</strong>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:var(--text-sm);color:var(--text-secondary)" id="statPerfilLabel2">Fichas activas</span>
            <strong id="statPerfilFichas">—</strong>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:var(--text-sm);color:var(--text-secondary)" id="statPerfilLabel3">Aprendices</span>
            <strong id="statPerfilAprendices">—</strong>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:var(--text-sm);color:var(--text-secondary)">Asistencia media</span>
            <strong style="color:var(--green-primary)" id="statPerfilPct">—</strong>
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- ─── Right: Edit Forms ────────────────────────────────────────── -->
  <div style="display:flex;flex-direction:column;gap:var(--sp-5)">

    <!-- Información personal -->
    <div class="card">
      <div class="card-header">
        <div>
          <h3 class="card-title">Información personal</h3>
          <p class="card-subtitle">Actualiza tus datos de perfil</p>
        </div>
      </div>
      <div class="card-body">
        <form id="formPerfil" onsubmit="perfil.guardar(event)">

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Nombre completo</label>
              <input type="text" class="form-control" id="perfilNombre" placeholder="Cargando...">
            </div>
            <div class="form-group">
              <label class="form-label">Correo electrónico</label>
              <input type="email" class="form-control" id="perfilEmail" placeholder="Cargando...">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Documento</label>
              <input type="text" class="form-control" id="perfilDoc" readonly
                     style="background:var(--surface-alt);cursor:not-allowed">
              <small style="font-size:var(--text-xs);color:var(--text-muted)">El documento no puede modificarse</small>
            </div>
            <div class="form-group">
              <label class="form-label">Rol</label>
              <input type="text" class="form-control" id="perfilRolInput" readonly
                     style="background:var(--surface-alt);cursor:not-allowed">
              <small style="font-size:var(--text-xs);color:var(--text-muted)">El rol no puede modificarse</small>
            </div>
          </div>

          <div style="display:flex;gap:var(--sp-3);padding-top:var(--sp-4);border-top:1px solid var(--border)">
            <button type="submit" class="btn btn-primary" id="btnGuardarPerfil">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:16px;height:16px">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
              Guardar cambios
            </button>
            <button type="reset" class="btn btn-ghost">Descartar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Cambiar contraseña -->
    <div class="card">
      <div class="card-header">
        <div>
          <h3 class="card-title">Cambiar contraseña</h3>
          <p class="card-subtitle">Usa una contraseña fuerte de al menos 8 caracteres</p>
        </div>
      </div>
      <div class="card-body">
        <form id="formPassword" onsubmit="perfil.cambiarPassword(event)">

          <div class="form-group">
            <label class="form-label">Contraseña actual</label>
            <div class="input-group">
              <input type="password" class="form-control" id="passActual" placeholder="••••••••">
              <span class="input-group__append" onclick="togglePass('passActual')" style="cursor:pointer;padding:0 12px">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:16px;height:16px;color:var(--text-muted)">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
              </span>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Nueva contraseña</label>
              <div class="input-group">
              <input type="password" class="form-control" id="passNueva"
                     placeholder="••••••••" oninput="perfil.evalPassword(this.value)">
              <span class="input-group__append" onclick="togglePass('passNueva')" style="cursor:pointer;padding:0 12px">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:16px;height:16px;color:var(--text-muted)">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
              </span>
              </div>
              <div class="password-strength" id="passStrength">
                <div class="password-strength__bars">
                  <div class="strength-bar" id="pbar1"></div>
                  <div class="strength-bar" id="pbar2"></div>
                  <div class="strength-bar" id="pbar3"></div>
                  <div class="strength-bar" id="pbar4"></div>
                </div>
                <span class="password-strength__label" id="passLabel">—</span>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Confirmar contraseña</label>
              <div class="input-group">
              <input type="password" class="form-control" id="passConfirm" placeholder="••••••••">
              <span class="input-group__append" onclick="togglePass('passConfirm')" style="cursor:pointer;padding:0 12px">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:16px;height:16px;color:var(--text-muted)">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
              </span>
              </div>
            </div>
          </div>

          <div style="display:flex;gap:var(--sp-3);padding-top:var(--sp-4);border-top:1px solid var(--border)">
            <button type="submit" class="btn btn-primary">Actualizar contraseña</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Danger zone -->
    <div class="card" style="border:1px solid #FCA5A5">
      <div class="card-header" style="border-bottom:1px solid #FCA5A5">
        <div>
          <h3 class="card-title" style="color:#DC2626">Zona de peligro</h3>
          <p class="card-subtitle">Acciones irreversibles sobre tu cuenta</p>
        </div>
      </div>
      <div class="card-body">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <div>
            <div style="font-size:var(--text-sm);font-weight:var(--fw-medium);color:var(--text-primary)">
              Cerrar sesión
            </div>
            <div style="font-size:var(--text-xs);color:var(--text-muted)">
              Finaliza tu sesión actual en este dispositivo
            </div>
          </div>
          <button class="btn btn-danger btn-sm" onclick="auth.logout()">Salir</button>
        </div>
      </div>
    </div>

  </div>

</div>

<script>
function togglePass(id) {
  const el = document.getElementById(id);
  if (el) el.type = el.type === 'password' ? 'text' : 'password';
}
</script>
