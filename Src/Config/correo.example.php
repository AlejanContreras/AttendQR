<?php

/**
 * AttendQR – Configuración de correo (PLANTILLA)
 *
 * [Recuperación de cuenta del docente]
 *
 * 1. Copia este archivo como  Src/Config/correo.php  (mismo directorio).
 * 2. Llena tus datos. correo.php NO se sube a Git (.gitignore): lleva una contraseña.
 *
 * Modos:
 *   'desactivado' → no se envía nada (la solicitud responde igual, pero no llega correo).
 *   'archivo'     → SOLO EN LOCALHOST: el correo se guarda en Storage/logs/correos_locales.log
 *                   para probar todo el flujo sin una cuenta de Gmail.
 *   'smtp'        → envío real por SMTP (Gmail).
 *
 * Gmail: activa la verificación en 2 pasos en la cuenta y crea una
 * "Contraseña de aplicación" (16 letras) en https://myaccount.google.com/apppasswords
 * Esa es la que va en smtp_password, NO la contraseña normal de Gmail.
 */
return [
    'modo'             => 'desactivado',

    'smtp_host'        => 'smtp.gmail.com',
    'smtp_puerto'      => 587,               // 587 = STARTTLS
    'smtp_usuario'     => 'tu.correo@gmail.com',
    'smtp_password'    => 'xxxx xxxx xxxx xxxx', // contraseña de aplicación
    'remitente_correo' => 'tu.correo@gmail.com',
    'remitente_nombre' => 'AttendQR',

    // Enlace que aparece en el correo para ir a iniciar sesión.
    // Ej.: 'https://attendqr.infinityfreeapp.com/Public/Views/login.php'
    'url_login'        => '',
];
