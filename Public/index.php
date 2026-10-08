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
<?php
// vj() = versión automática para JS (usa la función v() ya definida en header.php)
function vj(string $rel): string {
    $abs = __DIR__ . '/' . $rel;
    $ts  = file_exists($abs) ? filemtime($abs) : time();
    return htmlspecialchars($rel) . '?v=' . $ts;
}
?>
<script src="<?= vj('Assets/JS/api/api.js') ?>"></script>
<script src="<?= vj('Assets/JS/utils/utils.js') ?>"></script>
<script src="<?= vj('Assets/JS/auth/auth.js') ?>"></script>

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
<script src="<?= vj($viewJs[$currentView]) ?>"></script>
<?php endif; ?>

<?php if ($userRole === 'aprendiz' && !empty($usuario['requiere_correo'])): ?>
<!-- [Correo del aprendiz] Aprendiz sin correo: se le pide antes de seguir.
     Sirve para que pueda recuperar su contraseña sin depender del instructor. -->
<div id="modalCorreoAprendiz" style="position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,.55);
     display:flex;align-items:center;justify-content:center;padding:16px">
  <form onsubmit="return guardarCorreoAprendiz(event)" style="background:var(--surface,#fff);color:var(--text-primary,#111);
        border-radius:16px;max-width:420px;width:100%;padding:24px;box-shadow:0 20px 50px rgba(0,0,0,.3)">
    <h3 style="font-size:1.15rem;font-weight:700;margin-bottom:6px">Registra tu correo</h3>
    <p style="font-size:.9rem;color:var(--text-muted,#6b7280);margin-bottom:16px">
      Si algún día olvidas tu contraseña, te enviaremos una temporal a este correo
      sin tener que pedírsela a tu instructor.
    </p>
    <input type="email" id="correoAprendizInput" class="form-control" required maxlength="120"
           placeholder="tucorreo@gmail.com" autocomplete="email" style="margin-bottom:8px">
    <div id="correoAprendizError" style="display:none;color:var(--danger,#dc2626);font-size:.85rem;margin-bottom:8px"></div>
    <button type="submit" class="btn btn-primary btn-full" id="btnCorreoAprendiz" style="width:100%;margin-top:8px">
      Guardar y continuar
    </button>
  </form>
</div>
<script>
async function guardarCorreoAprendiz(e) {
  e.preventDefault();
  const input = document.getElementById('correoAprendizInput');
  const err   = document.getElementById('correoAprendizError');
  const btn   = document.getElementById('btnCorreoAprendiz');
  const correo = (input.value || '').trim();
  err.style.display = 'none';
  if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(correo)) {
    err.textContent = 'Ingresa un correo electrónico válido.';
    err.style.display = 'block';
    return false;
  }
  btn.disabled = true;
  try {
    await Api.aprendices.actualizar(window.ATTENDQR_USER.id, { correo });
    document.getElementById('modalCorreoAprendiz').remove();
    AttendQR?.toast?.success?.('Correo guardado. ¡Gracias!');
  } catch (ex) {
    err.textContent = ex.message || 'No se pudo guardar el correo.';
    err.style.display = 'block';
    btn.disabled = false;
  }
  return false;
}
setTimeout(() => document.getElementById('correoAprendizInput')?.focus(), 200);
</script>
<?php endif; ?>

</body>
</html>
