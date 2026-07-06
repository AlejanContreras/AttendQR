<?php /* QR Dinámico — Vista parcial (Fase 2) */ ?>

<div class="page-header">
  <div>
    <h1 class="page-header__title">QR Dinámico</h1>
    <p class="page-header__sub">
      <span id="qrFichaCodigo">—</span> · <span id="qrFichaProgram">Cargando...</span>
    </p>
  </div>
  <div style="display:flex;gap:var(--sp-3)">
    <button class="btn btn-danger" id="btnCerrarSesion">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:16px;height:16px">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
      </svg>
      Cerrar clase
    </button>
  </div>
</div>

<!-- ─── Session status bar ───────────────────────────────────────────── -->
<div class="session-status-bar">
  <div style="display:flex;align-items:center;gap:var(--sp-2)">
    <span class="session-status-bar__dot"></span>
    <strong>Sesión abierta</strong>
  </div>
  <div style="display:flex;gap:var(--sp-3);font-size:var(--text-sm);color:var(--text-secondary)">
    <span>Inicio: <strong id="qrHoraApertura">—</strong></span>
    <span>·</span>
    <span>Tiempo: <strong id="sessionElapsed">00:00:00</strong></span>
  </div>
</div>

<!-- ─── QR Layout ────────────────────────────────────────────────────── -->
<div class="qr-layout">

  <!-- Left: QR Display Card -->
  <div class="qr-display-card">

    <!-- Session info header -->
    <div class="qr-session-info">
      <div class="qr-session-info__ficha">Ficha <span id="qrFichaCodigo2">—</span></div>
      <div class="qr-session-info__program" id="qrFichaProgram2">—</div>
      <div class="qr-session-info__date">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:14px;height:14px">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <?php
          $dias = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
          $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
          $hoy = new DateTime();
          echo $dias[(int)$hoy->format('w')] . ', ' . $hoy->format('j') . ' de ' . $meses[(int)$hoy->format('n')-1] . ' de ' . $hoy->format('Y');
        ?>
      </div>
    </div>

    <!-- QR Frame -->
    <div class="qr-frame">
      <div class="qr-code-wrap" id="qrCodeWrap">
        <div class="qr-corner qr-corner--tl"></div>
        <div class="qr-corner qr-corner--tr"></div>
        <div class="qr-corner qr-corner--bl"></div>
        <div class="qr-corner qr-corner--br"></div>

        <!-- QR generado dinámicamente por qrcode.js — cambia en cada rotación -->
        <div id="qrCanvas"
             style="display:flex;align-items:center;justify-content:center;
                    width:240px;height:240px;background:#fff;border-radius:8px">
          <div class="spinner"></div>
        </div>

        <!-- Refresh overlay -->
        <div id="qrRefreshOverlay" class="qr-expired-overlay" style="display:none">
          <div class="spinner"></div>
          <span style="font-size:var(--text-xs);color:var(--text-muted)">Rotando token...</span>
        </div>
      </div>
    </div>

    <!-- qrcode.js — genera el bitmap QR real a partir del token -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

    <!-- Token chip -->
    <div style="text-align:center;min-height:80px">
      <div style="font-size:var(--text-xs);color:var(--text-muted);margin-bottom:var(--sp-2);text-transform:uppercase;letter-spacing:.08em;font-weight:var(--fw-semibold)">
        Token activo — copia este código
      </div>
      <div style="display:inline-flex;align-items:center;gap:var(--sp-3);background:var(--surface-alt);
                  border:2px solid var(--green-primary);border-radius:var(--r-md);padding:10px 20px;
                  cursor:pointer;transition:background var(--t-fast)"
           onclick="navigator.clipboard?.writeText(document.getElementById('qrToken').textContent).then(()=>AttendQR.toast.success('Token copiado'))"
           title="Clic para copiar">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="var(--green-primary)" style="width:18px;height:18px;flex-shrink:0">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
        </svg>
        <code id="qrToken" style="font-size:var(--text-lg);font-weight:var(--fw-bold);
                                   letter-spacing:.12em;color:var(--text-primary)">—</code>
      </div>
    </div>

    <!-- Countdown -->
    <div class="qr-countdown">
      <div class="qr-countdown__label">Próxima rotación en</div>
      <div class="qr-countdown__timer" id="qrCountdown">—</div>
      <div class="qr-countdown__bar">
        <div class="qr-countdown__progress" id="qrProgress" style="width:100%"></div>
      </div>
      <div class="qr-countdown__hint">El token se renueva automáticamente</div>
    </div>

  </div><!-- /qr-display-card -->

  <!-- Right: Stats + Info -->
  <div class="qr-sidebar">

    <!-- Real-time attendance counters -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Asistencia en tiempo real</h3>
      </div>
      <div class="card-body">
        <div class="qr-counter-grid">
          <div class="qr-counter-item qr-counter-item--present">
            <div class="qr-counter-item__num" id="countPresente">—</div>
            <div class="qr-counter-item__label">Presentes</div>
          </div>
          <div class="qr-counter-item qr-counter-item--retard">
            <div class="qr-counter-item__num" id="countTardanza">—</div>
            <div class="qr-counter-item__label">Tardanzas</div>
          </div>
          <div class="qr-counter-item qr-counter-item--absent">
            <div class="qr-counter-item__num" id="countAusente">—</div>
            <div class="qr-counter-item__label">Ausentes</div>
          </div>
          <div class="qr-counter-item qr-counter-item--pending">
            <div class="qr-counter-item__num" id="countPendiente">—</div>
            <div class="qr-counter-item__label">Pendientes</div>
          </div>
        </div>

        <div style="margin-top:var(--sp-4)">
          <div style="display:flex;justify-content:space-between;margin-bottom:var(--sp-2)">
            <span style="font-size:var(--text-sm);color:var(--text-secondary)">Registrados</span>
            <strong id="countTotal">—</strong>
          </div>
          <div class="progress">
            <div class="progress-bar" id="attendanceBar" style="width:0%;background:var(--green-primary)"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Session info -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Información de sesión</h3>
      </div>
      <div class="card-body">
        <div style="display:flex;flex-direction:column;gap:var(--sp-3)">
          <div style="display:flex;justify-content:space-between">
            <span style="font-size:var(--text-sm);color:var(--text-muted)">Apertura</span>
            <strong style="font-size:var(--text-sm)" id="qrHoraApertura2">—</strong>
          </div>
          <div style="display:flex;justify-content:space-between">
            <span style="font-size:var(--text-sm);color:var(--text-muted)">Inicio clase (H)</span>
            <strong style="font-size:var(--text-sm)" id="qrHoraInicioClase">—</strong>
          </div>
          <div style="display:flex;justify-content:space-between">
            <span style="font-size:var(--text-sm);color:var(--text-muted)">Cierre asistencia</span>
            <strong style="font-size:var(--text-sm)" id="qrHoraCierre">—</strong>
          </div>
          <div style="display:flex;justify-content:space-between">
            <span style="font-size:var(--text-sm);color:var(--text-muted)">Límite retardo</span>
            <strong style="font-size:var(--text-sm)" id="qrLimiteRetardo">—</strong>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /qr-sidebar -->

</div><!-- /qr-layout -->
