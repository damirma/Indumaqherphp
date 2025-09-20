<?php
/**
 * =============================================
 * API DE MÁQUINAS - DASHBOARD INDUMAQHER
 * =============================================
 * CRUD completo para gestión de máquinas
 */

require_once '../includes/auth.php';

// Verificar autenticación
$auth->requireAuth();

// Configurar headers para JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = Database::getInstance();
    
    switch ($method) {
        case 'GET':
            handleGetRequest($db, $action);
            break;
            
        case 'POST':
            handlePostRequest($db, $action);
            break;
            
        case 'PUT':
            handlePutRequest($db, $action);
            break;
            
        case 'DELETE':
            handleDeleteRequest($db, $action);
            break;
            
        default:
            throw new Exception('Método no permitido');
    }
    
} catch (Exception $e) {
    error_log("Error en API machines: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}

/**
 * Manejar peticiones GET
 */
function handleGetRequest($db, $action) {
    switch ($action) {
        case 'list':
            getMachinesList($db);
            break;
            
        case 'detail':
            getMachineDetail($db);
            break;
            
        case 'categories':
            getCategories($db);
            break;
            
        default:
            getMachinesList($db);
    }
}

/**
 * Obtener lista de máquinas con filtros
 */
function getMachinesList($db) {
    // Parámetros de filtrado y paginación
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $category = $_GET['category'] ?? '';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(50, max(1, (int)($_GET['limit'] ?? 12)));
    $offset = ($page - 1) * $limit;
    
    // Construir query base
    $whereConditions = [];
    $params = [];
    
    // Filtro de búsqueda
    if ($search) {
        $whereConditions[] = "(m.name LIKE ? OR m.description LIKE ? OR m.model LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    }
    
    // Filtro por estado
    if ($status && $status !== 'all') {
        $whereConditions[] = "m.status = ?";
        $params[] = $status;
    }
    
    // Filtro por categoría
    if ($category && $category !== 'all') {
        $whereConditions[] = "m.category_id = ?";
        $params[] = $category;
    }
    
    $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Query principal para obtener máquinas
    $sql = "SELECT m.id, m.name, m.model, m.slug, m.short_description, m.status, 
                   m.featured, m.views, m.inquiries_count, m.main_image, m.created_at,
                   c.name as category_name, c.color as category_color,
                   COALESCE(u.username, 'Sistema') as created_by_username
            FROM machines m
            LEFT JOIN categories c ON m.category_id = c.id
            LEFT JOIN users u ON m.created_by = u.id
            $whereClause
            ORDER BY m.featured DESC, m.created_at DESC
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $machines = $db->fetchAll($sql, $params);
    
    // Formatear datos
    foreach ($machines as &$machine) {
        $machine['created_at'] = date('d/m/Y H:i', strtotime($machine['created_at']));
        $machine['main_image'] = $machine['main_image'] ?: '../assets/images/no-image.jpg';
        $machine['status_text'] = getStatusText($machine['status']);
        $machine['status_class'] = getStatusClass($machine['status']);
    }
    
    // Contar total para paginación
    $countSql = "SELECT COUNT(*) as total FROM machines m 
                 LEFT JOIN categories c ON m.category_id = c.id 
                 $whereClause";
    $countParams = array_slice($params, 0, -2); // Remover limit y offset
    $totalCount = $db->fetch($countSql, $countParams)['total'];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'machines' => $machines,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalCount / $limit),
                'total_items' => (int)$totalCount,
                'items_per_page' => $limit
            ]
        ]
    ]);
}

/**
 * Obtener detalle de una máquina
 */
function getMachineDetail($db) {
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        throw new Exception('ID de máquina requerido');
    }
    
    $sql = "SELECT m.*, c.name as category_name, c.color as category_color,
                   ms.capacity, ms.speed, ms.power, ms.width, ms.height, ms.depth, ms.weight,
                   ms.materials, ms.certifications, ms.additional_specs,
                   mp.base_price, mp.currency, mp.price_range, mp.is_quote_only, mp.price_notes
            FROM machines m
            LEFT JOIN categories c ON m.category_id = c.id
            LEFT JOIN machine_specifications ms ON m.id = ms.machine_id
            LEFT JOIN machine_pricing mp ON m.id = mp.machine_id
            WHERE m.id = ?";
    
    $machine = $db->fetch($sql, [$id]);
    
    if (!$machine) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Máquina no encontrada']);
        return;
    }
    
    // Formatear campos JSON
    $machine['materials'] = json_decode($machine['materials'] ?? '[]', true);
    $machine['certifications'] = json_decode($machine['certifications'] ?? '[]', true);
    $machine['additional_specs'] = json_decode($machine['additional_specs'] ?? '{}', true);
    
    echo json_encode([
        'success' => true,
        'data' => $machine
    ]);
}

/**
 * Obtener categorías activas
 */
function getCategories($db) {
    $sql = "SELECT id, name, slug, color FROM categories WHERE is_active = 1 ORDER BY sort_order, name";
    $categories = $db->fetchAll($sql);
    
    echo json_encode([
        'success' => true,
        'data' => $categories
    ]);
}

/**
 * Manejar peticiones POST (crear)
 */
