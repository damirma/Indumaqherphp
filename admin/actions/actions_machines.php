<?php
declare(strict_types=1);

/**
 * Endpoint único para acciones sobre Máquinas.
 * Soporta: create, update, delete, upload, toggle_status
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/services/MachineService.php';
// CSRF helper (si existe)
$__csrf_file = __DIR__ . '/../csrf.php';
if (is_file($__csrf_file)) {
  require_once $__csrf_file;
}

$auth->requireAuth();

// ===== Helpers =====
function jsonResponse(array $data, int $code = 200): never {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function redirectBack(string $to, array $q = []): never {
  if (!empty($q)) $to .= (str_contains($to, '?') ? '&' : '?') . http_build_query($q);
  header('Location: ' . $to);
  exit;
}

function wantJson(): bool {
  return (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
         || (isset($_POST['__json']) || isset($_GET['__json']));
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (!in_array($action, ['create','update','delete','upload','toggle_status'], true)) {
  wantJson()
    ? jsonResponse(['success'=>false, 'error'=>'Acción no soportada'], 400)
    : redirectBack('../dashboard.php?section=machines', ['err'=>'action']);
}

// CSRF para toda acción mutante (POST solamente)
if ($method === 'POST') {
  $token = (string)($_POST['csrf'] ?? '');
  $ok = true;
  if (function_exists('csrf_check')) {
    $ok = csrf_check($token);
  } else {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $sessionToken = $_SESSION['csrf_token'] ?? $_SESSION['__csrf'] ?? '';
    $ok = ($token !== '') && ($sessionToken !== '') && hash_equals($sessionToken, $token);
  }
  if (!$ok) {
    wantJson()
      ? jsonResponse(['success'=>false, 'error'=>'CSRF inválido'], 403)
      : redirectBack('../dashboard.php?section=machines', ['err'=>'csrf']);
  }
}

$service = new MachineService($pdo);
$user    = $auth->getUser();
$userId  = (int)($user['id'] ?? 0);

// ====== ACTIONS ======
try {
  switch ($action) {

    // CREATE ---------------------------------------------------------------
    case 'create': {
      if ($method !== 'POST') { jsonResponse(['success'=>false, 'error'=>'Method not allowed'], 405); }

      $data = [
        'name'              => trim((string)($_POST['name'] ?? '')),
        'model'             => trim((string)($_POST['model'] ?? '')),
        'slug'              => trim((string)($_POST['slug'] ?? '')),
        'description'       => (string)($_POST['description'] ?? ''),
        'short_description' => (string)($_POST['short_description'] ?? ''),
        'status'            => (string)($_POST['status'] ?? 'draft'),
        'category_id'       => ($_POST['category_id'] ?? '') !== '' ? (int)$_POST['category_id'] : null,
        'featured'          => isset($_POST['featured']) ? 1 : 0,
        'sort_order'        => (int)($_POST['sort_order'] ?? 0),
        'main_image'        => (string)($_POST['main_image'] ?? ''),
      ];

      // Imagen opcional
      if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $data['main_image'] = (function (): string {
          $max = 2*1024*1024;
          if ($_FILES['image']['size'] > $max) throw new RuntimeException('Imagen > 2MB');

          $finfo = finfo_open(FILEINFO_MIME_TYPE);
          $mime  = finfo_file($finfo, $_FILES['image']['tmp_name']);
          finfo_close($finfo);
          if (!in_array($mime, ['image/jpeg','image/png','image/webp'], true)) {
            throw new RuntimeException('Formato no permitido');
          }

          $dir = __DIR__ . '/../../public/uploads/';
          if (!is_dir($dir)) mkdir($dir, 0775, true);
          $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
          $name = 'machine_'.date('Ymd_His').'_' . bin2hex(random_bytes(3)) . '.' . $ext;
          if (!move_uploaded_file($_FILES['image']['tmp_name'], $dir.$name)) {
            throw new RuntimeException('No se pudo mover la imagen');
          }
          return '/uploads/'.$name;
        })();
      }

      $newId = $service->create($data, $userId);

      wantJson()
        ? jsonResponse(['success'=>true, 'id'=>$newId])
        : redirectBack('../dashboard.php?section=machines', ['created'=>1]);
    } break;

    // UPDATE ---------------------------------------------------------------
    case 'update': {
      if ($method !== 'POST') { jsonResponse(['success'=>false, 'error'=>'Method not allowed'], 405); }
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) { jsonResponse(['success'=>false, 'error'=>'ID inválido'], 400); }

      $data = [
        'name'              => trim((string)($_POST['name'] ?? '')),
        'model'             => trim((string)($_POST['model'] ?? '')),
        'slug'              => trim((string)($_POST['slug'] ?? '')),
        'description'       => (string)($_POST['description'] ?? ''),
        'short_description' => (string)($_POST['short_description'] ?? ''),
        'status'            => (string)($_POST['status'] ?? 'draft'),
        'category_id'       => ($_POST['category_id'] ?? '') !== '' ? (int)$_POST['category_id'] : null,
        'featured'          => isset($_POST['featured']) ? 1 : 0,
        'sort_order'        => (int)($_POST['sort_order'] ?? 0),
        'main_image'        => (string)($_POST['main_image'] ?? ''),
      ];

      if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // reutilizamos la lógica de subida
        $dir = __DIR__ . '/../../public/uploads/';
        if (!is_dir($dir)) mkdir($dir, 0775, true);

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $_FILES['image']['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, ['image/jpeg','image/png','image/webp'], true)) {
          jsonResponse(['success'=>false,'error'=>'Formato no permitido'], 400);
        }
        if ($_FILES['image']['size'] > 2*1024*1024) {
          jsonResponse(['success'=>false,'error'=>'Imagen > 2MB'], 400);
        }

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $name = 'machine_'.date('Ymd_His').'_' . bin2hex(random_bytes(3)) . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], $dir.$name);
        $data['main_image'] = '/uploads/'.$name;
      }

      $service->update($id, $data, $userId);

      wantJson()
        ? jsonResponse(['success'=>true, 'id'=>$id])
        : redirectBack('../dashboard.php?section=machines', ['saved'=>1]);
    } break;

    // DELETE ---------------------------------------------------------------
    case 'delete': {
      if ($method !== 'POST') { jsonResponse(['success'=>false, 'error'=>'Method not allowed'], 405); }
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) { jsonResponse(['success'=>false, 'error'=>'ID inválido'], 400); }

      $service->delete($id);

      wantJson()
        ? jsonResponse(['success'=>true])
        : redirectBack('../dashboard.php?section=machines', ['deleted'=>1]);
    } break;

    // UPLOAD SOLO IMAGEN ---------------------------------------------------
    case 'upload': {
      if ($method !== 'POST') { jsonResponse(['success'=>false, 'error'=>'Method not allowed'], 405); }
      // Aceptar tanto 'image' (formulario) como 'file' (compatibilidad)
      $fileKey = !empty($_FILES['image']) ? 'image' : 'file';
      if (empty($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['success'=>false, 'error'=>'Archivo inválido'], 400);
      }

      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mime  = finfo_file($finfo, $_FILES[$fileKey]['tmp_name']);
      finfo_close($finfo);

      if (!in_array($mime, ['image/jpeg','image/png','image/webp'], true)) {
        jsonResponse(['success'=>false,'error'=>'Formato no permitido'], 400);
      }
      if ($_FILES[$fileKey]['size'] > 2*1024*1024) {
        jsonResponse(['success'=>false,'error'=>'Imagen > 2MB'], 400);
      }

      $dir = __DIR__ . '/../../public/uploads/';
      if (!is_dir($dir)) mkdir($dir, 0775, true);
      $ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
      $name = 'machine_'.date('Ymd_His').'_' . bin2hex(random_bytes(3)) . '.' . $ext;

      move_uploaded_file($_FILES[$fileKey]['tmp_name'], $dir.$name);
      jsonResponse(['success'=>true, 'url'=>'/uploads/'.$name]);
    } break;

    // TOGGLE STATUS --------------------------------------------------------
    case 'toggle_status': {
      if ($method !== 'POST') { jsonResponse(['success'=>false, 'error'=>'Method not allowed'], 405); }
      $id = (int)($_POST['id'] ?? 0);
      $to = (string)($_POST['to'] ?? 'published'); // published|draft|archived
      if ($id <= 0 || !in_array($to, ['published','draft','archived'], true)) {
        jsonResponse(['success'=>false, 'error'=>'Parámetros inválidos'], 400);
      }
      $service->toggleStatus($id, $to);
      wantJson()
        ? jsonResponse(['success'=>true, 'id'=>$id, 'status'=>$to])
        : redirectBack('../dashboard.php?section=machines', ['status'=>$to,'id'=>$id]);
    } break;

  }

} catch (Throwable $e) {
  if (wantJson()) jsonResponse(['success'=>false, 'error'=>$e->getMessage()], 500);
  redirectBack('../dashboard.php?section=machines', ['err'=>'server']);
}

?>
