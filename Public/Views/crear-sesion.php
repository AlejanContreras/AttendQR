<?php /* Crear Sesión — Vista parcial */ ?>

<div class="page-header">
  <div>
    <h1 class="page-header__title">Nueva Sesión de Clase</h1>
    <p class="page-header__sub">Configura los parámetros de la sesión de asistencia</p>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:var(--sp-6);align-items:start">

  <!-- ─── Form Card ────────────────────────────────────────────────── -->
  <div class="card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Datos de la sesión</h3>
        <p class="card-subtitle">Todos los campos marcados con * son obligatorios</p>
      </div>
    </div>
    <div class="card-body">
      <form id="formCrearSesion" onsubmit="sesiones.crear(event)">

        <!-- Ficha -->
        <div class="form-group">
          <label class="form-label" for="idFicha">
            Ficha <span class="required">*</span>
          </label>
          <select id="idFicha" name="id_ficha" class="form-control" required>
            <option value="">— Selecciona una ficha —</option>
            <option value="1">2345678 · Análisis y Desarrollo de Software</option>
            <option value="2">2345679 · Diseño y Desarrollo Web</option>
            <option value="3">2345680 · Bases de Datos</option>
          </select>
        </div>

        <!-- Fecha -->
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="fechaSesion">
              Fecha de sesión <span class="required">*</span>
            </label>
            <input type="date" id="fechaSesion" name="fecha_sesion"
                   class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="horaInicio">
              Hora de inicio clase <span class="required">*</span>
            </label>
            <input type="time" id="horaInicio" name="hora_inicio_clase"
                   class="form-control" value="08:00" required>
          </div>
        </div>

        <!-- Duración y límite retardo -->
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="duracionMaxima">
              Duración máxima registro (min) <span class="required">*</span>
            </label>
            <input type="number" id="duracionMaxima" name="duracion_maxima"
                   class="form-control" min="15" max="120" value="30"
                   placeholder="Ej. 30" required>
            <small class="form-hint">Tiempo que estará abierto el QR para registro</small>
          </div>
          <div class="form-group">
            <label class="form-label" for="limiteRetardo">
              Límite retardo (min) <span class="required">*</span>
            </label>
            <input type="number" id="limiteRetardo" name="limite_retardo"
                   class="form-control" min="1" max="60" value="10"
                   placeholder="Ej. 10" required>
            <small class="form-hint">Minutos de gracia antes de marcar tardanza</small>
          </div>
        </div>

        <!-- Rotación QR -->
        <div class="form-group">
          <label class="form-label" for="rotacionQr">
            Rotación de token QR (segundos) <span class="required">*</span>
          </label>
          <select id="rotacionQr" name="rotacion_qr" class="form-control" required>
            <option value="30">30 segundos (recomendado)</option>
            <option value="45">45 segundos</option>
            <option value="60">60 segundos</option>
            <option value="20">20 segundos (alta seguridad)</option>
          </select>
          <small class="form-hint">
            Cada cuánto se genera un nuevo token QR para evitar capturas de pantalla
          </small>
        </div>

        <!-- Buttons -->
        <div style="display:flex;gap:var(--sp-3);padding-top:var(--sp-4);border-top:1px solid var(--border)">
          <button type="submit" class="btn btn-primary" id="btnCrear">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:16px;height:16px">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Crear sesión
          </button>
          <a href="index.php?view=dashboard-docente&rol=docente" class="btn btn-secondary">
            Cancelar
          </a>
        </div>

      </form>
    </div>
  </div>

  <!-- ─── Sidebar info ─────────────────────────────────────────────── -->
  <div style="display:flex;flex-direction:column;gap:var(--sp-4)">

    <!-- Cómo funciona -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">¿Cómo funciona?</h3>
      </div>
      <div class="card-body">
        <div style="display:flex;flex-direction:column;gap:var(--sp-4)">
          <div style="display:flex;gap:var(--sp-3);align-items:flex-start">
            <div style="width:28px;height:28px;border-radius:50%;background:var(--green-light);
                        display:flex;align-items:center;justify-content:center;
                        flex-shrink:0;font-size:var(--text-xs);font-weight:var(--fw-bold);color:var(--green-primary)">1</div>
            <div>
              <div style="font-size:var(--text-sm);font-weight:var(--fw-medium);color:var(--text-primary)">Configura la sesión</div>
              <div style="font-size:var(--text-xs);color:var(--text-muted)">Selecciona la ficha, fecha y parámetros de tiempo</div>
            </div>
          </div>
          <div style="display:flex;gap:var(--sp-3);align-items:flex-start">
            <div style="width:28px;height:28px;border-radius:50%;background:var(--green-light);
                        display:flex;align-items:center;justify-content:center;
                        flex-shrink:0;font-size:var(--text-xs);font-weight:var(--fw-bold);color:var(--green-primary)">2</div>
            <div>
              <div style="font-size:var(--text-sm);font-weight:var(--fw-medium);color:var(--text-primary)">Muestra el QR</div>
              <div style="font-size:var(--text-xs);color:var(--text-muted)">El sistema genera un token QR dinámico que rota automáticamente</div>
            </div>
          </div>
          <div style="display:flex;gap:var(--sp-3);align-items:flex-start">
            <div style="width:28px;height:28px;border-radius:50%;background:var(--green-light);
                        display:flex;align-items:center;justify-content:center;
                        flex-shrink:0;font-size:var(--text-xs);font-weight:var(--fw-bold);color:var(--green-primary)">3</div>
            <div>
              <div style="font-size:var(--text-sm);font-weight:var(--fw-medium);color:var(--text-primary)">Aprendices escanean</div>
              <div style="font-size:var(--text-xs);color:var(--text-muted)">Cada aprendiz escanea con su dispositivo para registrar asistencia</div>
            </div>
          </div>
          <div style="display:flex;gap:var(--sp-3);align-items:flex-start">
            <div style="width:28px;height:28px;border-radius:50%;background:var(--green-light);
                        display:flex;align-items:center;justify-content:center;
                        flex-shrink:0;font-size:var(--text-xs);font-weight:var(--fw-bold);color:var(--green-primary)">4</div>
            <div>
              <div style="font-size:var(--text-sm);font-weight:var(--fw-medium);color:var(--text-primary)">Cierra la sesión</div>
              <div style="font-size:var(--text-xs);color:var(--text-muted)">El registro se guarda automáticamente al cerrar</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Sesiones activas -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Sesiones activas</h3>
        <span class="badge badge-success">2 abiertas</span>
      </div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:var(--sp-3)">
        <div style="padding:var(--sp-3);background:var(--surface-alt);border-radius:var(--r-md);border-left:3px solid var(--green-primary)">
          <div style="font-size:var(--text-sm);font-weight:var(--fw-medium);color:var(--text-primary)">Ficha 2345678</div>
          <div style="font-size:var(--text-xs);color:var(--text-muted)">Análisis y Desarrollo · Desde 08:00</div>
          <a href="index.php?view=qr&rol=docente" class="btn btn-ghost btn-sm" style="margin-top:var(--sp-2)">Ver QR</a>
        </div>
        <div style="padding:var(--sp-3);background:var(--surface-alt);border-radius:var(--r-md);border-left:3px solid var(--green-primary)">
          <div style="font-size:var(--text-sm);font-weight:var(--fw-medium);color:var(--text-primary)">Ficha 2345679</div>
          <div style="font-size:var(--text-xs);color:var(--text-muted)">Diseño Web · Desde 10:00</div>
          <a href="index.php?view=qr&rol=docente" class="btn btn-ghost btn-sm" style="margin-top:var(--sp-2)">Ver QR</a>
        </div>
      </div>
    </div>

  </div>

</div>

<style>
.form-hint { font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--sp-1); display: block; }
@media (max-width: 900px) {
  div[style*="grid-template-columns:1fr 340px"] { grid-template-columns: 1fr; }
}
</style>
