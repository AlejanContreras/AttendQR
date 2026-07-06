<?php /* Variables esperadas: $pageTitle, $userRole, $userName, $userInitials */ ?>
<header class="topbar" id="topbar">

  <!-- Left: toggle + título -->
  <div class="topbar__left">
    <button class="topbar__toggle" id="sidebarToggle" aria-label="Abrir/cerrar menú">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
    </button>
    <span class="topbar__page-title"><?= htmlspecialchars($pageTitle ?? 'AttendQR') ?></span>
  </div>

  <!-- Right: acciones + usuario -->
  <div class="topbar__right">

    <span id="topbarClock" style="font-size:var(--text-xs);color:var(--text-muted);padding:0 8px"></span>

    <!-- Logout -->
    <button class="topbar__btn" title="Cerrar sesión" aria-label="Cerrar sesión" onclick="auth.logout()">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
      </svg>
    </button>

    <!-- User chip -->
    <div class="topbar__user" onclick="window.location.href='index.php?view=perfil&rol=<?= htmlspecialchars($userRole ?? '') ?>'"
         style="cursor:pointer" title="Ver mi perfil">
      <div class="topbar__user-avatar" data-usuario-iniciales><?= htmlspecialchars($userInitials ?? 'U') ?></div>
      <div style="display:flex;flex-direction:column;line-height:1.2">
        <span class="topbar__user-name" data-usuario-nombre><?= htmlspecialchars($userName ?? '') ?></span>
        <span class="topbar__user-role"><?= ucfirst(htmlspecialchars($userRole ?? '')) ?></span>
      </div>
    </div>

  </div>
</header>
