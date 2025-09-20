<?php
declare(strict_types=1);

/**
 * Login administrativo — Indumaqher
 * - Funciona en subcarpeta (p.ej. /indumaqherphp)
 * - Evita bucles de redirección
 * - Config antes de sesión
 * - CSRF integrado
 */

/* ====== BASE_PATH (p.ej. "/indumaqherphp") ====== */
$scriptDir = str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')); // "/indumaqherphp/admin"
$BASE_PATH = rtrim(str_replace('\\','/', dirname($scriptDir)), '/');        // "/indumaqherphp"
if ($BASE_PATH === '' || $BASE_PATH === '.') $BASE_PATH = '';

/* ====== CARGAR CONFIG ANTES DE SESIÓN ====== */
$root  = dirname(__DIR__); // .../Indumaqherphp
$found = false;
foreach ([$root . '/config/config.php', $root . '/config.php', $root . '/config/database.php'] as $cfg) {
  if (is_file($cfg)) { require_once $cfg; $found = true; break; }
}
if (!$found) {
  http_response_code(500);
  echo "<h1>Error de configuración</h1><p>No se encontró archivo de configuración en /config.</p>";
  exit;
}

/* ====== SESIÓN (cookie limitada al proyecto) ====== */
if (session_status() === PHP_SESSION_NONE) {
  $cookiePath = $BASE_PATH ?: '/';
  session_set_cookie_params([
    'lifetime' => 3600,                       // 1h
    'path'     => $cookiePath,
    'domain'   => '',                         // localhost
    'secure'   => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  // Puedes añadir: ini_set('session.use_only_cookies','1');
  session_start();
}

/* ====== CSRF simple ====== */
function csrf_token(): string {
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}
function csrf_check(string $token): bool {
  return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/* ====== OBTENER PDO desde tu helper Database ====== */
try {
  // En tus configs existe Database::getInstance()
  $db  = Database::getInstance();
  $pdo = $db->getConnection();
} catch (Throwable $e) {
  http_response_code(500);
  die('No fue posible obtener la conexión a la BD.');
}
if (!$pdo instanceof PDO) { http_response_code(500); die('Config inválida: no hay PDO.'); }

/* ====== Si ya hay sesión, al dashboard ====== */
if (!empty($_SESSION['user_id'])) {
  header('Location: ' . $BASE_PATH . '/admin/dashboard.php'); exit;
}

/* ====== PROCESAR POST ====== */
$err = $_GET['err'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Rate limit básico por IP/minuto
  $key   = 'rl_' . ($_SERVER['REMOTE_ADDR'] ?? 'x') . '_' . date('YmdHi');
  $count = ($_SESSION[$key] ?? 0) + 1;
  $_SESSION[$key] = $count;
  if ($count > 20) { http_response_code(429); die('Demasiados intentos.'); }

  if (!csrf_check($_POST['csrf'] ?? '')) {
    header('Location: ' . $BASE_PATH . '/admin/login.php?err=csrf'); exit;
  }

  $u = trim((string)($_POST['username'] ?? ''));
  $p = (string)($_POST['password'] ?? '');

  if ($u === '' || $p === '') {
    header('Location: ' . $BASE_PATH . '/admin/login.php?err=cred'); exit;
  }

  // Busca por username o email (útil si tu esquema lo permite)
  $stmt = $pdo->prepare('SELECT id, username, email, `password`, is_active FROM users WHERE (username = ? OR email = ?) LIMIT 1');
  $stmt->execute([$u, $u]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  $ok = false;
  if ($user && (int)($user['is_active'] ?? 1) === 1) {
    $hash = (string)$user['password'];
    // Normaliza $2b$ -> $2y$ si fuera necesario
    if (str_starts_with($hash, '$2b$')) {
      $ok = password_verify($p, '$2y$' . substr($hash, 4));
    } else {
      $ok = password_verify($p, $hash);
    }
  }

  if ($ok) {
    session_regenerate_id(true);
    $_SESSION['user_id']   = (int)$user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['email']     = $user['email'] ?? '';
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time']= time();

    header('Location: ' . $BASE_PATH . '/admin/dashboard.php'); exit;
  }

  header('Location: ' . $BASE_PATH . '/admin/login.php?err=cred'); exit;
}

/* ====== CSP con NONCE para permitir solo este script inline ====== */
$nonce = base64_encode(random_bytes(16));
?>
<!doctype html>
<html lang="es" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Acceso administrativo • Indumaqher</title>

  <!-- Seguridad (CSP permite solo script con nonce y estilos locales) -->
  <meta http-equiv="Content-Security-Policy"
        content="default-src 'self';
                 img-src 'self' data:;
                 style-src 'self';
                 script-src 'self' 'nonce-<?= $nonce ?>';
                 form-action 'self';
                 base-uri 'self'">

  <meta http-equiv="X-Content-Type-Options" content="nosniff">
  <meta http-equiv="X-Frame-Options" content="DENY">

  <!-- CSS exclusivo del login -->
  <link rel="stylesheet" href="<?= htmlspecialchars($BASE_PATH, ENT_QUOTES) ?>/assets/css/login.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha384-k6RqeWeci5ZR/Lv4MR0sA0FfDOM8y+6b6f6b6f6b6f6b6f6b6f6b6f6b6f6b6" crossorigin="anonymous">
</head>
<body class="login-page">
  <main class="login-wrap">
    <section class="login-card" aria-labelledby="login-title">
      <header class="login-header">
        <div class="brand">
          <span class="brand-dot"></span>
          <span class="brand-name">Indumaqher</span>
        </div>
        <h1 id="login-title">Acceso administrativo</h1>

        <?php if ($err==='cred'): ?>
          <p class="banner banner-error" role="alert">Usuario o contraseña inválidos.</p>
        <?php elseif ($err==='csrf'): ?>
          <p class="banner banner-error" role="alert">Token de seguridad inválido. Recarga la página e inténtalo de nuevo.</p>
        <?php endif; ?>
      </header>

      <form method="post"
            class="login-form"
            autocomplete="on"
            action="<?= htmlspecialchars($BASE_PATH, ENT_QUOTES) ?>/admin/login.php">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">

        <div class="field">
          <label for="u">Usuario o Email</label>
          <input id="u" name="username" required autocomplete="username" autofocus>
        </div>

        <div class="form-floating password-group">
          <input id="p" type="password" name="password" class="form-control" required autocomplete="current-password" placeholder="Contraseña">
          <label for="p"><i class="fas fa-lock"></i>Contraseña</label>
          <button type="button" id="toggle-password" class="toggle-password" tabindex="-1" aria-label="Mostrar u ocultar contraseña">
            <i class="fas fa-eye"></i>
          </button>
        </div>
        <button class="btn btn-login" type="submit">Ingresar</button>
      </form>
      <div class="forgot-password">
        <a href="<?= htmlspecialchars($BASE_PATH, ENT_QUOTES) ?>/" class="btn btn-secondary" style="display:inline-block;margin-top:1.5rem;">
          ← Volver al sitio
        </a>
      </div>
    </section>
  </main>

  <!-- Script mínimo con nonce para toggle de contraseña -->
  <script nonce="<?= $nonce ?>">
    document.addEventListener('DOMContentLoaded', function () {
      var btn = document.querySelector('.toggle');
      var inp = document.getElementById('p');
      if (btn && inp) {
        btn.addEventListener('click', function () {
          inp.type = (inp.type === 'password') ? 'text' : 'password';
        });
      }
    });
  </script>
</body>
</html>
</body>
</html>
