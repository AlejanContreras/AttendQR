<?php /* Historial de Asistencia — Vista parcial */ ?>

<div class="page-header">
  <div>
    <h1 class="page-header__title">Historial de Asistencia</h1>
    <p class="page-header__sub">Registro completo de sesiones y asistencia por ficha</p>
  </div>
  <button class="btn btn-secondary btn-sm">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:16px;height:16px">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
    </svg>
    Exportar
  </button>
</div>

<!-- ─── Filter Bar ────────────────────────────────────────────────────── -->
<div class="filter-bar">
  <div class="filter-bar__row">
    <div class="filter-bar__group">
      <label class="filter-bar__label">Ficha</label>
      <select class="form-control" id="filterFicha" onchange="historial.filtrar()">
        <option value="">Todas</option>
        <option value="1">2345678 · Análisis y Desarrollo</option>
        <option value="2">2345679 · Diseño Web</option>
        <option value="3">2345680 · Bases de Datos</option>
      </select>
    </div>
    <div class="filter-bar__group">
      <label class="filter-bar__label">Estado</label>
      <select class="form-control" id="filterEstado" onchange="historial.filtrar()">
        <option value="">Todos</option>
        <option value="abierta">Abierta</option>
        <option value="cerrada">Cerrada</option>
        <option value="cancelada">Cancelada</option>
      </select>
    </div>
    <div class="filter-bar__group">
      <label class="filter-bar__label">Desde</label>
      <input type="date" class="form-control" id="filterDesde">
    </div>
    <div class="filter-bar__group">
      <label class="filter-bar__label">Hasta</label>
      <input type="date" class="form-control" id="filterHasta">
    </div>
    <div class="filter-bar__actions">
      <button class="btn btn-primary btn-sm" onclick="historial.filtrar()">Filtrar</button>
      <button class="btn btn-ghost btn-sm"   onclick="historial.limpiar()">Limpiar</button>
    </div>
  </div>
</div>

<!-- ─── Summary Cards ────────────────────────────────────────────────── -->
<div class="historial-summary">
  <div class="summary-card summary-card--sessions">
    <div class="summary-card__num">28</div>
    <div class="summary-card__label">Total sesiones</div>
  </div>
  <div class="summary-card summary-card--sessions">
    <div class="summary-card__num" style="color:var(--text-secondary)">25</div>
    <div class="summary-card__label">Cerradas</div>
  </div>
  <div class="summary-card summary-card--present">
    <div class="summary-card__num">2</div>
    <div class="summary-card__label">Abiertas</div>
  </div>
  <div class="summary-card summary-card--absent">
    <div class="summary-card__num">1</div>
    <div class="summary-card__label">Canceladas</div>
  </div>
  <div class="summary-card summary-card--pct">
    <div class="summary-card__num">87%</div>
    <div class="summary-card__label">Asistencia media</div>
  </div>
</div>

