<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
$auth->requireAuth();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/services/MachineService.php';

header('Content-Type: application/json; charset=utf-8');

try {
  $db  = Database::getInstance();
  $pdo = $db->getConnection();
  $svc = new MachineService($pdo);

  $q = trim((string)($_GET['q'] ?? ''));
  $status = trim((string)($_GET['status'] ?? ''));
  $page = max(1, (int)($_GET['page'] ?? 1));
  $limit = min(50, max(1, (int)($_GET['limit'] ?? 12)));

  $data = $svc->list(['q'=>$q, 'status'=>$status, 'page'=>$page, 'limit'=>$limit]);

  echo json_encode(['success'=>true, 'data'=>$data], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
}
