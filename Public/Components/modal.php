<?php
/* Modal genérico reutilizable.
 * Variables opcionales: $modalId (default 'modal'), $modalTitle, $modalSize ('sm'|'md'|'lg')
 * Uso: incluir antes de </body>, controlar con JS (modal.open() / modal.close())
 */
$modalId   = $modalId   ?? 'modal';
$modalSize = $modalSize ?? 'md';
$sizePx    = ['sm' => '420px', 'md' => '560px', 'lg' => '740px'][$modalSize] ?? '560px';
?>

<div class="modal-backdrop" id="<?= htmlspecialchars($modalId) ?>Backdrop"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:var(--z-overlay);
            align-items:center;justify-content:center;padding:var(--sp-4)">

  <div class="modal-box"
       style="background:var(--surface);border-radius:var(--r-xl);box-shadow:var(--shadow-xl);
              width:100%;max-width:<?= $sizePx ?>;max-height:90vh;display:flex;flex-direction:column;
              animation:modalIn .25s ease">

    <!-- Modal Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;
                padding:var(--sp-5) var(--sp-6);border-bottom:1px solid var(--border);">
      <h3 id="<?= htmlspecialchars($modalId) ?>Title"
          style="font-size:var(--text-md);font-weight:var(--fw-semibold);color:var(--text-primary)">
        <?= htmlspecialchars($modalTitle ?? 'Confirmación') ?>
      </h3>
      <button class="topbar__btn"
              onclick="AttendQR.modal.close('<?= htmlspecialchars($modalId) ?>')"
              aria-label="Cerrar">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Modal Body -->
    <div id="<?= htmlspecialchars($modalId) ?>Body"
         style="flex:1;overflow-y:auto;padding:var(--sp-6)">
      <!-- Content injected by JS -->
    </div>

    <!-- Modal Footer -->
    <div id="<?= htmlspecialchars($modalId) ?>Footer"
         style="display:flex;gap:var(--sp-3);justify-content:flex-end;
                padding:var(--sp-4) var(--sp-6);border-top:1px solid var(--border);">
      <button class="btn btn-secondary"
              onclick="AttendQR.modal.close('<?= htmlspecialchars($modalId) ?>')">
        Cancelar
      </button>
      <button class="btn btn-primary" id="<?= htmlspecialchars($modalId) ?>Confirm">
        Confirmar
      </button>
    </div>

  </div>
</div>

<style>
@keyframes modalIn {
  from { opacity: 0; transform: translateY(-16px) scale(.97); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}
</style>
