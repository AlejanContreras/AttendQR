<?php /* Dashboard Aprendiz — Vista parcial */ ?>

<!-- ─── Welcome Banner ──────────────────────────────────────────────── -->
<div class="welcome-banner">
  <div class="welcome-banner__content">
    <div class="welcome-banner__greeting">Bienvenida</div>
    <h2 class="welcome-banner__name">María <span>García</span></h2>
    <div class="welcome-banner__meta">
      <span class="welcome-banner__meta-item">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        85.7% de asistencia
      </span>
      <span class="welcome-banner__meta-item">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
        </svg>
        Ficha 2345678
      </span>
    </div>
  </div>
  <div class="welcome-banner__visual">
    <div class="welcome-banner__avatar">MG</div>
  </div>
</div>

<!-- ─── Stat Cards ───────────────────────────────────────────────────── -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:var(--sp-4);margin-bottom:var(--sp-6)">

  <div class="stat-card stat-card--green">
    <div class="stat-card__icon">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
    </div>
    <div class="stat-card__body">
      <div class="stat-card__value">85.7%</div>
      <div class="stat-card__label">Asistencia total</div>
    </div>
    <div class="stat-card__trend stat-card__trend--up">Bueno</div>
  </div>

  <div class="stat-card stat-card--blue">
    <div class="stat-card__icon">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
      </svg>
    </div>
    <div class="stat-card__body">
      <div class="stat-card__value">24/28</div>
      <div class="stat-card__label">Sesiones asistidas</div>
    </div>
    <div class="stat-card__trend stat-card__trend--neutral">Este trimestre</div>
  </div>

  <div class="stat-card stat-card--orange">
    <div class="stat-card__icon">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
    </div>
    <div class="stat-card__body">
      <div class="stat-card__value">2</div>
      <div class="stat-card__label">Tardanzas</div>
    </div>
    <div class="stat-card__trend stat-card__trend--neutral">Este mes</div>
  </div>

  <div class="stat-card" style="--accent:#EF4444;background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.15)">
    <div class="stat-card__icon" style="background:rgba(239,68,68,.1)">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:#EF4444">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </div>
    <div class="stat-card__body">
      <div class="stat-card__value" style="color:#EF4444">4</div>
      <div class="stat-card__label">Ausencias</div>
    </div>
    <div class="stat-card__trend stat-card__trend--down">Reducir</div>
  </div>

</div>

<!-- ─── Grid: Donut + Historial ──────────────────────────────────────── -->
<div class="dashboard-grid">

  <!-- Attendance Circle -->
  <div class="card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Mi asistencia</h3>
        <p class="card-subtitle">Distribución del trimestre actual</p>
      </div>
    </div>
    <div class="card-body" style="display:flex;flex-direction:column;align-items:center;gap:var(--sp-6)">

      <div class="attendance-circle">
        <svg viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
          <circle cx="60" cy="60" r="52" fill="none" stroke="var(--border)" stroke-width="10"/>
          <circle cx="60" cy="60" r="52" fill="none"
                  stroke="var(--green-primary)" stroke-width="10"
                  stroke-dasharray="278 326" stroke-dashoffset="81.5"
                  stroke-linecap="round" transform="rotate(-90 60 60)"/>
          <circle cx="60" cy="60" r="52" fill="none"
                  stroke="#F59E0B" stroke-width="10"
                  stroke-dasharray="23 326" stroke-dashoffset="-197"
                  stroke-linecap="round" transform="rotate(-90 60 60)"/>
          <circle cx="60" cy="60" r="52" fill="none"
                  stroke="#EF4444" stroke-width="10"
                  stroke-dasharray="23 326" stroke-dashoffset="-220"
                  stroke-linecap="round" transform="rotate(-90 60 60)"/>
          <text x="60" y="55" text-anchor="middle" font-size="20" font-weight="700" fill="var(--text-primary)">85.7%</text>
          <text x="60" y="72" text-anchor="middle" font-size="9" fill="var(--text-muted)">Asistencia</text>
        </svg>
      </div>

      <div style="display:flex;flex-direction:column;gap:var(--sp-2);width:100%;max-width:220px">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <div style="display:flex;align-items:center;gap:var(--sp-2)">
            <span style="width:10px;height:10px;border-radius:50%;background:var(--green-primary);display:inline-block"></span>
            <span style="font-size:var(--text-sm);color:var(--text-secondary)">Presente</span>
          </div>
          <strong style="font-size:var(--text-sm)">24 sesiones</strong>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center">
          <div style="display:flex;align-items:center;gap:var(--sp-2)">
            <span style="width:10px;height:10px;border-radius:50%;background:#F59E0B;display:inline-block"></span>
            <span style="font-size:var(--text-sm);color:var(--text-secondary)">Tardanza</span>
          </div>
          <strong style="font-size:var(--text-sm)">2 sesiones</strong>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center">
          <div style="display:flex;align-items:center;gap:var(--sp-2)">
            <span style="width:10px;height:10px;border-radius:50%;background:#EF4444;display:inline-block"></span>
            <span style="font-size:var(--text-sm);color:var(--text-secondary)">Ausente</span>
          </div>
          <strong style="font-size:var(--text-sm)">4 sesiones</strong>
        </div>
      </div>

    </div>
  </div>

  <!-- Historial reciente -->
  <div class="card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Historial reciente</h3>
        <p class="card-subtitle">Últimas sesiones de mi ficha</p>
      </div>
      <a href="index.php?view=historial&rol=aprendiz" class="btn btn-ghost btn-sm">Ver todo</a>
    </div>
    <div class="card-body" style="padding:0">
      <table class="table">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Programa</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <div>Hoy, 08:00</div>
              <div style="font-size:var(--text-xs);color:var(--text-muted)">03/07/2025</div>
            </td>
            <td>Análisis y Desarrollo de Software</td>
            <td><span class="badge badge-success">Presente</span></td>
          </tr>
          <tr>
            <td>
              <div>Ayer, 10:00</div>
              <div style="font-size:var(--text-xs);color:var(--text-muted)">02/07/2025</div>
            </td>
            <td>Análisis y Desarrollo de Software</td>
            <td><span class="badge badge-warning">Tardanza</span></td>
          </tr>
          <tr>
            <td>
              <div>01/07/2025</div>
              <div style="font-size:var(--text-xs);color:var(--text-muted)">08:00 AM</div>
            </td>
            <td>Análisis y Desarrollo de Software</td>
            <td><span class="badge badge-success">Presente</span></td>
          </tr>
          <tr>
            <td>
              <div>30/06/2025</div>
              <div style="font-size:var(--text-xs);color:var(--text-muted)">08:00 AM</div>
            </td>
            <td>Análisis y Desarrollo de Software</td>
            <td><span class="badge badge-danger">Ausente</span></td>
          </tr>
          <tr>
            <td>
              <div>27/06/2025</div>
              <div style="font-size:var(--text-xs);color:var(--text-muted)">08:00 AM</div>
            </td>
            <td>Análisis y Desarrollo de Software</td>
            <td><span class="badge badge-success">Presente</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

</div>
