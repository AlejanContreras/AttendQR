<?php
/**
 * AttendQR — Diagnóstico del correo de recuperación (SOLO LOCALHOST)
 * Abrir: http://localhost/AttendQR/Tests/diagnostico_correo.php
 * No muestra la contraseña de aplicación. Borrar o no subir al servidor.
 */
declare(strict_types=1);
date_default_timezone_set('America/Bogota');
header('Content-Type: text/plain; charset=UTF-8');

$host = preg_replace('/:\d+$/', '', strtolower($_SERVER['HTTP_HOST'] ?? ''));
if (!in_array($host, ['localhost', '127.0.0.1'], true)) { http_response_code(403); exit('Solo localhost.'); }

define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/Src');

function linea(string $t, bool $ok): void { echo ($ok ? '[OK]   ' : '[FALLA] ') . $t . "\n"; }

echo "=== Diagnóstico correo AttendQR ===\n\n";

// 1. Configuración
$cfgFile = SRC_PATH . '/Config/correo.php';
linea('Existe Src/Config/correo.php', is_file($cfgFile));
$cfg = is_file($cfgFile) ? require $cfgFile : [];
linea("Modo = '" . ($cfg['modo'] ?? '?') . "' (debe ser smtp)", ($cfg['modo'] ?? '') === 'smtp');
$pw = str_replace(' ', '', (string) ($cfg['smtp_password'] ?? ''));
linea('Contraseña de aplicación con 16 caracteres (' . strlen($pw) . ')', strlen($pw) === 16);

// 2. Extensiones de PHP
linea('Extensión openssl activa (necesaria para Gmail)', extension_loaded('openssl'));
linea('Extensión pdo_mysql activa', extension_loaded('pdo_mysql'));

// 3. Base de datos
try {
    require_once SRC_PATH . '/Config/database.php';
    $db = Database::getConnection();
    $cols = $db->query("SHOW COLUMNS FROM docentes LIKE 'recuperacion%'")->fetchAll();
    linea('Migración de recuperación aplicada (columnas recuperacion_*)', count($cols) === 2);
    foreach ($db->query("SELECT correo, recuperacion_expira FROM docentes ORDER BY id_docente")->fetchAll(PDO::FETCH_ASSOC) as $d) {
        echo "       docente: {$d['correo']}  | temporal vigente hasta: " . ($d['recuperacion_expira'] ?? '—') . "\n";
    }
} catch (Throwable $e) {
    linea('Conexión / consulta a la base de datos: ' . $e->getMessage(), false);
}

// 4. Envío real de prueba (sin mostrar credenciales)
$para = $_GET['para'] ?? '';
echo "\n";
if ($para === '') {
    echo "Para enviar un correo de prueba agrega al final de la URL:  ?para=tu_correo@gmail.com\n";
    exit;
}
require_once SRC_PATH . '/Libs/PHPMailer/Exception.php';
require_once SRC_PATH . '/Libs/PHPMailer/PHPMailer.php';
require_once SRC_PATH . '/Libs/PHPMailer/SMTP.php';
try {
    $m = new PHPMailer\PHPMailer\PHPMailer(true);
    $m->isSMTP();
    $m->Host = (string) $cfg['smtp_host']; $m->Port = (int) $cfg['smtp_puerto'];
    $m->SMTPAuth = true; $m->Username = (string) $cfg['smtp_usuario']; $m->Password = (string) $cfg['smtp_password'];
    $m->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; $m->CharSet = 'UTF-8'; $m->Timeout = 15;
    $m->SMTPDebug = 0; // 0 = no imprime el diálogo SMTP (ahí iría la contraseña en base64)
    $m->setFrom((string) $cfg['remitente_correo'], 'AttendQR');
    $m->addAddress($para);
    $m->Subject = 'AttendQR · Prueba de correo';
    $m->Body = 'Si lees esto, el envío por Gmail funciona.';
    $m->send();
    linea("Correo de prueba enviado a {$para}. Revisa bandeja y spam.", true);
} catch (Throwable $e) {
    linea('Envío falló: ' . ($m->ErrorInfo ?? '') . ' | ' . $e->getMessage(), false);
}
