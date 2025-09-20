<?php
declare(strict_types=1);

// CARGA TU CONFIG (usa tu clase Database)
$root = dirname(__DIR__);
require $root . '/config/config.php';
$db  = getDB();              // instancia Database
$pdo = $db->getConnection(); // PDO

// ====== EDITA AQUÍ SI QUIERES OTRO USUARIO/CLAVE ======
$username   = 'admin';
$password   = 'admin@2025';   // escribe la clave que quieras
$is_active  = 1;
// =======================================================

$hash = password_hash($password, PASSWORD_BCRYPT);

// Si existe -> actualiza. Si no, crea.
$pdo->prepare("INSERT INTO users (username, `password`, is_active)
               VALUES (?, ?, ?)
               ON DUPLICATE KEY UPDATE `password` = VALUES(`password`), is_active = VALUES(is_active)")
    ->execute([$username, $hash, $is_active]);

echo "<h3>Listo ✅</h3><p>Usuario: <b>{$username}</b><br>Contraseña: <b>{$password}</b></p>";
echo '<p>Ahora ve a <a href="login.php">login.php</a></p>';
