<?php /* Registro de Asistencia — Vista parcial (solo aprendiz) */ ?>

<div class="page-header">
  <div>
    <h1 class="page-header__title">Registrar Asistencia</h1>
    <p class="page-header__sub">Ingresa el token QR que te muestra tu docente</p>
  </div>
</div>

<div style="max-width:520px;margin:0 auto">

  <!-- ─── Card de registro ─────────────────────────────────────────── -->
  <div class="card">
    <div class="card-header">
      <div style="display:flex;align-items:center;gap:var(--sp-3)">
        <div style="width:40px;height:40px;border-radius:var(--r-md);background:var(--green-light);
                    display:flex;align-items:center;justify-content:center">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="var(--green-primary)" style="width:20px;height:20px">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
          </svg>
        </div>
        <div>
          <h3 class="card-title">Token QR</h3>
          <p class="card-subtitle">El token cambia cada 30 segundos — ingrésalo rápido</p>
        </div>
      </div>
    </div>
    <div class="card-body">

      <!-- QR scan area placeholder -->
      <div style="position:relative;width:200px;height:200px;margin:0 auto var(--sp-5);
                  border:2px dashed var(--border);border-radius:var(--r-lg);
                  display:flex;flex-direction:column;align-items:center;justify-content:center;
                  gap:var(--sp-2);color:var(--text-muted);background:var(--surface-alt)">
        <!-- Corner markers -->
        <span style="position:absolute;top:8px;left:8px;width:20px;height:20px;border-top:3px solid var(--green-primary);border-left:3px solid var(--green-primary);border-radius:2px 0 0 0"></span>
        <span style="position:absolute;top:8px;right:8px;width:20px;height:20px;border-top:3px solid var(--green-primary);border-right:3px solid var(--green-primary);border-radius:0 2px 0 0"></span>
        <span style="position:absolute;bottom:8px;left:8px;width:20px;height:20px;border-bottom:3px solid var(--green-primary);border-left:3px solid var(--green-primary);border-radius:0 0 0 2px"></span>
        <span style="position:absolute;bottom:8px;right:8px;width:20px;height:20px;border-bottom:3px solid var(--green-primary);border-right:3px solid var(--green-primary);border-radius:0 0 2px 0"></span>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:48px;height:48px;opacity:.3">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <span style="font-size:var(--text-xs);text-align:center;max-width:140px;line-height:1.4">
          Cámara no disponible · Ingresa el token manualmente
        </span>
      </div>

      <form id="formRegistrarAsistencia" onsubmit="asistencia.registrar(event)">

        <div class="form-group">
          <label class="form-label" for="tokenInput">
            Token de sesión <span class="required">*</span>
          </label>
          <div class="input-group">
            <span class="input-group__icon">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
              </svg>
            </span>
            <input type="text" id="tokenInput" class="form-control"
                   placeholder="Ingresa el token que aparece en pantalla"
                   autocomplete="off" autocorrect="off" spellcheck="false"
                   style="font-family:monospace;font-size:var(--text-sm);letter-spacing:.03em">
          </div>
          <small style="font-size:var(--text-xs);color:var(--text-muted);margin-top:var(--sp-1);display:block">
            Copia o escribe el código hexadecimal tal como aparece en la pantalla del docente.
          </small>
        </div>

        <!-- Resultado inline -->
        <div id="resultadoArea" style="display:none;margin-bottom:var(--sp-4)"></div>

        <div style="display:flex;gap:var(--sp-3)">
          <button type="submit" class="btn btn-primary" id="btnRegistrar" style="flex:1">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:16px;height:16px">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Registrar asistencia
          </button>
          <a href="index.php?view=dashboard-aprendiz" class="btn btn-secondary">
            Volver
          </a>
        </div>

      </form>

    </div>
  </div>

  <!-- ─── Card de ayuda ────────────────────────────────────────────── -->
  <div class="card" style="margin-top:var(--sp-4)">
    <div class="card-header">
      <h3 class="card-title">¿Dónde encuentro el token?</h3>
    </div>
    <div class="card-body">
      <div style="display:flex;flex-direction:column;gap:var(--sp-3)">
        <div style="display:flex;gap:var(--sp-3);align-items:flex-start">
          <div style="width:28px;height:28px;border-radius:50%;background:var(--green-light);
                      display:flex;align-items:center;justify-content:center;
                      flex-shrink:0;font-size:var(--text-xs);font-weight:var(--fw-bold);color:var(--green-primary)">1</div>
          <div>
            <div style="font-size:var(--text-sm);font-weight:var(--fw-medium);color:var(--text-primary)">Tu docente abre una sesión</div>
            <div style="font-size:var(--text-xs);color:var(--text-muted)">El docente inicia la sesión de clase desde su panel</div>
          </div>
        </div>
        <div style="display:flex;gap:var(--sp-3);align-items:flex-start">
          <div style="width:28px;height:28px;border-radius:50%;background:var(--green-light);
                      display:flex;align-items:center;justify-content:center;
                      flex-shrink:0;font-size:var(--text-xs);font-weight:var(--fw-bold);color:var(--green-primary)">2</div>
          <div>
            <div style="font-size:var(--text-sm);font-weight:var(--fw-medium);color:var(--text-primary)">Observa el token en pantalla</div>
            <div style="font-size:var(--text-xs);color:var(--text-muted)">El token QR se muestra en la pantalla del aula y cambia cada 30 segundos</div>
          </div>
        </div>
        <div style="display:flex;gap:var(--sp-3);align-items:flex-start">
          <div style="width:28px;height:28px;border-radius:50%;background:var(--green-light);
                      display:flex;align-items:center;justify-content:center;
                      flex-shrink:0;font-size:var(--text-xs);font-weight:var(--fw-bold);color:var(--green-primary)">3</div>
          <div>
            <div style="font-size:var(--text-sm);font-weight:var(--fw-medium);color:var(--text-primary)">Ingrésalo aquí antes de que expire</div>
            <div style="font-size:var(--text-xs);color:var(--text-muted)">Tienes 30 segundos para ingresarlo. Si expira, pide el nuevo token a tu docente</div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