function handlePostRequest($db, $action) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        $data = $_POST;
    }
    
    // Validar campos requeridos
    $required = ['name', 'description', 'status'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Campo '$field' es requerido");
        }
    }
    
    $db->beginTransaction();
    
    try {
        // Generar slug único
        $slug = generateUniqueSlug($db, $data['name']);
        
        // Insertar máquina
        $sql = "INSERT INTO machines (name, model, slug, description, short_description, 
                                    category_id, main_image, status, featured, meta_title, 
                                    meta_description, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $machineId = $db->insert($sql, [
            $data['name'],
            $data['model'] ?? '',
            $slug,
            $data['description'],
            $data['short_description'] ?? '',
            $data['category_id'] ?: null,
            $data['main_image'] ?? null,
            $data['status'],
            (int)($data['featured'] ?? 0),
            $data['meta_title'] ?? $data['name'],
            $data['meta_description'] ?? '',
            $_SESSION['user_id']
        ]);
        
        // Insertar especificaciones si existen
        if (!empty($data['specifications'])) {
            $specs = $data['specifications'];
            $sql = "INSERT INTO machine_specifications (machine_id, capacity, speed, power, 
                                                      width, height, depth, weight, materials, 
                                                      certifications, additional_specs) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $db->query($sql, [
                $machineId,
                $specs['capacity'] ?? null,
                $specs['speed'] ?? null,
                $specs['power'] ?? null,
                $specs['width'] ?? null,
                $specs['height'] ?? null,
                $specs['depth'] ?? null,
                $specs['weight'] ?? null,
                json_encode($specs['materials'] ?? []),
                json_encode($specs['certifications'] ?? []),
                json_encode($specs['additional_specs'] ?? [])
            ]);
        }
        
        // Insertar precios si existen
        if (!empty($data['pricing'])) {
            $pricing = $data['pricing'];
            $sql = "INSERT INTO machine_pricing (machine_id, base_price, currency, price_range, 
                                               is_quote_only, price_notes) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $db->query($sql, [
                $machineId,
                $pricing['base_price'] ?? null,
                $pricing['currency'] ?? 'USD',
                $pricing['price_range'] ?? null,
                (int)($pricing['is_quote_only'] ?? 1),
                $pricing['price_notes'] ?? null
            ]);
        }
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'data' => ['id' => $machineId, 'slug' => $slug],
            'message' => 'Máquina creada exitosamente'
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Manejar peticiones PUT (actualizar)
 */
function handlePutRequest($db, $action) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? $_GET['id'] ?? 0;
    
    if (!$id) {
        throw new Exception('ID de máquina requerido');
    }
    
    $db->beginTransaction();
    
    try {
        // Actualizar máquina principal
        $sql = "UPDATE machines SET 
                name = ?, model = ?, description = ?, short_description = ?,
                category_id = ?, main_image = ?, status = ?, featured = ?,
                meta_title = ?, meta_description = ?, updated_by = ?
                WHERE id = ?";
        
        $db->query($sql, [
            $data['name'],
            $data['model'] ?? '',
            $data['description'],
            $data['short_description'] ?? '',
            $data['category_id'] ?: null,
            $data['main_image'] ?? null,
            $data['status'],
            (int)($data['featured'] ?? 0),
            $data['meta_title'] ?? $data['name'],
            $data['meta_description'] ?? '',
            $_SESSION['user_id'],
            $id
        ]);
        
        // Actualizar especificaciones
        if (isset($data['specifications'])) {
            $specs = $data['specifications'];
            
            // Verificar si ya existen especificaciones
            $existingSpecs = $db->fetch("SELECT machine_id FROM machine_specifications WHERE machine_id = ?", [$id]);
            
            if ($existingSpecs) {
                // Actualizar
                $sql = "UPDATE machine_specifications SET 
                        capacity = ?, speed = ?, power = ?, width = ?, height = ?, depth = ?, 
                        weight = ?, materials = ?, certifications = ?, additional_specs = ?
                        WHERE machine_id = ?";
            } else {
                // Insertar
                $sql = "INSERT INTO machine_specifications (capacity, speed, power, width, height, depth, 
                                                          weight, materials, certifications, additional_specs, machine_id) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            }
            
            $db->query($sql, [
                $specs['capacity'] ?? null,
                $specs['speed'] ?? null,
                $specs['power'] ?? null,
                $specs['width'] ?? null,
                $specs['height'] ?? null,
                $specs['depth'] ?? null,
                $specs['weight'] ?? null,
                json_encode($specs['materials'] ?? []),
                json_encode($specs['certifications'] ?? []),
                json_encode($specs['additional_specs'] ?? []),
                $id
            ]);
        }
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Máquina actualizada exitosamente'
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Manejar peticiones DELETE
 */
function handleDeleteRequest($db, $action) {
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        throw new Exception('ID de máquina requerido');
    }
    
    // Verificar que la máquina existe
    $machine = $db->fetch("SELECT id, name FROM machines WHERE id = ?", [$id]);
    
    if (!$machine) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Máquina no encontrada']);
        return;
    }
    
    $db->beginTransaction();
    
    try {
        // Las especificaciones y precios se eliminan automáticamente por CASCADE
        $db->query("DELETE FROM machines WHERE id = ?", [$id]);
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "Máquina '{$machine['name']}' eliminada exitosamente"
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Funciones auxiliares
 */

function generateUniqueSlug($db, $name, $id = null) {
    $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    $slug = $baseSlug;
    $counter = 1;
    
    do {
        $sql = "SELECT id FROM machines WHERE slug = ?" . ($id ? " AND id != ?" : "");
        $params = $id ? [$slug, $id] : [$slug];
        $existing = $db->fetch($sql, $params);
        
        if ($existing) {
            $slug = $baseSlug . '-' . $counter++;
        } else {
            break;
        }
    } while (true);
    
    return $slug;
}

function getStatusText($status) {
    $statuses = [
        'draft' => 'Borrador',
        'published' => 'Publicado',
        'archived' => 'Archivado'
    ];
    
    return $statuses[$status] ?? $status;
}

function getStatusClass($status) {
    $classes = [
        'draft' => 'warning',
        'published' => 'success',
        'archived' => 'secondary'
    ];
    
    return $classes[$status] ?? 'primary';
}
?>