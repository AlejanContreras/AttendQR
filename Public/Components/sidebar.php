<?php
/* Variables esperadas desde index.php:
 * $currentView  — vista activa (string)
 * $userRole     — 'docente' | 'aprendiz'
 * $userName     — nombre completo del usuario
 * $userInitials — iniciales del usuario
 * $userSubtitle — subtítulo debajo del nombre
 */

$docNavItems = [
  ['view' => 'dashboard-docente', 'label' => 'Dashboard',     'icon' => 'grid'],
  ['view' => 'crear-sesion',      'label' => 'Nueva Sesión',  'icon' => 'calendar-plus'],
  ['view' => 'qr',                'label' => 'QR Dinámico',   'icon' => 'qr-code'],
  ['view' => 'aprendices',        'label' => 'Estudiantes',   'icon' => 'users'],
  ['view' => 'historial',         'label' => 'Historial',     'icon' => 'list'],
  ['view' => 'perfil',            'label' => 'Mi Perfil',     'icon' => 'user'],
];

$aprNavItems = [
  ['view' => 'dashboard-aprendiz',   'label' => 'Dashboard',        'icon' => 'grid'],
  ['view' => 'registrar-asistencia', 'label' => 'Registrar QR',     'icon' => 'qr-code'],
  ['view' => 'historial',            'label' => 'Mi Asistencia',    'icon' => 'list'],
  ['view' => 'perfil',               'label' => 'Mi Perfil',        'icon' => 'user'],
];

$navItems = ($userRole === 'aprendiz') ? $aprNavItems : $docNavItems;

// Inline SVG icon helper
function sidebarIcon(string $name): string {
  $icons = [
    'grid' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>',
    'calendar-plus' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2zm7-6v3m0 0v-3m0 3H9m3 0h3"/>',
    'qr-code' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>',
    'list' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>',
    'users' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>',
    'user' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
    'logout' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>',
  ];
  $path = $icons[$name] ?? $icons['grid'];
  return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">' . $path . '</svg>';
}
?>

<aside class="sidebar" id="sidebar">

  <!-- Brand -->
  <div class="sidebar__brand">
    <button class="sidebar__collapse-btn" id="sidebarCollapseBtn"
            onclick="AttendQR.sidebar.toggle()" aria-label="Contraer menú" title="Contraer menú">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
      </svg>
    </button>
    <div class="sidebar__brand-logo" onclick="window.location.href='index.php?view=<?= $userRole === 'aprendiz' ? 'dashboard-aprendiz' : 'dashboard-docente' ?>&rol=<?= $userRole ?>'" style="cursor:pointer" title="Ir al dashboard">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:#fff">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
      </svg>
    </div>
    <div>
      <div class="sidebar__brand-name">AttendQR</div>
      <div class="sidebar__brand-sub">SENA · Control QR</div>
    </div>
  </div>

  <!-- User -->
  <div class="sidebar__user">
    <div class="sidebar__avatar" data-usuario-iniciales><?= htmlspecialchars($userInitials ?? 'U') ?></div>
    <div class="sidebar__user-info">
      <div class="sidebar__user-name" data-usuario-nombre><?= htmlspecialchars($userName ?? 'Usuario') ?></div>
      <div class="sidebar__user-role"><?= htmlspecialchars($userSubtitle ?? ucfirst($userRole ?? 'docente')) ?></div>
    </div>
  </div>

  <!-- Navigation -->
  <nav class="sidebar__nav">
    <div class="sidebar__section-label">Menú principal</div>

    <?php foreach ($navItems as $item): ?>
      <?php $isActive = ($currentView === $item['view']); ?>
      <a href="index.php?view=<?= $item['view'] ?>&rol=<?= $userRole ?>"
         class="sidebar__nav-item <?= $isActive ? 'is-active' : '' ?>"
         data-label="<?= htmlspecialchars($item['label']) ?>">
        <span class="sidebar__nav-icon"><?= sidebarIcon($item['icon']) ?></span>
        <span class="sidebar__nav-label"><?= htmlspecialchars($item['label']) ?></span>
      </a>
    <?php endforeach; ?>

    <div class="sidebar__section-label" style="margin-top:8px">Cuenta</div>
  </nav>

  <!-- Footer / Logout -->
  <div class="sidebar__footer">
    <button class="sidebar__nav-item" data-label="Cerrar sesión"
            onclick="auth.logout()"
            style="border-radius:8px;margin:0;padding:10px 12px;color:#8FA4BB;
                   width:100%;text-align:left;background:none;border:none;cursor:pointer">
      <span class="sidebar__nav-icon"><?= sidebarIcon('logout') ?></span>
      <span class="sidebar__nav-label">Cerrar sesión</span>
    </button>
  </div>

</aside>
