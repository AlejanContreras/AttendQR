<?php /* Dashboard Aprendiz — Vista parcial (Fase 2) */ ?>

<!-- ─── Welcome Banner ──────────────────────────────────────────────── -->
<div class="welcome-banner">
  <div class="welcome-banner__content">
    <div class="welcome-banner__greeting">Bienvenido/a</div>
    <h2 class="welcome-banner__name"><span data-usuario-nombre><?= htmlspecialchars($userName ?? '') ?></span></h2>
    <div class="welcome-banner__meta">
      <span class="welcome-banner__meta-item">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        <span id="statAsistenciaPct">—</span> de asistencia
      </span>
      <a href="index.php?view=historial&rol=aprendiz" class="btn btn-primary btn-sm" style="margin-left:var(--sp-2)">
        Ver historial
      </a>
    </div>
  </div>
  <div class="welcome-banner__visual">
    <div class="welcome-banner__avatar" data-usuario-iniciales><?= htmlspecialchars($userInitials ?? 'U') ?></div>
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
      <div class="stat-card__value" id="statAsistenciaPct2">—</div>
      <div class="stat-card__label">Asistencia total</div>
    </div>
    <div class="stat-card__trend" id="statAsistenciaTrend">—</div>
  </div>

  <div class="stat-card stat-card--blue">
    <div class="stat-card__icon">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
      </svg>
    </div>
    <div class="stat-card__body">
      <div class="stat-card__value" id="statSesionesTotal">—</div>
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
      <div class="stat-card__value" id="statTardanzas">—</div>
      <div class="stat-card__label">Tardanzas</div>
    </div>
    <div class="stat-card__trend stat-card__trend--neutral">Total</div>
  </div>

  <div class="stat-card" style="background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.15)">
    <div class="stat-card__icon" style="background:rgba(239,68,68,.1)">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:#EF4444">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </div>
    <div class="stat-card__body">
      <div class="stat-card__value" id="statAusencias" style="color:#EF4444">—</div>
      <div class="stat-card__label">Ausencias</div>
    </div>
    <div class="stat-card__trend stat-card__trend--neutral">Total</div>
  </div>

</div>

<!-- ─── Grid ─────────────────────────────────────────────────────────── -->
<div class="dashboard-grid">

  <!-- Donut de asistencia -->
  <div class="card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Mi asistencia</h3>
        <p class="card-subtitle">Distribución del trimestre</p>
      </div>
    </div>
    <div class="card-body" style="display:flex;flex-direction:column;align-items:center;gap:var(--sp-6)">
      <div class="attendance-circle">
        <svg viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg" id="donutSvg">
          <circle cx="60" cy="60" r="52" fill="none" stroke="var(--border)" stroke-width="10"/>
          <circle id="donutPresente" cx="60" cy="60" r="52" fill="none"
                  stroke="var(--green-primary)" stroke-width="10"
                  stroke-dasharray="0 326" stroke-linecap="round" transform="rotate(-90 60 60)"/>
          <circle id="donutRetardo" cx="60" cy="60" r="52" fill="none"
                  stroke="#F59E0B" stroke-width="10"
                  stroke-dasharray="0 326" stroke-linecap="round" transform="rotate(-90 60 60)"/>
          <text id="attendanceCircleText" x="60" y="55" text-anchor="middle" font-size="20"
                font-weight="700" fill="var(--text-primary)">—</text>
          <text x="60" y="72" text-anchor="middle" font-size="9" fill="var(--text-muted)">Asistencia</text>
        </svg>
      </div>
      <div style="display:flex;flex-direction:column;gap:var(--sp-2);width:100%;max-width:220px" id="donutLeyenda">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <div style="display:flex;align-items:center;gap:var(--sp-2)">
            <span style="width:10px;height:10px;border-radius:50%;background:var(--green-primary);display:inline-block"></span>
            <span style="font-size:var(--text-sm);color:var(--text-secondary)">Presente</span>
          </div>
          <strong id="leyendaPresente" style="font-size:var(--text-sm)">—</strong>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center">
          <div style="display:flex;align-items:center;gap:var(--sp-2)">
            <span style="width:10px;height:10px;border-radius:50%;background:#F59E0B;display:inline-block"></span>
            <span style="font-size:var(--text-sm);color:var(--text-secondary)">Tardanza</span>
          </div>
          <strong id="leyendaRetardo" style="font-size:var(--text-sm)">—</strong>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center">
          <div style="display:flex;align-items:center;gap:var(--sp-2)">
            <span style="width:10px;height:10px;border-radius:50%;background:#EF4444;display:inline-block"></span>
            <span style="font-size:var(--text-sm);color:var(--text-secondary)">Ausente</span>
          </div>
          <strong id="leyendaAusente" style="font-size:var(--text-sm)">—</strong>
        </div>
      </div>
    </div>
  </div>

  <!-- Historial reciente -->
  <div class="card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Historial reciente</h3>
        <p class="card-subtitle">Últimas 5 sesiones</p>
      </div>
      <a href="index.php?view=historial&rol=aprendiz" class="btn btn-ghost btn-sm">Ver todo</a>
    </div>
    <div class="card-body" style="padding:0">
      <table class="table">
        <thead>
          <tr><th>Fecha</th><th>Programa</th><th>Estado</th></tr>
        </thead>
        <tbody id="historialAprendizBody">
          <tr>
            <td colspan="3" style="text-align:center;color:var(--text-muted);padding:var(--sp-6)">
              <div class="spinner"></div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script>
// Actualizar donut real con datos del backend
function actualizarDonut(pct, retardo, ausente, total) {
  const CIRCUM = 326;
  const presente = total - retardo - ausente;

  const arcPresente = total > 0 ? (presente / total) * CIRCUM : 0;
  const arcRetardo  = total > 0 ? (retardo  / total) * CIRCUM : 0;
  const arcAusente  = total > 0 ? (ausente  / total) * CIRCUM : 0;
  const offset      = 0;

  const dp = document.getElementById('donutPresente');
  const dr = document.getElementById('donutRetardo');
  if (dp) dp.setAttribute('stroke-dasharray', `${arcPresente} ${CIRCUM - arcPresente}`);
  if (dr) {
    dr.setAttribute('stroke-dasharray', `${arcRetardo} ${CIRCUM - arcRetardo}`);
    dr.setAttribute('stroke-dashoffset', `-${arcPresente}`);
  }

  const txt = document.getElementById('attendanceCircleText');
  if (txt) txt.textContent = pct + '%';

  const lp = document.getElementById('leyendaPresente');
  const lr = document.getElementById('leyendaRetardo');
  const la = document.getElementById('leyendaAusente');
  if (lp) lp.textContent = presente + ' sesiones';
  if (lr) lr.textContent = retardo  + ' sesiones';
  if (la) la.textContent = ausente  + ' sesiones';

  const p2 = document.getElementById('statAsistenciaPct2');
  if (p2) p2.textContent = pct + '%';
  const tr = document.getElementById('statAsistenciaTrend');
  if (tr) {
    tr.textContent = pct >= 80 ? 'Bueno' : pct >= 60 ? 'Regular' : 'Mejorar';
    tr.className   = 'stat-card__trend ' + (pct >= 80 ? 'stat-card__trend--up' : 'stat-card__trend--down');
  }
}
</script>
