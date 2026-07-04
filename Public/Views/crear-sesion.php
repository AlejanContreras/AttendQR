<?php /* Crear Sesión — Vista parcial */ ?>

<div class="page-header">
  <div>
    <h1 class="page-header__title">Nueva Sesión de Clase</h1>
    <p class="page-header__sub">Selecciona una ficha para abrir una sesión de asistencia</p>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:var(--sp-6);align-items:start">

  <!-- ─── Form Card ────────────────────────────────────────────────── -->
  <div class="card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Datos de la sesión</h3>
        <p class="card-subtitle">Selecciona la ficha para iniciar el registro de asistencia</p>
      </div>
    </div>
    <div class="card-body">
      <form id="formCrearSesion" onsubmit="sesiones.crear(event)">

        <!-- Ficha -->
        <div class="form-group">
          <label class="form-label" for="idFicha">
            Ficha de formación <span class="required">*</span>
          </label>
          <select id="idFicha" name="id_ficha" class="form-control" required>
            <option value="">Cargando fichas...</option>
          </select>
        </div>

        <!-- Nombre de la materia -->
        <div class="form-group">
          <label class="form-label" for="nombreMateria">Nombre de la materia</label>
          <input type="text" id="nombreMateria" name="nombre_materia" class="form-control"
                 placeholder="Ej: Bases de Datos, Programación Web...">
          <small class="form-hint">Opcional — aparecerá en el historial y el panel QR.</small>
        </div>

        <!-- Hora de inicio -->
        <div class="form-group">
          <label class="form-label" for="horaInicioClase">
            Hora oficial de inicio <span class="required">*</span>
          </label>
          <input type="time" id="horaInicioClase" name="hora_inicio_clase"
                 class="form-control" required>
          <small class="form-hint">
            Regla automática: <strong>Presente</strong> hasta H+5 min ·
            <strong>Retardo</strong> H+6 a H+20 min ·
            <strong>Cerrado</strong> después de H+20 min.
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
              <div style="font-size:var(--text-sm);font-weight:var(--fw-medium);color:var(--text-primary)">Selecciona la ficha</div>
              <div style="font-size:var(--text-xs);color:var(--text-muted)">El sistema usa los parámetros de jornada configurados para esa ficha</div>
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
              <div style="font-size:var(--text-sm);font-weight:var(--fw-medium);color:var(--text-primary)">Aprendices registran</div>
              <div style="font-size:var(--text-xs);color:var(--text-muted)">Cada aprendiz ingresa el token en su dispositivo para registrar asistencia</div>
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

    <!-- Sesiones activas — cargadas dinámicamente por sesiones.js -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Sesiones activas</h3>
        <span class="badge badge-success" id="sesionesActivasBadge">cargando...</span>
      </div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:var(--sp-3)" id="sesionesActivasSidebar">
        <div class="spinner" style="width:20px;height:20px;margin:var(--sp-2) auto"></div>
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
