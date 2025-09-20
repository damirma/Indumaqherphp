<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
$auth->requireAuth();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/services/MachineService.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) throw new InvalidArgumentException('ID inválido');

    $db  = Database::getInstance();
    $pdo = $db->getConnection();
    $svc = new MachineService($pdo);

    $row = $svc->get($id);
    if (!$row) { http_response_code(404); echo json_encode(['success'=>false,'error'=>'No encontrada']); exit; }

    echo json_encode(['success'=>true,'data'=>$row], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
