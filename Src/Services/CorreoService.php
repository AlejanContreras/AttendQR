<?php

declare(strict_types=1);

/**
 * AttendQR – CorreoService
 *
 * [Recuperación de cuenta del docente] Envío de correos con PHPMailer
 * (Src/Libs/PHPMailer, sin Composer). La configuración vive en
 * Src/Config/correo.php (ver correo.example.php).
 *
 * Nunca lanza excepciones hacia afuera: devuelve true/false y deja el
 * detalle en el error_log de PHP. Así un fallo de Gmail no rompe la API.
 *
 * Ubicación en el proyecto: Src/Services/CorreoService.php
 */
class CorreoService
{
    /** @var array<string, mixed>|null */
    private ?array $config;

    public function __construct()
    {
        $archivo      = SRC_PATH . '/Config/correo.php';
        $this->config = is_file($archivo) ? (require $archivo) : null;
    }

    /** ¿Hay un modo de envío configurado? */
    public function estaConfigurado(): bool
    {
        return is_array($this->config)
            && in_array($this->config['modo'] ?? '', ['smtp', 'archivo'], true);
    }

    public function urlLogin(): string
    {
        return (string) ($this->config['url_login'] ?? '');
    }

    /**
     * Envía un correo. Devuelve true si se entregó al servidor SMTP
     * (o se guardó en el archivo local en modo 'archivo').
     */
    public function enviar(string $destino, string $nombreDestino, string $asunto, string $html, string $texto): bool
    {
        if (!$this->estaConfigurado()) {
            error_log('[AttendQR][Correo] Envío omitido: Src/Config/correo.php no existe o está desactivado.');
            return false;
        }

        $modo = $this->config['modo'];

        if ($modo === 'archivo') {
            return $this->guardarEnArchivo($destino, $asunto, $texto);
        }

        return $this->enviarSmtp($destino, $nombreDestino, $asunto, $html, $texto);
    }

    private function enviarSmtp(string $destino, string $nombreDestino, string $asunto, string $html, string $texto): bool
    {
        require_once SRC_PATH . '/Libs/PHPMailer/Exception.php';
        require_once SRC_PATH . '/Libs/PHPMailer/PHPMailer.php';
        require_once SRC_PATH . '/Libs/PHPMailer/SMTP.php';

        $c = $this->config;

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = (string) ($c['smtp_host'] ?? 'smtp.gmail.com');
            $mail->Port       = (int) ($c['smtp_puerto'] ?? 587);
            $mail->SMTPAuth   = true;
            $mail->Username   = (string) ($c['smtp_usuario'] ?? '');
            $mail->Password   = (string) ($c['smtp_password'] ?? '');
            $mail->SMTPSecure = $mail->Port === 465
                ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 15;

            $mail->setFrom(
                (string) ($c['remitente_correo'] ?? $mail->Username),
                (string) ($c['remitente_nombre'] ?? 'AttendQR')
            );
            $mail->addAddress($destino, $nombreDestino);
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body    = $html;
            $mail->AltBody = $texto;

            $mail->send();
            return true;

        } catch (\Throwable $e) {
            // Nunca se registra la contraseña temporal, solo el error técnico.
            error_log('[AttendQR][Correo] Error SMTP: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Modo 'archivo': solo funciona en localhost. En un servidor público el
     * archivo podría quedar expuesto, por eso se rechaza.
     */
    private function guardarEnArchivo(string $destino, string $asunto, string $texto): bool
    {
        $host = strtolower((string) ($_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? ''));
        $host = preg_replace('/:\d+$/', '', $host) ?? $host;
        if (!in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            error_log("[AttendQR][Correo] Modo 'archivo' rechazado: solo se permite en localhost.");
            return false;
        }

        $dir = ROOT_PATH . '/Storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $bloque = str_repeat('=', 60) . "\n"
            . date('Y-m-d H:i:s') . "\nPara: {$destino}\nAsunto: {$asunto}\n\n{$texto}\n\n";

        return file_put_contents($dir . '/correos_locales.log', $bloque, FILE_APPEND | LOCK_EX) !== false;
    }
}
