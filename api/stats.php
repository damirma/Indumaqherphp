<?php
/**
 * =============================================
 * API DE ESTADÍSTICAS - DASHBOARD INDUMAQHER
 * =============================================
 * Endpoint para obtener métricas del dashboard
 */

require_once '../includes/auth.php';

// Verificar autenticación
$auth->requireAuth();

// Configurar headers para JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    $db = Database::getInstance();
    $stats = [];
    
    // 1. Total de máquinas
    $sql = "SELECT COUNT(*) as total, 
                   SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
                   SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                   SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as this_month
            FROM machines";
    $machinesData = $db->fetch($sql);
    
    $stats['machines'] = [
        'total' => (int)$machinesData['total'],
        'published' => (int)$machinesData['published'],
        'draft' => (int)$machinesData['draft'],
        'this_month' => (int)$machinesData['this_month']
    ];
    
    // 2. Total de consultas
    $sql = "SELECT COUNT(*) as total,
                   SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_inquiries,
                   SUM(CASE WHEN status = 'contacted' THEN 1 ELSE 0 END) as contacted,
                   SUM(CASE WHEN status = 'quoted' THEN 1 ELSE 0 END) as quoted,
                   SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as this_week
            FROM inquiries";
    $inquiriesData = $db->fetch($sql);
    
    $stats['inquiries'] = [
        'total' => (int)$inquiriesData['total'],
        'new' => (int)$inquiriesData['new_inquiries'],
        'contacted' => (int)$inquiriesData['contacted'],
        'quoted' => (int)$inquiriesData['quoted'],
        'this_week' => (int)$inquiriesData['this_week']
    ];
    
    // 3. Total de categorías
    $sql = "SELECT COUNT(*) as total,
                   SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active
            FROM categories";
    $categoriesData = $db->fetch($sql);
    
    $stats['categories'] = [
        'total' => (int)$categoriesData['total'],
        'active' => (int)$categoriesData['active']
    ];
    
    // 4. Total de vistas
    $sql = "SELECT COALESCE(SUM(views), 0) as total_views,
                   COALESCE(SUM(CASE WHEN updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN views ELSE 0 END), 0) as this_month_views
            FROM machines";
    $viewsData = $db->fetch($sql);
    
    $stats['views'] = [
        'total' => (int)$viewsData['total_views'],
        'this_month' => (int)$viewsData['this_month_views']
    ];
    
    // 5. Actividad reciente
    $sql = "SELECT 'machine' as type, name as title, 'Nueva máquina agregada' as description, 
                   created_at, 'fas fa-cogs' as icon, 'success' as color
            FROM machines 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            
            UNION ALL
            
            SELECT 'inquiry' as type, CONCAT(customer_name, ' - ', customer_company) as title, 
                   'Nueva consulta recibida' as description, created_at, 'fas fa-envelope' as icon, 'info' as color
            FROM inquiries 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            
            UNION ALL
            
            SELECT 'category' as type, name as title, 'Categoría creada' as description, 
                   created_at, 'fas fa-tags' as icon, 'warning' as color
            FROM categories 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            
            ORDER BY created_at DESC
            LIMIT 10";
    
    $activities = $db->fetchAll($sql);
    
    // Formatear fechas de actividades
    foreach ($activities as &$activity) {
        $activity['created_at'] = date('d/m/Y H:i', strtotime($activity['created_at']));
        $activity['time_ago'] = timeAgo(strtotime($activity['created_at']));
    }
    
    $stats['recent_activity'] = $activities;
    
    // 6. Consultas por mes (últimos 6 meses)
    $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
                   DATE_FORMAT(created_at, '%M %Y') as month_name,
                   COUNT(*) as count
            FROM inquiries 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC";
    
    $monthlyInquiries = $db->fetchAll($sql);
    
    $stats['monthly_inquiries'] = $monthlyInquiries;
    
    // 7. Máquinas más vistas
    $sql = "SELECT m.name, m.views, c.name as category_name, m.slug
            FROM machines m
            LEFT JOIN categories c ON m.category_id = c.id
            WHERE m.status = 'published' AND m.views > 0
            ORDER BY m.views DESC
            LIMIT 5";
    
    $popularMachines = $db->fetchAll($sql);
    
    $stats['popular_machines'] = $popularMachines;
    
    // 8. Resumen por estado de consultas
    $sql = "SELECT status, COUNT(*) as count
            FROM inquiries
            GROUP BY status
            ORDER BY count DESC";
    
    $inquiriesByStatus = $db->fetchAll($sql);
    
    $stats['inquiries_by_status'] = $inquiriesByStatus;
    
    // 9. Últimas máquinas agregadas
    $sql = "SELECT m.id, m.name, m.slug, m.status, m.created_at, c.name as category_name
            FROM machines m
            LEFT JOIN categories c ON m.category_id = c.id
            ORDER BY m.created_at DESC
            LIMIT 5";
    
    $latestMachines = $db->fetchAll($sql);
    
    // Formatear fechas
    foreach ($latestMachines as &$machine) {
        $machine['created_at'] = date('d/m/Y', strtotime($machine['created_at']));
    }
    
    $stats['latest_machines'] = $latestMachines;
    
    // 10. Tiempo de respuesta promedio a consultas
    $sql = "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_response_hours
            FROM inquiries 
            WHERE status != 'new' AND updated_at > created_at
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    
    $responseTimeData = $db->fetch($sql);
    $stats['avg_response_time'] = round($responseTimeData['avg_response_hours'] ?? 0, 1);
    
    // Última actualización
    $stats['last_updated'] = date('d/m/Y H:i:s');
    
    // Devolver respuesta exitosa
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
    
} catch (Exception $e) {
    error_log("Error en API stats: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}

/**
 * Función para calcular tiempo transcurrido
 */
function timeAgo($datetime) {
    $time = time() - $datetime;
    $time = ($time < 1) ? 1 : $time;
    
    $tokens = [
        31536000 => 'año',
        2592000 => 'mes',
        604800 => 'semana',
        86400 => 'día',
        3600 => 'hora',
        60 => 'minuto',
        1 => 'segundo'
    ];
    
    foreach ($tokens as $unit => $text) {
        if ($time < $unit) continue;
        $numberOfUnits = floor($time / $unit);
        return $numberOfUnits . ' ' . $text . (($numberOfUnits > 1) ? 's' : '') . ' atrás';
    }
}
?>