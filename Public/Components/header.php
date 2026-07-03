<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="AttendQR — Sistema de Control de Asistencia mediante QR Dinámico">
  <title><?= htmlspecialchars($pageTitle ?? 'AttendQR') ?> — AttendQR</title>

  <!-- Design System CSS -->
  <link rel="stylesheet" href="Assets/CSS/variables.css">
  <link rel="stylesheet" href="Assets/CSS/reset.css">
  <link rel="stylesheet" href="Assets/CSS/style.css">
  <link rel="stylesheet" href="Assets/CSS/layout.css">
  <link rel="stylesheet" href="Assets/CSS/components.css">

  <!-- View-specific CSS -->
  <?php if (!empty($viewCss)): foreach ($viewCss as $css): ?>
  <link rel="stylesheet" href="Assets/CSS/<?= htmlspecialchars($css) ?>">
  <?php endforeach; endif; ?>

  <!-- Favicon placeholder -->
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='6' fill='%2339A900'/><text y='24' x='5' font-size='22' font-family='Arial' fill='white' font-weight='bold'>A</text></svg>">
</head>
<body>
