<?php
/* Variables esperadas: $pageTitle, $userRole, $userName, $userInitials */
?>
<header class="topbar" id="topbar">

  <!-- Left: toggle + breadcrumb -->
  <div class="topbar__left">
    <button class="topbar__toggle" id="sidebarToggle" aria-label="Abrir/cerrar menú"
            title="Abrir / cerrar menú lateral">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
    </button>
    <span class="topbar__page-title"><?= htmlspecialchars($pageTitle ?? 'AttendQR') ?></span>
  </div>

  <!-- Right: actions + user -->
  <div class="topbar__right">

    <!-- Notifications (placeholder) -->
    <div class="topbar__btn" title="Notificaciones" id="notifBtn">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
      </svg>
      <span class="topbar__badge"></span>
    </div>

    <!-- Current datetime -->
    <div style="font-size: var(--text-xs); color: var(--text-muted); padding: 0 8px; display: none" id="topbar-clock">
      <span id="clock-time"></span>
    </div>

    <!-- Role switch (demo helper) -->
    <?php if ($userRole === 'docente'): ?>
    <a href="index.php?view=dashboard-aprendiz&rol=aprendiz"
       class="btn btn-sm btn-secondary"
       title="Ver como aprendiz (demo)">
      Modo Aprendiz
    </a>
    <?php else: ?>
    <a href="index.php?view=dashboard-docente&rol=docente"
       class="btn btn-sm btn-secondary"
       title="Ver como docente (demo)">
      Modo Docente
    </a>
    <?php endif; ?>

    <!-- User chip -->
    <div class="topbar__user" id="userMenuTrigger">
      <div class="topbar__user-avatar"><?= htmlspecialchars($userInitials ?? 'U') ?></div>
      <div style="display:none" class="d-flex flex-col" id="topbarUserMeta">
        <span class="topbar__user-name"><?= htmlspecialchars($userName ?? 'Usuario') ?></span>
        <span class="topbar__user-role"><?= ucfirst(htmlspecialchars($userRole ?? 'docente')) ?></span>
      </div>
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
           style="width:14px;height:14px;color:var(--text-muted)">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
      </svg>
    </div>

  </div>
</header>
