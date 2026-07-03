<?php
declare(strict_types=1);

/**
 * AttendQR — Layout Shell (Frontend Fase 1)
 *
 * Router visual: carga la vista solicitada dentro del layout principal.
 * No conecta con la API. Todo el contenido es UI simulada.
 *
 * Parámetros GET:
 *   ?view=dashboard-docente   (vista a renderizar)
 *   ?rol=docente              (rol simulado: docente | aprendiz)
 */

// ─── Configuración de vistas ────────────────────────────────────────
$allowedViews = [
    'dashboard-docente',
    'dashboard-aprendiz',
    'crear-sesion',
    'qr',
    'historial',
    'perfil',
    '404',
];

$currentView = $_GET['view'] ?? 'dashboard-docente';
$userRole    = $_GET['rol']  ?? 'docente';

if (!in_array($currentView, $allowedViews, true)) {
    $currentView = '404';
}

// Si la vista es aprendiz pero el rol es docente, ajustar
if ($currentView === 'dashboard-aprendiz') {
    $userRole = 'aprendiz';
}

// ─── Datos de usuario simulados ─────────────────────────────────────
$usuarios = [
    'docente' => [
        'nombre'    => 'Carlos Rodríguez',
        'iniciales' => 'CR',
        'subtitulo' => 'Docente — SENA Medellín',
        'email'     => 'c.rodriguez@sena.edu.co',
    ],
    'aprendiz' => [
        'nombre'    => 'María García',
        'iniciales' => 'MG',
        'subtitulo' => 'Aprendiz · Ficha 2345678',
        'documento' => '1098765432',
    ],
];

$usuario      = $usuarios[$userRole] ?? $usuarios['docente'];
$userName     = $usuario['nombre'];
$userInitials = $usuario['iniciales'];
$userSubtitle = $usuario['subtitulo'];

// ─── Títulos de página ───────────────────────────────────────────────
$pageTitles = [
    'dashboard-docente'  => 'Panel Docente',
    'dashboard-aprendiz' => 'Panel Aprendiz',
    'crear-sesion'       => 'Nueva Sesión de Clase',
    'qr'                 => 'QR Dinámico',
    'historial'          => 'Historial de Asistencia',
    'perfil'             => 'Mi Perfil',
    '404'                => 'Página no encontrada',
];

$pageTitle = $pageTitles[$currentView] ?? 'AttendQR';

// ─── CSS adicional por vista ─────────────────────────────────────────
$viewCssMap = [
    'dashboard-docente'  => ['dashboard.css'],
    'dashboard-aprendiz' => ['dashboard.css'],
    'crear-sesion'       => [],
    'qr'                 => ['qr.css'],
    'historial'          => ['historial.css'],
    'perfil'             => ['perfil.css'],
];

$viewCss = $viewCssMap[$currentView] ?? [];

// ─── Archivo de vista ────────────────────────────────────────────────
$viewFile = __DIR__ . '/Views/' . $currentView . '.php';
?>
<?php include 'Components/header.php'; ?>

<div class="app-shell">

  <?php include 'Components/sidebar.php'; ?>

  <div class="main-wrapper" id="mainWrapper">

    <?php include 'Components/navbar.php'; ?>

    <main class="content-area" id="contentArea">
      <?php if (file_exists($viewFile)): ?>
        <?php include $viewFile; ?>
      <?php else: ?>
        <?php include 'Views/404.php'; ?>
      <?php endif; ?>
    </main>

    <?php include 'Components/footer.php'; ?>

  </div><!-- /main-wrapper -->

</div><!-- /app-shell -->

<!-- Overlay (sidebar mobile) -->
<div class="overlay" id="overlay" onclick="AttendQR.sidebar.close()"></div>

<?php include 'Components/modal.php'; ?>
<?php include 'Components/loader.php'; ?>

<!-- JavaScript -->
<script src="Assets/JS/utils/utils.js"></script>
<script src="Assets/JS/auth/auth.js"></script>

<?php
$viewJs = [
    'dashboard-docente'  => 'Assets/JS/dashboard/dashboard.js',
    'dashboard-aprendiz' => 'Assets/JS/dashboard/dashboard.js',
    'crear-sesion'       => 'Assets/JS/sesiones/sesiones.js',
    'qr'                 => 'Assets/JS/qr/qr.js',
    'historial'          => 'Assets/JS/historial/historial.js',
    'perfil'             => 'Assets/JS/perfil/perfil.js',
];

if (isset($viewJs[$currentView])): ?>
<script src="<?= htmlspecialchars($viewJs[$currentView]) ?>"></script>
<?php endif; ?>

</body>
</html>
