<!-- Full-page loader (hidden by default, shown during async operations) -->
<div id="fullPageLoader"
     style="display:none;position:fixed;inset:0;background:rgba(255,255,255,.85);
            z-index:var(--z-toast);align-items:center;justify-content:center;
            flex-direction:column;gap:var(--sp-4)">
  <div class="spinner spinner-lg"></div>
  <span style="font-size:var(--text-sm);color:var(--text-secondary);font-weight:var(--fw-medium)"
        id="loaderMessage">Cargando...</span>
</div>

<!-- Toast container (notifications) -->
<div id="toast-container"></div>
