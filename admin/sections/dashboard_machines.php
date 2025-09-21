<?php
declare(strict_types=1);

/**
 * Sección: Gestión de Máquinas (Dashboard)
 * Ruta sugerida: /admin/sections/dashboard_machines.php
 */

require_once __DIR__ . '/../../includes/auth.php';
$auth->requireAuth();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/services/MachineService.php';

/* ---------------------- CSRF ---------------------- */
$csrf_val = '';
$csrf_file = __DIR__ . '/../csrf.php';
if (is_file($csrf_file)) {
  require_once $csrf_file;
  if (function_exists('csrf_token')) {
    $csrf_val = (string) csrf_token();
  }
}
if ($csrf_val === '' && session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
if ($csrf_val === '') {
  if (empty($_SESSION['__csrf'])) $_SESSION['__csrf'] = bin2hex(random_bytes(32));
  $csrf_val = $_SESSION['__csrf'];
}

/* ---------------------- DB & Service ---------------------- */
$db  = Database::getInstance();
$pdo = $db->getConnection();
$svc = new MachineService($pdo);

/* ---------------------- Filtros iniciales ---------------------- */
$q      = trim((string)($_GET['q'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));
$page   = max(1, (int)($_GET['page'] ?? 1));

$data  = $svc->list(['q' => $q, 'status' => $status, 'page' => $page, 'limit' => 12]);
$items = $data['items'];
$pag   = $data['pagination'];

$rootPath = $BASE_PATH ?? '';
$rootPath = trim((string)$rootPath);
if ($rootPath === '.' || $rootPath === '/') $rootPath = '';
if ($rootPath !== '') $rootPath = rtrim($rootPath, '/');

if ($rootPath === '') {
  $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
  $rootPath  = rtrim(str_replace('/admin', '', $scriptDir), '/');
  if ($rootPath === '.' || $rootPath === '/') $rootPath = '';
}

$APP_PATH   = $rootPath;                                 // /indumaqherphp  | ''
$ADMIN_PATH = $rootPath !== '' ? $rootPath . '/admin'    // /indumaqherphp/admin
                               : '/admin';               // admin en raíz
$ADMIN_PATH = rtrim($ADMIN_PATH, '/');
$PLACEHOLDER = (($APP_PATH !== '') ? $APP_PATH : '') . '/assets/imagenes/img1.png';
?>
<style>
  .cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
  }
  .machine-card {
    background: #ffffff;
    border-radius: 1rem;
    box-shadow: 0 10px 25px -12px rgba(15, 23, 42, 0.35);
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }
  .machine-card .thumb {
    position: relative;
    width: 100%;
    padding-top: 56.25%;
    border-radius: 0.85rem;
    overflow: hidden;
    background: #f5f7fa;
  }
  .machine-card .thumb img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
  .machine-card .actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
  }
  .machine-card .actions .btn {
    flex: 1 1 140px;
  }
  @media (max-width: 640px) {
    .cards-grid {
      grid-template-columns: 1fr;
    }
    .machine-card {
      padding: 1.25rem;
    }
  }
</style>
<!-- Encabezado -->
<header>
  <div class="header-content">
    <h1>Gestión de Máquinas</h1>
    <p class="header-subtitle">Administra tu catálogo de máquinas industriales</p>
  </div>
  <div class="header-actions">
    <button class="btn btn-primary" id="btn-new-machine">
      <i class="fas fa-plus"></i> Nueva máquina
    </button>
  </div>
</header>

<!-- Filtros -->
<div class="filters-bar">
  <div class="filter-group">
    <label>Buscar</label>
    <input type="text" id="machines-search" placeholder="Nombre de máquina..." value="<?= htmlspecialchars($q, ENT_QUOTES) ?>">
  </div>
  <div class="filter-group">
    <label>Estado</label>
    <select id="machines-status-filter">
      <option value="">Todos los estados</option>
      <option value="published" <?= $status==='published'?'selected':'' ?>>Publicado</option>
      <option value="draft"     <?= $status==='draft'?'selected':'' ?>>Borrador</option>
      <option value="archived"  <?= $status==='archived'?'selected':'' ?>>Archivado</option>
    </select>
  </div>
  <button class="btn btn-secondary" id="machines-clear">
    <i class="fas fa-times"></i> Limpiar
  </button>
