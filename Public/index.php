<?php
declare(strict_types=1);

/**
 * AttendQR — Layout Shell (Frontend Fase 2)
 *
 * Guard de sesión PHP: si no hay sesión activa redirige a login.
 * Los datos del usuario se leen directamente de $_SESSION['usuario'].
 */

// ─── Guard de sesión ────────────────────────────────────────────────
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['usuario']) || !is_array($_SESSION['usuario'])) {
    header('Location: Views/login.php');
    exit;
}

$usuario = $_SESSION['usuario'];

// ─── Datos del usuario desde la sesión real ──────────────────────────
$userRole     = $usuario['rol']    ?? 'docente';
$userName     = $usuario['nombre'] ?? '';
$userId       = (int) ($usuario['id'] ?? 0);

// Iniciales: primeras letras de nombre y apellido
$partes        = array_filter(explode(' ', $userName));
$userInitials  = strtoupper(
    implode('', array_map(fn($p) => $p[0] ?? '', array_slice($partes, 0, 2)))
);

$userSubtitle = $userRole === 'aprendiz'
    ? 'Aprendiz — SENA'
    : 'Docente — SENA';

// ─── Vista solicitada ────────────────────────────────────────────────
$allowedViews = [
    'dashboard-docente',
    'dashboard-aprendiz',
    'crear-sesion',
    'qr',
    'historial',
    'perfil',
    'registrar-asistencia',
    'aprendices',
    '404',
];

$currentView = $_GET['view'] ?? ($userRole === 'aprendiz' ? 'dashboard-aprendiz' : 'dashboard-docente');

if (!in_array($currentView, $allowedViews, true)) {
    $currentView = '404';
}

// Protección de vistas por rol
$soloDocente  = ['crear-sesion', 'qr', 'dashboard-docente', 'aprendices'];
$soloAprendiz = ['dashboard-aprendiz', 'registrar-asistencia'];

if ($userRole === 'aprendiz' && in_array($currentView, $soloDocente, true)) {
    $currentView = 'dashboard-aprendiz';
}
if ($userRole === 'docente' && in_array($currentView, $soloAprendiz, true)) {
    $currentView = 'dashboard-docente';
}

// ─── Títulos de página ───────────────────────────────────────────────
$pageTitles = [
    'dashboard-docente'    => 'Panel Docente',
    'dashboard-aprendiz'   => 'Panel Aprendiz',
    'crear-sesion'         => 'Mis Clases',
    'qr'                   => 'QR Dinámico',
    'historial'            => 'Historial de Asistencia',
    'perfil'               => 'Mi Perfil',
    'registrar-asistencia' => 'Registrar Asistencia',
    'aprendices'           => 'Gestión de Aprendices',
    '404'                  => 'Página no encontrada',
];

$pageTitle = $pageTitles[$currentView] ?? 'AttendQR';

// ─── CSS adicional por vista ─────────────────────────────────────────
$viewCssMap = [
    'dashboard-docente'    => ['dashboard.css'],
    'dashboard-aprendiz'   => ['dashboard.css'],
    'crear-sesion'         => [],
    'qr'                   => ['qr.css'],
    'historial'            => ['historial.css'],
    'perfil'               => ['perfil.css'],
    'registrar-asistencia' => [],
    'aprendices'           => [],
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

<div class="overlay" id="overlay" onclick="AttendQR.sidebar.close()"></div>

<?php include 'Components/modal.php'; ?>
<?php include 'Components/loader.php'; ?>

<!-- Datos del usuario para JS (sin datos sensibles) -->
<script>
window.ATTENDQR_USER = <?= json_encode([
    'id'     => $userId,
    'nombre' => $userName,
    'rol'    => $userRole,
], JSON_UNESCAPED_UNICODE) ?>;
window.ATTENDQR_VIEW = <?= json_encode($currentView) ?>;
</script>

<!-- JavaScript — orden: api → utils → auth → vista específica -->
<script src="Assets/JS/api/api.js"></script>
<script src="Assets/JS/utils/utils.js"></script>
<script src="Assets/JS/auth/auth.js"></script>

<?php
$viewJs = [
    'dashboard-docente'    => 'Assets/JS/dashboard/dashboard.js',
    'dashboard-aprendiz'   => 'Assets/JS/dashboard/dashboard.js',
    'crear-sesion'         => 'Assets/JS/sesiones/sesiones.js',
    'qr'                   => 'Assets/JS/qr/qr.js',
    'historial'            => 'Assets/JS/historial/historial.js',
    'perfil'               => 'Assets/JS/perfil/perfil.js',
    'registrar-asistencia' => 'Assets/JS/asistencia/asistencia.js',
    'aprendices'           => 'Assets/JS/aprendices/aprendices.js',
];

if (isset($viewJs[$currentView])): ?>
<script src="<?= htmlspecialchars($viewJs[$currentView]) ?>"></script>
<?php endif; ?>

</body>
</html>
