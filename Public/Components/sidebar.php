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
  ['view' => 'historial',         'label' => 'Historial',     'icon' => 'list'],
  ['view' => 'perfil',            'label' => 'Mi Perfil',     'icon' => 'user'],
];

$aprNavItems = [
  ['view' => 'dashboard-aprendiz', 'label' => 'Dashboard',     'icon' => 'grid'],
  ['view' => 'historial',          'label' => 'Mi Asistencia', 'icon' => 'list'],
  ['view' => 'perfil',             'label' => 'Mi Perfil',     'icon' => 'user'],
];

$navItems = ($userRole === 'aprendiz') ? $aprNavItems : $docNavItems;

// Inline SVG icon helper
function sidebarIcon(string $name): string {
  $icons = [
    'grid' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>',
    'calendar-plus' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2zm7-6v3m0 0v-3m0 3H9m3 0h3"/>',
    'qr-code' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>',
    'list' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>',
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
    <div class="sidebar__brand-logo">
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
    <div class="sidebar__avatar"><?= htmlspecialchars($userInitials ?? 'U') ?></div>
    <div class="sidebar__user-info">
      <div class="sidebar__user-name"><?= htmlspecialchars($userName ?? 'Usuario') ?></div>
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
    <a href="Views/login.php" class="sidebar__nav-item" data-label="Cerrar sesión"
       style="border-radius: 8px; margin: 0; padding: 10px 12px; color: #8FA4BB;">
      <span class="sidebar__nav-icon"><?= sidebarIcon('logout') ?></span>
      <span class="sidebar__nav-label">Cerrar sesión</span>
    </a>
  </div>

</aside>
