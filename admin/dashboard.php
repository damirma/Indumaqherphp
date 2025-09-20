<?php
/**
 * =============================================
 * DASHBOARD PRINCIPAL - INDUMAQHER ADMIN
 * =============================================
 * Layout principal del panel. Las secciones viven en /admin/sections/*
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
$auth->requireAuth();

$user = $auth->getUser();
$currentSection = $_GET['section'] ?? 'overview';

// BASE_PATH dinámico para enlaces (soporta subcarpeta /indumaqherphp)
$scriptDir = str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')); // /indumaqherphp/admin
$BASE_PATH = rtrim(str_replace('\\','/', dirname($scriptDir)), '/');        // /indumaqherphp
if ($BASE_PATH === '' || $BASE_PATH === '.') $BASE_PATH = '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin - Indumaqher</title>
  <meta name="description" content="Panel de administración para gestión de máquinas y consultas de Indumaqher">
  <meta name="robots" content="noindex, nofollow">

  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Dashboard CSS -->
  <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>

  <!-- Loading Screen -->
  <div id="loading-screen" class="loading-screen">
    <div class="loading-content">
      <div class="loading-spinner"><div></div></div>
      <h3>Cargando Dashboard...</h3>
      <p>Bienvenido, <?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></p>
    </div>
  </div>

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="logo">
      <i class="fas fa-industry"></i>
      <span>Indumaqher</span>
    </div>
    <div class="logo-subtitle">Admin Panel</div>

    <nav>
      <ul>
        <li class="<?= $currentSection === 'overview' ? 'active' : '' ?>">
          <a href="<?= htmlspecialchars($BASE_PATH) ?>/admin/dashboard.php?section=overview">
            <i class="fas fa-chart-pie"></i><span>Dashboard</span>
          </a>
        </li>

        <li class="<?= $currentSection === 'machines' ? 'active' : '' ?>">
          <a href="<?= htmlspecialchars($BASE_PATH) ?>/admin/dashboard.php?section=machines">
            <i class="fas fa-cogs"></i><span>Máquinas</span>
            <span class="badge" id="machines-count">0</span>
          </a>
        </li>

        <li class="<?= $currentSection === 'inquiries' ? 'active' : '' ?>">
          <a href="<?= htmlspecialchars($BASE_PATH) ?>/admin/dashboard.php?section=inquiries">
            <i class="fas fa-envelope"></i><span>Consultas</span>
            <span class="badge alert" id="inquiries-badge" style="display:none">0</span>
          </a>
        </li>

        <li class="<?= $currentSection === 'categories' ? 'active' : '' ?>">
          <a href="<?= htmlspecialchars($BASE_PATH) ?>/admin/dashboard.php?section=categories">
            <i class="fas fa-tags"></i><span>Categorías</span>
          </a>
        </li>

        <li class="<?= $currentSection === 'analytics' ? 'active' : '' ?>">
          <a href="<?= htmlspecialchars($BASE_PATH) ?>/admin/dashboard.php?section=analytics">
            <i class="fas fa-chart-line"></i><span>Analytics</span>
          </a>
        </li>

        <li class="<?= $currentSection === 'settings' ? 'active' : '' ?>">
          <a href="<?= htmlspecialchars($BASE_PATH) ?>/admin/dashboard.php?section=settings">
            <i class="fas fa-cog"></i><span>Configuración</span>
          </a>
        </li>
      </ul>

      <ul class="sidebar-bottom">
        <li class="user-info">
          <i class="fas fa-user-circle"></i>
          <div>
            <div class="user-name"><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></div>
            <div class="user-role"><?= htmlspecialchars(ucfirst($user['role'])) ?></div>
          </div>
        </li>
        <li class="logout">
          <a href="<?= htmlspecialchars($BASE_PATH) ?>/admin/logout.php" onclick="return confirm('¿Estás seguro de cerrar sesión?')">
            <i class="fas fa-sign-out-alt"></i><span>Cerrar Sesión</span>
          </a>
        </li>
      </ul>
    </nav>
  </aside>

  <!-- Main -->
  <main class="main-content" id="mainContent">

    <?php if ($currentSection === 'overview'): ?>
      <!-- SECTION: OVERVIEW -->
      <section class="dashboard-section active">
        <header>
          <div class="header-content">
            <h1>Dashboard Principal</h1>
            <p class="header-subtitle">Resumen general del sistema</p>
          </div>
          <div class="header-actions">
            <div class="last-update">Última actualización: <span id="last-update-time">--:--</span></div>
            <button class="refresh-btn" onclick="refreshDashboard()">
              <i class="fas fa-sync-alt"></i> Actualizar
            </button>
          </div>
        </header>

        <!-- Stats -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon machines"><i class="fas fa-cogs"></i></div>
            <div class="stat-content">
              <h3 id="totalMachines">0</h3><p>Total Máquinas</p>
            </div>
            <div class="stat-change positive"><i class="fas fa-arrow-up"></i><span id="machines-change">+0 este mes</span></div>
          </div>

          <div class="stat-card">
            <div class="stat-icon inquiries"><i class="fas fa-envelope"></i></div>
            <div class="stat-content">
              <h3 id="totalInquiries">0</h3><p>Consultas Totales</p>
            </div>
            <div class="stat-change positive"><i class="fas fa-arrow-up"></i><span id="inquiries-change">+0 esta semana</span></div>
          </div>

          <div class="stat-card">
            <div class="stat-icon categories"><i class="fas fa-tags"></i></div>
            <div class="stat-content">
              <h3 id="totalCategories">0</h3><p>Categorías Activas</p>
            </div>
            <div class="stat-change neutral"><i class="fas fa-minus"></i><span>Sin cambios</span></div>
          </div>

          <div class="stat-card">
            <div class="stat-icon views"><i class="fas fa-eye"></i></div>
            <div class="stat-content">
              <h3 id="totalViews">0</h3><p>Vistas Totales</p>
            </div>
            <div class="stat-change positive"><i class="fas fa-arrow-up"></i><span id="views-change">+0% este mes</span></div>
          </div>
        </div>

        <!-- Quick actions -->
        <div class="quick-actions">
          <h2><i class="fas fa-bolt"></i> Acciones Rápidas</h2>
          <div class="actions-grid">
            <button class="action-btn" onclick="showSection('machines')"><i class="fas fa-plus"></i><span>Nueva Máquina</span></button>
            <button class="action-btn" onclick="showSection('inquiries')"><i class="fas fa-envelope-open"></i><span>Ver Consultas</span></button>
            <button class="action-btn" onclick="showSection('categories')"><i class="fas fa-tag"></i><span>Nueva Categoría</span></button>
            <button class="action-btn" onclick="exportData()"><i class="fas fa-download"></i><span>Exportar Datos</span></button>
          </div>
        </div>

        <!-- Recent activity -->
        <div class="recent-activity">
          <h2><i class="fas fa-clock"></i> Actividad Reciente</h2>
          <div id="recentActivityList">
            <div class="loading-state"><i class="fas fa-spinner fa-spin"></i><p>Cargando actividad...</p></div>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($currentSection === 'machines'): ?>
      <!-- SECTION: MACHINES (incluida como módulo) -->
      <?php include __DIR__ . '/sections/dashboard_machines.php'; ?>
    <?php endif; ?>

    <?php if (!in_array($currentSection, ['overview','machines'], true)): ?>
      <!-- Placeholder para futuras secciones -->
      <section class="dashboard-section active">
        <header>
          <div class="header-content">
            <h1><?= htmlspecialchars(ucfirst($currentSection)) ?></h1>
            <p class="header-subtitle">Sección en desarrollo</p>
          </div>
        </header>
        <div class="empty-state">
          <p>Esta sección aún no tiene contenido.</p>
        </div>
      </section>
    <?php endif; ?>

  </main>

  <!-- Bootstrap bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
  // Config global del dashboard
  const DASHBOARD_CONFIG = {
    API_BASE: '<?= htmlspecialchars($BASE_PATH, ENT_QUOTES) ?>/api/',
    ITEMS_PER_PAGE: 12,
    AUTO_REFRESH: 30000
  };

  class DashboardPHP {
    constructor() {
      this.currentSection = '<?= $currentSection ?>';
      this.statsData = null;
      this.init();
    }

    async init() {
      try {
        setTimeout(() => { const ls = document.getElementById('loading-screen'); if (ls) ls.style.display = 'none'; }, 600);
        // Cargar estadísticas sólo en overview (en otras secciones no es necesario)
        if (this.currentSection === 'overview') {
          await this.loadStats();
          this.setupAutoRefresh();
        }
      } catch (e) {
        console.error('Error inicializando dashboard:', e);
      }
    }

    async loadStats() {
      try {
        const res = await fetch(`${DASHBOARD_CONFIG.API_BASE}stats.php`, {credentials:'same-origin'});
        const json = await res.json();
        if (!json.success) return;

        this.statsData = json.data;
        // Counters (si existen en el DOM)
        const setText = (id, text) => { const el = document.getElementById(id); if (el) el.textContent = text; };
        setText('totalMachines', this.statsData.machines?.total ?? 0);
        setText('totalInquiries', this.statsData.inquiries?.total ?? 0);
        setText('totalCategories', this.statsData.categories?.active ?? 0);
        setText('totalViews', this.statsData.views?.total ?? 0);
        setText('machines-change', `+${this.statsData.machines?.this_month ?? 0} este mes`);
        setText('inquiries-change', `+${this.statsData.inquiries?.this_week ?? 0} esta semana`);
        setText('views-change', `+${this.statsData.views?.this_month ?? 0} este mes`);

        const badge = document.getElementById('machines-count');
        if (badge) badge.textContent = this.statsData.machines?.total ?? 0;

        const ib = document.getElementById('inquiries-badge');
        if (ib) {
          const n = this.statsData.inquiries?.new ?? 0;
          ib.style.display = n > 0 ? 'inline' : 'none';
          ib.textContent = n;
        }

        // Última actualización
        const now = new Date();
        const t = now.toLocaleTimeString('es-CO', {hour:'2-digit', minute:'2-digit'});
        const lu = document.getElementById('last-update-time');
        if (lu) lu.textContent = t;

        // Actividad reciente
        const act = document.getElementById('recentActivityList');
        if (act) {
          const ra = this.statsData.recent_activity || [];
          if (!ra.length) { act.innerHTML = '<p class="text-muted">No hay actividad reciente</p>'; }
          else {
            act.innerHTML = ra.map(a => `
              <div class="activity-item">
                <div class="activity-icon ${a.color || ''}"><i class="${a.icon || 'fas fa-info'}"></i></div>
                <div class="activity-content">
                  <h5>${a.title || ''}</h5>
                  <p>${a.description || ''}</p>
                  <small>${a.created_at || ''}</small>
                </div>
              </div>
            `).join('');
          }
        }
      } catch (e) {
        console.error('Error cargando estadísticas:', e);
      }
    }

    setupAutoRefresh() {
      setInterval(() => this.loadStats(), DASHBOARD_CONFIG.AUTO_REFRESH);
    }
  }

  // Helpers globales básicos
  function showSection(section){ window.location.href = `<?= htmlspecialchars($BASE_PATH) ?>/admin/dashboard.php?section=${encodeURIComponent(section)}`; }
  function refreshDashboard(){ if (window.dashboardPHP) window.dashboardPHP.loadStats(); }
  function exportData(){ alert('Función de exportación en desarrollo'); }

  // Init
  document.addEventListener('DOMContentLoaded', () => {
    window.dashboardPHP = new DashboardPHP();
  });
  </script>
</body>
</html>
