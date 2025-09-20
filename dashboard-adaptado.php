<?php
require_once '../includes/auth.php';
$auth->requireAuth();
$user = $auth->getUser();
$currentSection = $_GET['section'] ?? 'overview';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Indumaqher</title>
    <meta name="description" content="Panel de administración para gestión de máquinas y consultas de Indumaqher">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div id="loading-screen" class="loading-screen">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <h3>Cargando Dashboard...</h3>
            <p>Bienvenido, <?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></p>
        </div>
    </div>
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <i class="fas fa-industry"></i>
            <span>Indumaqher</span>
            <div class="logo-subtitle">Admin Panel</div>
        </div>
        <nav>
            <ul>
                <li class="<?= $currentSection === 'overview' ? 'active' : '' ?>" onclick="window.location='?section=overview'">
                    <i class="fas fa-chart-pie"></i>
                    <span>Dashboard</span>
                </li>
                <li class="<?= $currentSection === 'machines' ? 'active' : '' ?>" onclick="window.location='?section=machines'">
                    <i class="fas fa-cogs"></i>
                    <span>Máquinas</span>
                    <span class="badge" id="machines-count">0</span>
                </li>
                <li class="<?= $currentSection === 'inquiries' ? 'active' : '' ?>" onclick="window.location='?section=inquiries'">
                    <i class="fas fa-envelope"></i>
                    <span>Consultas</span>
                    <span class="badge alert" id="inquiries-badge" style="display: none;">0</span>
                </li>
                <li class="<?= $currentSection === 'categories' ? 'active' : '' ?>" onclick="window.location='?section=categories'">
                    <i class="fas fa-tags"></i>
                    <span>Categorías</span>
                </li>
                <li class="<?= $currentSection === 'analytics' ? 'active' : '' ?>" onclick="window.location='?section=analytics'">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                </li>
                <li class="<?= $currentSection === 'settings' ? 'active' : '' ?>" onclick="window.location='?section=settings'">
                    <i class="fas fa-cog"></i>
                    <span>Configuración</span>
                </li>
            </ul>
            <ul class="sidebar-bottom">
                <li class="logout" onclick="window.location='logout.php'">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Cerrar Sesión</span>
                </li>
            </ul>
        </nav>
    </div>
    <div class="main-content" id="mainContent">
        <?php include 'dashboard-section-render.php'; ?>
    </div>
    <!-- Modals y scripts originales -->
    <?php include 'dashboard-modals.php'; ?>
    <script src="../assets/js/secure-frontend-auth.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>