</div>

<!-- Grid SSR -->
<div id="machinesGrid">
  <?php if (!$items): ?>
    <div class="empty-state">
      <p>No hay máquinas para mostrar.</p>
      <button class="btn btn-primary" id="btn-empty-new">
        <i class="fas fa-plus"></i> Crear la primera
      </button>
    </div>
  <?php else: ?>
    <div class="cards-grid">
      <?php foreach ($items as $m): ?>
        <article class="machine-card <?= htmlspecialchars($m['status'] ?? '') ?>">
          <?php $mainImg = trim((string)($m['main_image'] ?? '')); ?>
          <div class="thumb">
            <img src="<?= htmlspecialchars($mainImg !== '' ? $mainImg : $PLACEHOLDER) ?>"
                 alt="<?= htmlspecialchars($m['name'] ?? 'Máquina') ?>" loading="lazy">
          </div>
          <div class="body">
            <h3><?= htmlspecialchars($m['name'] ?? '') ?></h3>
            <?php if (!empty($m['model'])): ?>
              <p class="muted">Modelo: <?= htmlspecialchars($m['model']) ?></p>
            <?php endif; ?>
            <p class="desc"><?= htmlspecialchars($m['short_description'] ?? '') ?></p>
          </div>
          <div class="meta">
            <span class="badge <?= ($m['status'] ?? '')==='published'?'success':(($m['status'] ?? '')==='draft'?'warning':'neutral') ?>">
              <?= ($m['status'] ?? '')==='published'?'Publicado':(($m['status'] ?? '')==='draft'?'Borrador':'Archivado') ?>
            </span>
            <?php if (!empty($m['featured'])): ?>
              <span class="badge info">Destacado</span>
            <?php endif; ?>
          </div>
          <div class="actions">
            <button class="btn btn-secondary" data-edit="<?= (int)($m['id'] ?? 0) ?>">
              <i class="fas fa-pen"></i> Editar
            </button>
            <?php if (($m['status'] ?? '') === 'published'): ?>
              <button class="btn btn-light" data-toggle-status data-id="<?= (int)($m['id'] ?? 0) ?>" data-to="draft">
                <i class="fas fa-eye-slash"></i> Pasar a borrador
              </button>
            <?php else: ?>
              <button class="btn btn-light" data-toggle-status data-id="<?= (int)($m['id'] ?? 0) ?>" data-to="published">
                <i class="fas fa-eye"></i> Publicar
              </button>
            <?php endif; ?>
            <button class="btn btn-danger" data-delete="<?= (int)($m['id'] ?? 0) ?>">
              <i class="fas fa-trash"></i> Eliminar
            </button>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Modal CRUD -->
