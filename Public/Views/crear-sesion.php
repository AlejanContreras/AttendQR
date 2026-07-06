<?php /* Crear Sesión — Vista parcial (P2: fichas cards + modal) */ ?>

<div class="page-header">
  <div>
    <h1 class="page-header__title">Gestionar clases</h1>
    <p class="page-header__sub">Selecciona una ficha e inicia la sesión de asistencia con un clic</p>
  </div>
</div>

<!-- Banner: sesiones activas (aparece cuando ya hay clases abiertas) -->
<div id="bannerSesionesActivas" style="display:none;margin-bottom:var(--sp-5)"></div>

<!-- Grid de fichas -->
<div id="fichasGrid"
     style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));
            gap:var(--sp-5);margin-bottom:var(--sp-8)">
  <div style="grid-column:1/-1;text-align:center;padding:var(--sp-12);color:var(--text-muted)">
    <div class="spinner spinner-lg" style="margin:0 auto var(--sp-4)"></div>
    <p style="font-size:var(--text-sm)">Cargando tus fichas...</p>
  </div>
</div>

<!-- Cómo funciona (colapsado al fondo, no distrae) -->
<details class="como-funciona-panel">
  <summary>
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
         style="width:16px;height:16px;flex-shrink:0">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    ¿Cómo funciona el sistema de asistencia?
  </summary>
  <div class="como-funciona-panel__body">
    <div class="como-funciona-steps">
      <?php foreach ([
        ['Selecciona la ficha',        'Elige el grupo que tienes en clase ahora.',                    '1'],
        ['Confirma la hora de inicio', 'El sistema calcula automáticamente presente / retardo.',       '2'],
        ['Muestra el QR',              'El código rota cada 30 s para evitar suplantaciones.',         '3'],
        ['Los aprendices registran',   'Ingresan el token en sus dispositivos.',                      '4'],
        ['Cierra la sesión',           'Los registros quedan guardados en el historial.',              '5'],
      ] as [$titulo, $desc, $n]): ?>
        <div class="como-funciona-step">
          <div class="como-funciona-step__num"><?= $n ?></div>
          <div>
            <div class="como-funciona-step__title"><?= htmlspecialchars($titulo) ?></div>
            <div class="como-funciona-step__desc"><?= htmlspecialchars($desc) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</details>

<!-- ─── Modal: Iniciar clase ─────────────────────────────────────────── -->
<div id="modalIniciarBackdrop" class="modal-backdrop-custom" style="display:none"
     onclick="if(event.target===this)sesiones.cerrarModal()">
  <div class="modal-custom" role="dialog" aria-modal="true" aria-labelledby="modalIniciarTitulo">

    <div class="modal-custom__header">
      <div>
        <div style="font-size:var(--text-xs);font-weight:var(--fw-semibold);
                    text-transform:uppercase;letter-spacing:.08em;
                    color:var(--green-primary);margin-bottom:var(--sp-1)">
          Iniciar sesión de asistencia
        </div>
        <h3 class="modal-custom__title" id="modalIniciarTitulo">Ficha —</h3>
        <p  class="modal-custom__sub"   id="modalIniciarSub"></p>
      </div>
      <button class="modal-custom__close" onclick="sesiones.cerrarModal()" aria-label="Cerrar">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <form id="formIniciarClase" onsubmit="sesiones.crear(event)">
      <input type="hidden" id="modalFichaId">

      <div class="modal-custom__body">

        <div class="form-group">
          <label class="form-label" for="horaInicioClase">
            Hora oficial de inicio de clase <span class="required">*</span>
          </label>
          <input type="time" id="horaInicioClase" name="hora_inicio_clase"
                 class="form-control" required
                 style="font-size:var(--text-md);font-weight:var(--fw-medium)">
          <div class="form-hint" style="display:flex;gap:var(--sp-4);margin-top:var(--sp-2)">
            <span style="display:flex;align-items:center;gap:4px">
              <span style="width:8px;height:8px;border-radius:50%;background:var(--success);display:inline-block"></span>
              Presente: H a H+5 min
            </span>
            <span style="display:flex;align-items:center;gap:4px">
              <span style="width:8px;height:8px;border-radius:50%;background:var(--warning);display:inline-block"></span>
              Retardo: H+6 a H+20 min
            </span>
            <span style="display:flex;align-items:center;gap:4px">
              <span style="width:8px;height:8px;border-radius:50%;background:var(--danger);display:inline-block"></span>
              Cerrado: después de H+20
            </span>
          </div>
        </div>

        <div class="form-group" style="margin-bottom:0">
          <label class="form-label" for="nombreMateria">Nombre de la materia</label>
          <input type="text" id="nombreMateria" name="nombre_materia"
                 class="form-control"
                 placeholder="Ej: Bases de Datos, Programación Web...">
          <span class="form-hint">Opcional — aparece en el historial y en el panel QR.</span>
        </div>

      </div>

      <div class="modal-custom__footer">
        <button type="button" class="btn btn-secondary" onclick="sesiones.cerrarModal()">
          Cancelar
        </button>
        <button type="submit" class="btn btn-primary btn-lg" id="btnCrear">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
               style="width:18px;height:18px">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          Iniciar clase
        </button>
      </div>
    </form>

  </div>
</div>