<!-- ─── Sessions Table ───────────────────────────────────────────────── -->
<div class="card">
  <div class="card-header">
    <div>
      <h3 class="card-title">Sesiones</h3>
      <p class="card-subtitle">28 registros encontrados</p>
    </div>
  </div>
  <div class="card-body" style="padding:0">
    <div class="table-wrapper">
      <table class="table table--hover">
        <thead>
          <tr>
            <th style="width:40px"></th>
            <th>Ficha / Programa</th>
            <th>Fecha</th>
            <th>Apertura</th>
            <th>Cierre</th>
            <th>Estado</th>
            <th>Pres.</th>
            <th>Tard.</th>
            <th>Aus.</th>
            <th>Asistencia</th>
          </tr>
        </thead>
        <tbody>

          <!-- Row 1 -->
          <tr class="session-row" data-session="s1">
            <td>
              <button class="session-expand-toggle" onclick="historial.toggle('s1', this)">
                <span class="session-expand-toggle__icon">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                       stroke="currentColor" style="width:14px;height:14px">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                </span>
              </button>
            </td>
            <td>
              <div style="font-weight:var(--fw-medium)">2345678</div>
              <div style="font-size:var(--text-xs);color:var(--text-muted)">Análisis y Desarrollo</div>
            </td>
            <td>03/07/2025</td>
            <td>08:00</td>
            <td>—</td>
            <td><span class="badge badge-success">Abierta</span></td>
            <td>18</td><td>1</td><td>1</td>
            <td class="pct-cell">
              <span>90%</span>
              <div class="pct-cell__bar"><div class="pct-cell__fill" style="width:90%"></div></div>
            </td>
          </tr>
          <tr class="session-detail-row" id="detail-s1">
            <td colspan="10">
              <div class="session-detail-inner">
                <h4 style="font-size:var(--text-sm);font-weight:var(--fw-semibold);margin-bottom:var(--sp-3)">
                  Detalle — Ficha 2345678
                </h4>
                <table class="mini-table">
                  <thead><tr><th>Aprendiz</th><th>Documento</th><th>Registro</th><th>Estado</th></tr></thead>
                  <tbody>
                    <tr><td>María García</td><td>1098765432</td><td>08:03</td><td><span class="badge badge-success">Presente</span></td></tr>
                    <tr><td>Juan López</td><td>1001234567</td><td>08:12</td><td><span class="badge badge-warning">Tardanza</span></td></tr>
                    <tr><td>Ana Martínez</td><td>1087654321</td><td>—</td><td><span class="badge badge-danger">Ausente</span></td></tr>
                  </tbody>
                </table>
              </div>
            </td>
          </tr>

          <!-- Row 2 -->
          <tr class="session-row" data-session="s2">
            <td>
              <button class="session-expand-toggle" onclick="historial.toggle('s2', this)">
                <span class="session-expand-toggle__icon">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                       stroke="currentColor" style="width:14px;height:14px">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                </span>
              </button>
            </td>
            <td>
              <div style="font-weight:var(--fw-medium)">2345679</div>
              <div style="font-size:var(--text-xs);color:var(--text-muted)">Diseño Web</div>
            </td>
            <td>03/07/2025</td>
            <td>10:00</td>
            <td>—</td>
            <td><span class="badge badge-success">Abierta</span></td>
            <td>14</td><td>0</td><td>2</td>
            <td class="pct-cell">
              <span>87%</span>
              <div class="pct-cell__bar"><div class="pct-cell__fill" style="width:87%"></div></div>
            </td>
          </tr>
          <tr class="session-detail-row" id="detail-s2">
            <td colspan="10">
              <div class="session-detail-inner">
                <p style="font-size:var(--text-sm);color:var(--text-muted)">Sin detalle disponible en Fase 1.</p>
              </div>
            </td>
          </tr>

          <!-- Row 3 -->
          <tr class="session-row" data-session="s3">
            <td>
              <button class="session-expand-toggle" onclick="historial.toggle('s3', this)">
                <span class="session-expand-toggle__icon">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                       stroke="currentColor" style="width:14px;height:14px">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                </span>
              </button>
            </td>
            <td>
              <div style="font-weight:var(--fw-medium)">2345680</div>
              <div style="font-size:var(--text-xs);color:var(--text-muted)">Bases de Datos</div>
            </td>
            <td>02/07/2025</td>
            <td>14:00</td>
            <td>15:30</td>
            <td><span class="badge badge-neutral">Cerrada</span></td>
            <td>9</td><td>1</td><td>1</td>
            <td class="pct-cell">
              <span>81%</span>
              <div class="pct-cell__bar"><div class="pct-cell__fill pct-cell__fill--warning" style="width:81%"></div></div>
            </td>
          </tr>
          <tr class="session-detail-row" id="detail-s3">
            <td colspan="10">
              <div class="session-detail-inner">
                <p style="font-size:var(--text-sm);color:var(--text-muted)">Sin detalle disponible en Fase 1.</p>
              </div>
            </td>
          </tr>

          <!-- Row 4 -->
          <tr class="session-row" data-session="s4">
            <td>
              <button class="session-expand-toggle" onclick="historial.toggle('s4', this)">
                <span class="session-expand-toggle__icon">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                       stroke="currentColor" style="width:14px;height:14px">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                </span>
              </button>
            </td>
            <td>
              <div style="font-weight:var(--fw-medium)">2345678</div>
              <div style="font-size:var(--text-xs);color:var(--text-muted)">Análisis y Desarrollo</div>
            </td>
            <td>02/07/2025</td>
            <td>08:00</td>
            <td>09:30</td>
            <td><span class="badge badge-neutral">Cerrada</span></td>
            <td>19</td><td>1</td><td>0</td>
            <td class="pct-cell">
              <span>95%</span>
              <div class="pct-cell__bar"><div class="pct-cell__fill" style="width:95%"></div></div>
            </td>
          </tr>
          <tr class="session-detail-row" id="detail-s4">
            <td colspan="10">
              <div class="session-detail-inner">
                <p style="font-size:var(--text-sm);color:var(--text-muted)">Sin detalle disponible en Fase 1.</p>
              </div>
            </td>
          </tr>

          <!-- Row 5 — cancelled -->
          <tr class="session-row" data-session="s5">
            <td>
              <button class="session-expand-toggle" disabled style="opacity:.3;cursor:not-allowed">
                <span class="session-expand-toggle__icon">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                       stroke="currentColor" style="width:14px;height:14px">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                </span>
              </button>
            </td>
            <td>
              <div style="font-weight:var(--fw-medium)">2345679</div>
              <div style="font-size:var(--text-xs);color:var(--text-muted)">Diseño Web</div>
            </td>
            <td>01/07/2025</td>
            <td>10:00</td>
            <td>—</td>
            <td><span class="badge badge-danger">Cancelada</span></td>
            <td>—</td><td>—</td><td>—</td>
            <td style="color:var(--text-muted)">—</td>
          </tr>

        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer">
    <span style="font-size:var(--text-sm);color:var(--text-muted)">Mostrando 5 de 28 sesiones</span>
    <div style="display:flex;gap:var(--sp-2)">
      <button class="btn btn-ghost btn-sm" disabled>Anterior</button>
      <button class="btn btn-ghost btn-sm" style="background:var(--green-primary);color:#fff">1</button>
      <button class="btn btn-ghost btn-sm">2</button>
      <button class="btn btn-ghost btn-sm">3</button>
      <button class="btn btn-ghost btn-sm">Siguiente</button>
    </div>
  </div>
</div>
