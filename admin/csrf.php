<?php
declare(strict_types=1);

function csrf_start(): void {
  if (session_status() !== PHP_SESSION_ACTIVE) {
    // session_set_cookie_params(['httponly'=>true,'samesite'=>'Strict','secure'=>!empty($_SERVER['HTTPS'])]);
    session_start();
  }
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
}

function csrf_token(): string {
  csrf_start();
  return $_SESSION['csrf_token'];
}

function csrf_check(string $token): bool {
  csrf_start();
  return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
