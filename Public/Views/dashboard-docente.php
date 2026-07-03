<?php /* Dashboard Docente — Vista parcial */ ?>

<!-- ─── Welcome Banner ──────────────────────────────────────────────── -->
<div class="welcome-banner">
  <div class="welcome-banner__content">
    <div class="welcome-banner__greeting">Buenos días</div>
    <h2 class="welcome-banner__name">Carlos <span>Rodríguez</span></h2>
    <div class="welcome-banner__meta">
      <span class="welcome-banner__meta-item">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        2 sesiones activas
      </span>
      <span class="welcome-banner__meta-item">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
        </svg>
        47 aprendices
      </span>
      <a href="index.php?view=crear-sesion&rol=docente" class="btn btn-primary btn-sm" style="margin-left:var(--sp-2)">
        + Nueva sesión
      </a>
    </div>
  </div>
  <div class="welcome-banner__visual">
    <div class="welcome-banner__avatar">CR</div>
  </div>
</div>

<!-- ─── Stat Cards ───────────────────────────────────────────────────── -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:var(--sp-4);margin-bottom:var(--sp-6)">

  <div class="stat-card stat-card--green">
    <div class="stat-card__icon">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
      </svg>
    </div>
    <div class="stat-card__body">
      <div class="stat-card__value">2</div>
      <div class="stat-card__label">Sesiones activas</div>
    </div>
    <div class="stat-card__trend stat-card__trend--up">Hoy</div>
  </div>

  <div class="stat-card stat-card--blue">
    <div class="stat-card__icon">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
      </svg>
    </div>
    <div class="stat-card__body">
      <div class="stat-card__value">47</div>
      <div class="stat-card__label">Aprendices</div>
    </div>
    <div class="stat-card__trend stat-card__trend--neutral">3 fichas</div>
  </div>

  <div class="stat-card stat-card--purple">
    <div class="stat-card__icon">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
      </svg>
    </div>
    <div class="stat-card__body">
      <div class="stat-card__value">89%</div>
      <div class="stat-card__label">Asistencia hoy</div>
    </div>
    <div class="stat-card__trend stat-card__trend--up">+3% vs ayer</div>
  </div>

  <div class="stat-card stat-card--orange">
    <div class="stat-card__icon">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
      </svg>
    </div>
    <div class="stat-card__body">
      <div class="stat-card__value">3</div>
      <div class="stat-card__label">Fichas activas</div>
    </div>
    <div class="stat-card__trend stat-card__trend--neutral">Trimestre I</div>
  </div>

</div>

<!-- ─── Grid ─────────────────────────────────────────────────────────── -->
<div class="dashboard-grid">

  <!-- Acciones rápidas -->
  <div class="card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Acciones rápidas</h3>
        <p class="card-subtitle">Operaciones frecuentes de clase</p>
      </div>
    </div>
    <div class="card-body">
      <div class="quick-actions">
        <a href="index.php?view=crear-sesion&rol=docente" class="quick-action-card">
          <div class="quick-action-card__icon" style="background:var(--green-light)">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:var(--green-primary)">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
          </div>
          <span class="quick-action-card__label">Nueva sesión</span>
        </a>
        <a href="index.php?view=qr&rol=docente" class="quick-action-card">
          <div class="quick-action-card__icon" style="background:#EEF2FF">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:#6366F1">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
            </svg>
          </div>
          <span class="quick-action-card__label">QR Dinámico</span>
        </a>
        <a href="index.php?view=historial&rol=docente" class="quick-action-card">
          <div class="quick-action-card__icon" style="background:#FFF7ED">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:#F97316">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
          </div>
          <span class="quick-action-card__label">Historial</span>
        </a>
        <a href="index.php?view=perfil&rol=docente" class="quick-action-card">
          <div class="quick-action-card__icon" style="background:#F0FDF4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:#22C55E">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
          </div>
          <span class="quick-action-card__label">Mi perfil</span>
        </a>
      </div>
    </div>
  </div>

  <!-- Sesiones recientes -->
  <div class="card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Sesiones recientes</h3>
        <p class="card-subtitle">Últimas 5 sesiones</p>
      </div>
      <a href="index.php?view=historial&rol=docente" class="btn btn-ghost btn-sm">Ver todas</a>
    </div>
    <div class="card-body" style="padding:0">
      <table class="table">
        <thead>
          <tr>
            <th>Ficha</th>
            <th>Fecha</th>
            <th>Estado</th>
            <th>Asistencia</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <div style="font-weight:var(--fw-medium)">2345678</div>
              <div style="font-size:var(--text-xs);color:var(--text-muted)">Análisis y Desarrollo</div>
            </td>
            <td>Hoy, 08:00</td>
            <td><span class="badge badge-success">Abierta</span></td>
            <td>
              <div style="font-weight:var(--fw-semibold)">18/20</div>
              <div class="progress" style="margin-top:4px;height:4px">
                <div class="progress-bar" style="width:90%;background:var(--green-primary)"></div>
              </div>
            </td>
          </tr>
          <tr>
            <td>
              <div style="font-weight:var(--fw-medium)">2345679</div>
              <div style="font-size:var(--text-xs);color:var(--text-muted)">Diseño Web</div>
            </td>
            <td>Hoy, 10:00</td>
            <td><span class="badge badge-success">Abierta</span></td>
            <td>
              <div style="font-weight:var(--fw-semibold)">14/16</div>
              <div class="progress" style="margin-top:4px;height:4px">
                <div class="progress-bar" style="width:87%;background:var(--green-primary)"></div>
              </div>
            </td>
          </tr>
          <tr>
            <td>
              <div style="font-weight:var(--fw-medium)">2345680</div>
              <div style="font-size:var(--text-xs);color:var(--text-muted)">Bases de Datos</div>
            </td>
            <td>Ayer, 14:00</td>
            <td><span class="badge badge-neutral">Cerrada</span></td>
            <td>
              <div style="font-weight:var(--fw-semibold)">9/11</div>
              <div class="progress" style="margin-top:4px;height:4px">
                <div class="progress-bar" style="width:81%;background:var(--green-primary)"></div>
              </div>
            </td>
          </tr>
          <tr>
            <td>
              <div style="font-weight:var(--fw-medium)">2345678</div>
              <div style="font-size:var(--text-xs);color:var(--text-muted)">Análisis y Desarrollo</div>
            </td>
            <td>Ayer, 08:00</td>
            <td><span class="badge badge-neutral">Cerrada</span></td>
            <td>
              <div style="font-weight:var(--fw-semibold)">19/20</div>
              <div class="progress" style="margin-top:4px;height:4px">
                <div class="progress-bar" style="width:95%;background:var(--green-primary)"></div>
              </div>
            </td>
          </tr>
          <tr>
            <td>
              <div style="font-weight:var(--fw-medium)">2345679</div>
              <div style="font-size:var(--text-xs);color:var(--text-muted)">Diseño Web</div>
            </td>
            <td>01/07, 10:00</td>
            <td><span class="badge badge-danger">Cancelada</span></td>
            <td><span style="color:var(--text-muted)">—</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

</div>