<style>
/* ─── Ficha Card ──────────────────────────────────────────── */
.ficha-card {
  background: var(--surface);
  border-radius: var(--r-lg);
  border: 1.5px solid var(--border);
  padding: var(--sp-5) var(--sp-6);
  display: flex;
  align-items: center;
  gap: var(--sp-4);
  box-shadow: var(--shadow-sm);
  transition: border-color var(--t-fast), box-shadow var(--t-fast), transform var(--t-fast);
  cursor: pointer;
}
.ficha-card:hover {
  border-color: var(--green-primary);
  box-shadow: 0 0 0 3px rgba(57,169,0,.1), var(--shadow-md);
  transform: translateY(-2px);
}
.ficha-card__icon {
  width: 52px; height: 52px;
  border-radius: var(--r-lg);
  background: var(--green-light);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  transition: background var(--t-fast);
}
.ficha-card:hover .ficha-card__icon { background: rgba(57,169,0,.2); }
.ficha-card__icon svg { width: 26px; height: 26px; color: var(--green-dark); }
.ficha-card__body   { flex: 1; min-width: 0; }
.ficha-card__code   { font-size: var(--text-md); font-weight: var(--fw-bold);
                      color: var(--text-primary); }
.ficha-card__prog   { font-size: var(--text-sm); color: var(--text-muted);
                      margin-top: 2px; white-space: nowrap; overflow: hidden;
                      text-overflow: ellipsis; }
.ficha-card__status { font-size: var(--text-xs); margin-top: var(--sp-1); }
.ficha-card__cta    { flex-shrink: 0; }

/* ─── Active sessions banner ──────────────────────────────── */
.sesion-activa-banner {
  display: flex; flex-wrap: wrap; align-items: center; gap: var(--sp-3);
  padding: var(--sp-4) var(--sp-5);
  background: var(--success-bg);
  border: 1px solid var(--success-border);
  border-radius: var(--r-lg);
  font-size: var(--text-sm);
}
.sesion-activa-banner__dot {
  width: 10px; height: 10px; border-radius: 50%;
  background: var(--success); animation: blink 1.5s ease infinite;
  flex-shrink: 0;
}
.sesion-activa-banner strong { color: var(--success-text); }
.sesion-activa-banner__list { display: flex; gap: var(--sp-2); flex-wrap: wrap; margin-left: auto; }

/* ─── Modal custom ────────────────────────────────────────── */
.modal-backdrop-custom {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.5);
  backdrop-filter: blur(2px);
  z-index: var(--z-modal);
  display: flex; align-items: center; justify-content: center;
  padding: var(--sp-4);
  animation: fadeIn .15s ease;
}
.modal-custom {
  background: var(--surface);
  border-radius: var(--r-xl);
  box-shadow: var(--shadow-xl);
  width: 100%; max-width: 520px;
  animation: slideUp .2s ease;
  overflow: hidden;
}
.modal-custom__header {
  display: flex; align-items: flex-start; justify-content: space-between;
  padding: var(--sp-6) var(--sp-6) var(--sp-5);
  border-bottom: 1px solid var(--border);
}
.modal-custom__title { font-size: var(--text-lg); font-weight: var(--fw-bold);
                       color: var(--text-primary); }
.modal-custom__sub   { font-size: var(--text-sm); color: var(--text-muted); margin-top: 2px; }
.modal-custom__close {
  width: 32px; height: 32px; border-radius: var(--r-md);
  display: flex; align-items: center; justify-content: center;
  color: var(--text-muted); cursor: pointer;
  transition: background var(--t-fast), color var(--t-fast);
  background: none; border: none; flex-shrink: 0;
}
.modal-custom__close:hover { background: var(--surface-2); color: var(--text-primary); }
.modal-custom__close svg   { width: 18px; height: 18px; }
.modal-custom__body   { padding: var(--sp-6); }
.modal-custom__footer {
  display: flex; justify-content: flex-end; gap: var(--sp-3);
  padding: var(--sp-4) var(--sp-6);
  background: var(--surface-2);
  border-top: 1px solid var(--border);
}

/* ─── Cómo funciona ───────────────────────────────────────── */
.como-funciona-panel {
  border: 1px solid var(--border);
  border-radius: var(--r-lg);
  background: var(--surface);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}
.como-funciona-panel > summary {
  display: flex; align-items: center; gap: var(--sp-2);
  padding: var(--sp-4) var(--sp-6);
  font-size: var(--text-sm); font-weight: var(--fw-medium);
  color: var(--text-secondary); cursor: pointer;
  list-style: none; user-select: none;
  transition: background var(--t-fast), color var(--t-fast);
}
.como-funciona-panel > summary:hover { background: var(--surface-2); color: var(--text-primary); }
.como-funciona-panel > summary::-webkit-details-marker { display: none; }
.como-funciona-panel__body { padding: var(--sp-5) var(--sp-6) var(--sp-6); border-top: 1px solid var(--border); }
.como-funciona-steps { display: flex; flex-direction: column; gap: var(--sp-4); }
.como-funciona-step  { display: flex; gap: var(--sp-4); align-items: flex-start; }
.como-funciona-step__num {
  width: 28px; height: 28px; border-radius: 50%;
  background: var(--green-light); color: var(--green-dark);
  font-size: var(--text-xs); font-weight: var(--fw-bold);
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.como-funciona-step__title { font-size: var(--text-sm); font-weight: var(--fw-medium);
                              color: var(--text-primary); }
.como-funciona-step__desc  { font-size: var(--text-xs); color: var(--text-muted); margin-top: 1px; }

/* ─── Animations ──────────────────────────────────────────── */
@keyframes fadeIn  { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { transform: translateY(16px); opacity: 0; }
                     to   { transform: translateY(0);    opacity: 1; } }

/* ─── Responsive ──────────────────────────────────────────── */
@media (max-width: 480px) {
  #fichasGrid { grid-template-columns: 1fr !important; }
  .ficha-card { flex-wrap: wrap; }
  .ficha-card__cta { width: 100%; }
  .ficha-card__cta .btn { width: 100%; justify-content: center; }
}
</style>