<div id="machine-modal" class="modal" aria-hidden="true" role="dialog" aria-modal="true">
  <div class="modal-content large" role="document">
    <div class="modal-header">
      <h2 id="mm-title"><i class="fas fa-cogs"></i> Nueva máquina</h2>
      <button type="button" id="mm-close" class="modal-close" aria-label="Cerrar">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <form id="machine-form" method="post" enctype="multipart/form-data" action="#">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf_val, ENT_QUOTES) ?>">
      <input type="hidden" name="id" id="mf-id">

      <div class="form-grid two">
        <div class="form-group">
          <label for="mf-name">Nombre *</label>
          <input id="mf-name" name="name" required>
        </div>

        <div class="form-group">
          <label for="mf-model">Modelo</label>
          <input id="mf-model" name="model">
        </div>

        <div class="form-group">
          <label for="mf-slug">Slug</label>
          <input id="mf-slug" name="slug" placeholder="empacadora-vertical-ev-200">
        </div>

        <div class="form-group">
          <label for="mf-status">Estado</label>
          <select id="mf-status" name="status">
            <option value="draft">Borrador</option>
            <option value="published">Publicado</option>
            <option value="archived">Archivado</option>
          </select>
        </div>

        <div class="form-group">
          <label for="mf-sort">Orden</label>
          <input id="mf-sort" name="sort_order" type="number" step="1" value="0">
        </div>

        <div class="form-group">
          <label>&nbsp;</label>
          <label style="display:flex;gap:.5rem;align-items:center;">
            <input type="checkbox" id="mf-featured" name="featured" value="1">
            <span>Destacada</span>
          </label>
        </div>
      </div>

      <div class="form-group">
        <label for="mf-short">Descripción corta</label>
        <input id="mf-short" name="short_description" maxlength="500" placeholder="Resumen en 1-2 líneas">
      </div>

      <div class="form-group">
        <label for="mf-description">Descripción</label>
        <textarea id="mf-description" name="description" rows="6" placeholder="Especificaciones, notas, etc."></textarea>
      </div>

      <div class="form-grid two">
        <div class="form-group">
          <label for="mf-image">Imagen principal (subir)</label>
          <input id="mf-image" name="image" type="file" accept="image/*">
          <small>JPG/PNG/WEBP &le; 5MB</small>
        </div>

        <div class="form-group">
          <label for="mf-image-url">o URL de la imagen</label>
          <input id="mf-image-url" name="main_image" placeholder="https://...">
          <div id="mf-image-preview" style="margin-top:.5rem"></div>
        </div>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" id="mf-cancel">Cancelar</button>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i> Guardar
        </button>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  'use strict';

  const APP    = '<?= htmlspecialchars($APP_PATH, ENT_QUOTES) ?>';   // /indumaqherphp
  const ADMIN  = '<?= htmlspecialchars($ADMIN_PATH, ENT_QUOTES) ?>'; // /indumaqherphp/admin
  const CSRF   = '<?= htmlspecialchars($csrf_val, ENT_QUOTES) ?>';
  const NOIMG  = '<?= htmlspecialchars($PLACEHOLDER, ENT_QUOTES) ?>';

  const grid     = document.getElementById('machinesGrid');
  const s        = document.getElementById('machines-search');
  const st       = document.getElementById('machines-status-filter');
  const clearBtn = document.getElementById('machines-clear');
  const newBtn   = document.getElementById('btn-new-machine');

  const MOD      = document.getElementById('machine-modal');
  const MFORM    = document.getElementById('machine-form');
  const MTitle   = document.getElementById('mm-title');
  const MClose   = document.getElementById('mm-close');
  const MCancel  = document.getElementById('mf-cancel');

  const FId      = document.getElementById('mf-id');
  const FName    = document.getElementById('mf-name');
  const FModel   = document.getElementById('mf-model');
  const FSlug    = document.getElementById('mf-slug');
  const FDesc    = document.getElementById('mf-description');
  const FShort   = document.getElementById('mf-short');
  const FStat    = document.getElementById('mf-status');
  const FFeat    = document.getElementById('mf-featured');
  const FSort    = document.getElementById('mf-sort');
  const FImg     = document.getElementById('mf-image');
  const FImgUrl  = document.getElementById('mf-image-url');
  const FPreview = document.getElementById('mf-image-preview');

  const toast = (() => {
    const styleId = 'machines-toast-style';
    if (!document.getElementById(styleId)) {
      const style = document.createElement('style');
      style.id = styleId;
      style.textContent = `
        .toast-stack{position:fixed;top:1.25rem;right:1.25rem;display:flex;flex-direction:column;gap:.75rem;z-index:12000;pointer-events:none;}
        .toast-item{min-width:220px;max-width:320px;padding:.85rem 1rem;border-radius:.75rem;background:rgba(15,23,42,0.92);color:#fff;box-shadow:0 20px 40px -22px rgba(15,23,42,0.55);opacity:0;transform:translateY(-10px);transition:opacity .25s ease,transform .25s ease;pointer-events:auto;font-size:.95rem;line-height:1.35;}
        .toast-item.show{opacity:1;transform:translateY(0);}
        .toast-item.success{background:#1c7c54;}
        .toast-item.error{background:#c0392b;}
      `;
      document.head.appendChild(style);
    }

    const container = document.createElement('div');
    container.className = 'toast-stack';
    container.setAttribute('aria-live', 'assertive');
    container.setAttribute('aria-atomic', 'true');
    document.body.appendChild(container);

    return (message, type = 'info') => {
      if (!message) return;
      const item = document.createElement('div');
      item.className = `toast-item ${type}`;
      item.textContent = message;
      container.appendChild(item);
      requestAnimationFrame(() => item.classList.add('show'));
      setTimeout(() => {
        item.classList.remove('show');
        setTimeout(() => item.remove(), 260);
      }, 3400);
    };
  })();

  const cleanImage = (value) => {
    if (value === null || value === undefined) return NOIMG;
    const str = String(value).trim();
    return str || NOIMG;
  };

  const setModalVisible = (show) => {
    if (!MOD) return;
    if (show) {
      MOD.classList.add('show');
      MOD.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    } else {
      MOD.classList.remove('show');
      MOD.setAttribute('aria-hidden', 'true');
      document.body.style.removeProperty('overflow');
    }
  };

  const resetForm = () => {
    if (!MFORM) return;
    MFORM.reset();
    if (FId) FId.value = '';
    if (FSort) FSort.value = '0';
    if (FPreview) FPreview.innerHTML = '';
  };

  async function fetchJson(url, options = {}) {
    const opts = { ...options };
    const method = (opts.method || 'GET').toUpperCase();
    let requestUrl = url;

    if (method === 'GET') {
      try {
        const tmp = new URL(url, window.location.origin);
        if (!tmp.searchParams.has('__json')) tmp.searchParams.set('__json', '1');
        requestUrl = tmp.toString();
      } catch (_) {
        requestUrl = url + (url.includes('?') ? '&' : '?') + '__json=1';
      }
    } else {
      if (opts.body instanceof FormData) {
        if (!opts.body.has('__json')) opts.body.append('__json', '1');
      } else if (opts.body && typeof opts.body === 'object') {
        const form = new FormData();
        Object.entries(opts.body).forEach(([key, value]) => {
          if (Array.isArray(value)) {
            value.forEach((v) => form.append(key + '[]', v));
          } else {
            form.append(key, value);
          }
        });
        form.append('__json', '1');
        opts.body = form;
      }
    }

    opts.method = method;
    opts.credentials = opts.credentials || 'same-origin';
    opts.headers = { 'Accept': 'application/json', ...(opts.headers || {}) };

    const response = await fetch(requestUrl, opts);
    const raw = await response.text();

    try {
      const data = raw ? JSON.parse(raw) : {};
      return { response, json: data };
    } catch (err) {
      console.error('Respuesta no JSON', raw);
      throw new Error('El servidor devolvió una respuesta no válida');
    }
  }

  function render(items) {
    if (!grid) return;
    if (!items || !items.length) {
      grid.innerHTML = `
        <div class="empty-state">
          <p>No hay máquinas para mostrar.</p>
          <button class="btn btn-primary" id="btn-empty-new">
            <i class="fas fa-plus"></i> Crear la primera
          </button>
        </div>`;
      return;
    }

    const cards = items.map((m) => {
      const status = (m.status ?? '').toString().trim();
      const id = Number(m.id ?? 0) || 0;
      const name = (m.name ?? '').toString().trim();
      const model = (m.model ?? '').toString().trim();
      const shortDesc = (m.short_description ?? '').toString().trim();
      const img = cleanImage(m.main_image);
      const featured = !!m.featured;

      return `
        <article class="machine-card ${status}">
          <div class="thumb">
            <img src="${img}" alt="${name || 'Máquina'}" loading="lazy">
          </div>
          <div class="body">
            <h3>${name}</h3>
            ${model ? `<p class="muted">Modelo: ${model}</p>` : ''}
            <p class="desc">${shortDesc}</p>
          </div>
          <div class="meta">
            <span class="badge ${status === 'published' ? 'success' : (status === 'draft' ? 'warning' : 'neutral')}" data-status="${status}">
              ${status === 'published' ? 'Publicado' : (status === 'draft' ? 'Borrador' : 'Archivado')}
            </span>
            ${featured ? '<span class="badge info">Destacado</span>' : ''}
          </div>
          <div class="actions">
            <button class="btn btn-secondary" data-edit="${id}">
              <i class="fas fa-pen"></i> Editar
            </button>
            ${status === 'published'
              ? `<button class="btn btn-light" data-toggle-status data-id="${id}" data-to="draft"><i class="fas fa-eye-slash"></i> Pasar a borrador</button>`
              : `<button class="btn btn-light" data-toggle-status data-id="${id}" data-to="published"><i class="fas fa-eye"></i> Publicar</button>`
            }
            <button class="btn btn-danger" data-delete="${id}">
              <i class="fas fa-trash"></i> Eliminar
            </button>
          </div>
        </article>
      `;
    }).join('');

    grid.innerHTML = `<div class="cards-grid">${cards}</div>`;
  }

  async function load() {
    if (!grid) return;
    grid.innerHTML = `
      <div class="loading-state">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Cargando máquinas...</p>
      </div>`;

    const params = new URLSearchParams();
    if (s && s.value.trim()) params.set('q', s.value.trim());
    if (st && st.value) params.set('status', st.value);

    try {
      const { json } = await fetchJson(`${ADMIN}/ajax/machines_list.php?${params.toString()}`);
      if (!json.success) {
        const message = json.error || 'No fue posible cargar las máquinas.';
        toast(message, 'error');
        grid.innerHTML = `<div class="empty-state"><p>${message}</p></div>`;
        return;
      }
      render(json.data.items || json.data.machines || []);
    } catch (e) {
      console.error('Error cargando máquinas', e);
      const message = e.message || 'Error al cargar las máquinas.';
      toast(message, 'error');
      grid.innerHTML = `<div class="empty-state"><p>${message}</p></div>`;
    }
  }

  function openCreate() {
    if (!MFORM) return;
    resetForm();
    MFORM.action = `${ADMIN}/actions/actions_machines.php?action=create`;
    if (MTitle) MTitle.textContent = 'Nueva máquina';
    if (FStat) FStat.value = 'draft';
    setModalVisible(true);
  }

  async function openEdit(id) {
    try {
      const { json } = await fetchJson(`${ADMIN}/ajax/machines_get.php?id=${id}`);
      if (!json.success) {
        toast(json.error || 'No se pudo cargar la máquina', 'error');
        return;
      }

      const m = json.data || {};
      if (MFORM) MFORM.action = `${ADMIN}/actions/actions_machines.php?action=update`;
      if (MTitle) MTitle.textContent = `Editar máquina #${m.id ?? ''}`;
      if (FId) FId.value = m.id ?? '';
      if (FName) FName.value = m.name ?? '';
      if (FModel) FModel.value = m.model ?? '';
      if (FSlug) FSlug.value = m.slug ?? '';
      if (FDesc) FDesc.value = m.description ?? '';
      if (FShort) FShort.value = m.short_description ?? '';
      if (FStat) FStat.value = (m.status ?? 'draft') || 'draft';
      if (FFeat) FFeat.checked = !!m.featured;
      if (FSort) FSort.value = m.sort_order ?? 0;
      const rawImg = (m.main_image ?? '').toString().trim();
      if (FImgUrl) FImgUrl.value = rawImg;
      if (FPreview) {
        FPreview.innerHTML = rawImg ? `<img src="${rawImg}" alt="" style="max-width:180px;border-radius:.5rem;">` : '';
      }
      if (FImg) FImg.value = '';

      setModalVisible(true);
    } catch (e) {
      console.error('Error abriendo máquina', e);
      toast(e.message || 'No se pudo cargar la máquina', 'error');
    }
  }

  async function submitForm(ev) {
    ev.preventDefault();
    if (!MFORM) return;

    const formData = new FormData(MFORM);
    try {
      const { json } = await fetchJson(MFORM.action, { method: 'POST', body: formData });
      if (json.success) {
        toast(json.message || 'Cambios guardados', 'success');
        setModalVisible(false);
        load();
      } else {
        toast(json.error || 'Error al guardar', 'error');
      }
    } catch (e) {
      toast(e.message || 'Error inesperado al guardar', 'error');
    }
  }

  async function doDelete(id) {
    if (!confirm('¿Eliminar esta máquina de forma permanente?')) return;
    const fd = new FormData();
    fd.append('csrf', CSRF);
    fd.append('id', String(id));
    try {
      const { json } = await fetchJson(`${ADMIN}/actions/actions_machines.php?action=delete`, { method: 'POST', body: fd });
      if (json.success) {
        toast('Máquina eliminada', 'success');
        load();
      } else {
        toast(json.error || 'No se pudo eliminar', 'error');
      }
    } catch (e) {
      toast(e.message || 'Error eliminando la máquina', 'error');
    }
  }

  async function doToggleStatus(id, to) {
    const fd = new FormData();
    fd.append('csrf', CSRF);
    fd.append('id', String(id));
    fd.append('to', String(to));
    try {
      const { json } = await fetchJson(`${ADMIN}/actions/actions_machines.php?action=toggle_status`, { method: 'POST', body: fd });
      if (json.success) {
        toast('Estado actualizado', 'success');
        load();
      } else {
        toast(json.error || 'No se pudo cambiar el estado', 'error');
      }
    } catch (e) {
      toast(e.message || 'Error al cambiar estado', 'error');
    }
  }

  function handleGridClick(ev) {
    const editBtn = ev.target.closest('[data-edit]');
    if (editBtn) {
      ev.preventDefault();
      const id = Number(editBtn.getAttribute('data-edit')) || 0;
      if (id > 0) openEdit(id);
      return;
    }

    const deleteBtn = ev.target.closest('[data-delete]');
    if (deleteBtn) {
      ev.preventDefault();
      const id = Number(deleteBtn.getAttribute('data-delete')) || 0;
      if (id > 0) doDelete(id);
      return;
    }

    const toggleBtn = ev.target.closest('[data-toggle-status]');
    if (toggleBtn) {
      ev.preventDefault();
      const id = Number(toggleBtn.getAttribute('data-id')) || 0;
      const to = toggleBtn.getAttribute('data-to') || '';
      if (id > 0 && to) doToggleStatus(id, to);
      return;
    }

    const createBtn = ev.target.closest('#btn-empty-new');
    if (createBtn) {
      ev.preventDefault();
      openCreate();
    }
  }

  function closeOnBackdrop(ev) {
    if (ev.target === MOD) {
      setModalVisible(false);
    }
  }

  function handleKey(ev) {
    if (ev.key === 'Escape' && MOD?.classList.contains('show')) {
      setModalVisible(false);
    }
  }

  function updateImagePreview() {
    if (!FPreview) return;
    const url = (FImgUrl?.value || '').trim();
    FPreview.innerHTML = url ? `<img src="${url}" alt="" style="max-width:180px;border-radius:.5rem;">` : '';
  }

  async function uploadImage(ev) {
    const file = ev.target.files?.[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('csrf', CSRF);
    fd.append('image', file);
    try {
      const { json } = await fetchJson(`${ADMIN}/actions/actions_machines.php?action=upload`, { method: 'POST', body: fd });
      if (json.success && json.url) {
        if (FImgUrl) FImgUrl.value = json.url;
        updateImagePreview();
        toast('Imagen subida correctamente', 'success');
      } else {
        toast(json.error || 'No se pudo subir la imagen', 'error');
      }
    } catch (e) {
      toast(e.message || 'Error al subir la imagen', 'error');
    } finally {
      ev.target.value = '';
    }
  }

  if (grid) grid.addEventListener('click', handleGridClick);
  if (s) {
    let t;
    s.addEventListener('input', () => {
      clearTimeout(t);
      t = setTimeout(load, 350);
    });
  }
  st?.addEventListener('change', load);
  clearBtn?.addEventListener('click', () => {
    if (s) s.value = '';
    if (st) st.value = '';
    load();
  });
  newBtn?.addEventListener('click', (ev) => {
    ev.preventDefault();
    openCreate();
  });
  MCancel?.addEventListener('click', () => setModalVisible(false));
  MClose?.addEventListener('click', () => setModalVisible(false));
  MOD?.addEventListener('click', closeOnBackdrop);
  document.addEventListener('keydown', handleKey);
  MFORM?.addEventListener('submit', submitForm);
  FImg?.addEventListener('change', uploadImage);
  FImgUrl?.addEventListener('input', updateImagePreview);

  updateImagePreview();

  document.getElementById('btn-empty-new')?.addEventListener('click', (ev) => {
    ev.preventDefault();
    openCreate();
  });

  load();
})();
</script>






