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
<div id="machine-modal" class="modal hidden" aria-hidden="true">
  <div class="modal-content">
    <div class="modal-header">
      <h2 id="mm-title"><i class="fas fa-cogs"></i> Nueva máquina</h2>
      <button type="button" id="mm-close" class="modal-close"><i class="fas fa-times"></i></button>
    </div>

    <form id="machine-form" method="post" enctype="multipart/form-data" action="#">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf_val, ENT_QUOTES) ?>">
      <input type="hidden" name="id"   id="mf-id">

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
  const APP    = '<?= htmlspecialchars($APP_PATH, ENT_QUOTES) ?>';   // /indumaqherphp
  const ADMIN  = '<?= htmlspecialchars($ADMIN_PATH, ENT_QUOTES) ?>'; // /indumaqherphp/admin
  const CSRF   = '<?= htmlspecialchars($csrf_val, ENT_QUOTES) ?>';
  const NOIMG  = '<?= htmlspecialchars($PLACEHOLDER, ENT_QUOTES) ?>';

  const grid     = document.getElementById('machinesGrid');
  const s        = document.getElementById('machines-search');
  const st       = document.getElementById('machines-status-filter');
  const clearBtn = document.getElementById('machines-clear');
  const newBtn   = document.getElementById('btn-new-machine');
  const emptyNew = document.getElementById('btn-empty-new');

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

  const toggleModal = (show)=> MOD.classList[show?'remove':'add']('hidden');
  const toast = (msg)=> console.log(msg);
  const cleanImage = (value)=> {
    if (value === null || value === undefined) return NOIMG;
    const str = String(value).trim();
    return str ? str : NOIMG;
  };

  async function fetchJson(url, options = {}){
    const opts = {...options};
    const method = (opts.method || 'GET').toUpperCase();
    let finalUrl = url;

    if (method === 'GET') {
      finalUrl += (finalUrl.includes('?') ? '&' : '?') + '__json=1';
    } else if (opts.body instanceof FormData && !opts.body.has('__json')) {
      opts.body.append('__json', '1');
    }

    opts.credentials = opts.credentials || 'same-origin';
    opts.headers = { 'Accept':'application/json', ...(opts.headers || {}) };

    const res = await fetch(finalUrl, opts);
    const raw = await res.text();

    try {
    // Delegación de eventos: más robusto y evita perder handlers
    scope.addEventListener('click', (ev) => {
      const target = ev.target.closest('[data-edit], [data-delete], [data-toggle-status]');
      if (!target || !scope.contains(target)) return;
      if (target.hasAttribute('data-edit')) {
        const id = Number(target.getAt    scope.querySelectorAll('[data-edit]')?.forEach(btn=>{
      btn.onclick = ()=> openEdit(+btn.getAttribute('data-edit'));
    });
    scope.querySelectorAll('[data-delete]')?.forEach(btn=>{
      btn.onclick = ()=> doDelete(+btn.getAttribute('data-delete'));
    });
    scope.querySelectorAll('[data-toggle-status]')?.forEach(btn=>{
      btn.onclick = ()=> doToggleStatus(+btn.getAttribute('data-id'), btn.getAttribute('data-to'));
    });
;
    });
  }

  function render(items){
    if (!items || !items.length) {
      grid.innerHTML = `
        <div class="empty-state">
          <p>No hay Maquinas para mostrar.</p>
          <button class="btn btn-primary" id="btn-empty-new">
            <i class="fas fa-plus"></i> Crear la primera
          </button>
        </div>`;
      document.getElementById('btn-empty-new')?.addEventListener('click', openCreate);
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
            <img src="${img}" alt="${name || 'Maquina'}" loading="lazy">
          </div>
          <div class="body">
            <h3>${name}</h3>
            ${model ? `<p class="muted">Modelo: ${model}</p>` : ''}
            <p class="desc">${shortDesc}</p>
          </div>
          <div class="meta">
            <span class="badge ${status==='published'?'success':(status==='draft'?'warning':'neutral')}">
              ${status==='published'?'Publicado':(status==='draft'?'Borrador':'Archivado')}
            </span>
            ${featured ? `<span class="badge info">Destacado</span>` : ''}
          </div>
          <div class="actions">
            <button class="btn btn-secondary" data-edit="${id}">
              <i class="fas fa-pen"></i> Editar
            </button>
            ${status==='published'
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
    bindRowActions(grid);
  }
  async function load(){
    grid.innerHTML = `
      <div class="loading-state">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Cargando Maquinas...</p>
      </div>`;
    const params = new URLSearchParams();
    if (s?.value.trim()) params.set('q', s.value.trim());
    if (st?.value)       params.set('status', st.value);

    try {
      const {json} = await fetchJson(`${ADMIN}/ajax/machines_list.php?${params.toString()}`);
      if (!json.success) {
        const message = json.error || 'No fue posible cargar las Maquinas.';
        toast(message);
        grid.innerHTML = `<div class="empty-state"><p>${message}</p></div>`;
        return;
      }
      render(json.data.items || json.data.machines || []);
    } catch (e) {
      console.error('Error cargando Maquinas', e);
      const message = e.message || 'Error al cargar las Maquinas.';
      grid.innerHTML = `<div class="empty-state"><p>${message}</p></div>`;
      toast(message);
    }
  }

  function openCreate(){
    MFORM.action = `${ADMIN}/actions/actions_machines.php?action=create`;
    MTitle.textContent = 'Nueva Maquina';
    FId.value = '';
    FName.value='';
    FModel.value='';
    FSlug.value='';
    FDesc.value='';
    FShort.value='';
    FStat.value='draft';
    FFeat.checked=false;
    FSort.value='0';
    FImg.value='';
    FImgUrl.value='';
    FPreview.innerHTML='';
    toggleModal(true);
  }

  async function openEdit(id){
    try {
      const {json: j} = await fetchJson(`${ADMIN}/ajax/machines_get.php?id=${id}`);
      if (!j.success){ toast(j.error||'Error'); return; }
      const m = j.data || {};
      const rawImg = (m.main_image ?? '').toString().trim();

      MFORM.action   = `${ADMIN}/actions/actions_machines.php?action=update`;
      MTitle.textContent = `Editar Maquina #${m.id||''}`;
      FId.value   = m.id||'';
      FName.value = m.name||'';
      FModel.value= m.model||'';
      FSlug.value = m.slug||'';
      FDesc.value = m.description||'';
      FShort.value= m.short_description||'';
      FStat.value = (m.status ?? 'draft') || 'draft';
      FFeat.checked = !!m.featured;
      FSort.value  = (m.sort_order ?? 0);
      FImgUrl.value= rawImg;
      FPreview.innerHTML = rawImg ? `<img src="${rawImg}" alt="" style="max-width:180px;border-radius:.5rem;">` : '';
      FImg.value = '';
      toggleModal(true);
    } catch (e) {
      console.error('Error abriendo Maquina', e);
      toast(e.message || 'No se pudo cargar la Maquina');
    }
  }
  // Guardado
  MFORM.addEventListener('submit', async (ev)=>{
    ev.preventDefault();
    const fd  = new FormData(MFORM);
    try {
      const {json: j} = await fetchJson(MFORM.action, { method:'POST', body: fd });
      if (j.success){ toggleModal(false); toast(j.message||'Guardado'); load(); }
      else { toast(j.error||'Error al guardar'); }
    } catch (e) {
      toast(e.message || 'Error inesperado');
    }
  });

  // Upload de imagen (opcional)
  FImg?.addEventListener('change', async (ev)=>{
    const file = ev.target.files?.[0]; if(!file) return;
    const fd = new FormData();
    fd.append('csrf', CSRF);
    fd.append('image', file);
    try {
      const {json: j} = await fetchJson(`${ADMIN}/actions/actions_machines.php?action=upload`, { method:'POST', body: fd });
      if (j.success){
        FImgUrl.value = j.url;
        FPreview.innerHTML = `<img src="${j.url}" alt="" style="max-width:180px;border-radius:.5rem;">`;
        toast('Imagen subida');
      } else { toast(j.error || 'Error subiendo imagen'); }
    } catch (e) {
      toast(e.message || 'Error subiendo imagen');
    }
  });

  // Eliminar
  async function doDelete(id){
    if (!confirm('¿Eliminar esta máquina de forma permanente?')) return;
    const fd = new FormData();
    fd.append('csrf', CSRF); fd.append('id', String(id));
    try {
      const {json: j} = await fetchJson(`${ADMIN}/actions/actions_machines.php?action=delete`, { method:'POST', body: fd });
      if (j.success){ toast('Eliminada'); load(); } else { toast(j.error||'Error'); }
    } catch (e) {
      toast(e.message || 'Error eliminando');
    }
  }

  // Toggle estado
  async function doToggleStatus(id, to){
    const fd = new FormData();
    fd.append('csrf', CSRF); fd.append('id', String(id)); fd.append('to', String(to));
    try {
      const {json: j} = await fetchJson(`${ADMIN}/actions/actions_machines.php?action=toggle_status`, { method:'POST', body: fd });
      if (j.success){ toast('Estado actualizado'); load(); } else { toast(j.error||'Error'); }
    } catch (e) {
      toast(e.message || 'Error actualizando estado');
    }
  }

  // Filtros
  let t;
  s?.addEventListener('input', () => { clearTimeout(t); t = setTimeout(load, 300); });
  st?.addEventListener('change', load);
  clearBtn?.addEventListener('click', () => {
    if (s) s.value = '';
    if (st) st.value = '';
    load();
  });
  // Nueva
  newBtn?.addEventListener('click', openCreate);
  emptyNew?.addEventListener('click', openCreate);

  // Modal close
  MCancel?.addEventListener('click', ()=> toggleModal(false));
  MClose?.addEventListener('click',  ()=> toggleModal(false));

  // Enlazar acciones para el HTML SSR inicial
  bindRowActions(grid);
})();
</script>






