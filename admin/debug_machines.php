<?php
declare(strict_types=1);

// Simple diagnostics page for Machines edit issues
require_once __DIR__ . '/../includes/auth.php';
$auth->requireAuth();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/services/MachineService.php';

// Paths (same logic as dashboard)
$scriptDir = str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')); // /indumaqherphp/admin
$BASE_PATH = rtrim(str_replace('\\','/', dirname($scriptDir)), '/');        // /indumaqherphp
if ($BASE_PATH === '' || $BASE_PATH === '.') $BASE_PATH = '';
$ADMIN_PATH = $BASE_PATH !== '' ? $BASE_PATH . '/admin' : '/admin';

// DB + sample data
$db  = Database::getInstance();
$pdo = $db->getConnection();
$svc = new MachineService($pdo);
$list = $svc->list(['page'=>1,'limit'=>10]);
$items = $list['items'] ?? [];

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <title>Debug Máquinas</title>
  <meta name="robots" content="noindex,nofollow" />
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin: 2rem; }
    code { background:#f4f6f8; padding:.15rem .35rem; border-radius:.35rem }
    table { border-collapse: collapse; width: 100%; margin-top: 1rem; }
    th, td { border: 1px solid #e5e7eb; padding: .5rem .6rem; text-align: left; }
    th { background:#f8fafc; }
    .muted { color:#6b7280 }
    .ok { color:#15803d }
    .bad { color:#b91c1c }
    .row-actions { display:flex; gap:.5rem }
    .btn { padding:.35rem .6rem; border:1px solid #cbd5e1; background:#fff; border-radius:.4rem; cursor:pointer }
    .btn:hover { background:#f1f5f9 }
    pre { background:#0b1020; color:#e2e8f0; padding:1rem; border-radius:.5rem; max-height: 45vh; overflow:auto }
  </style>
</head>
<body>
  <h1>Diagnóstico de Máquinas</h1>

  <p>
    <strong>ADMIN_PATH:</strong> <code><?= htmlspecialchars($ADMIN_PATH) ?></code> ·
    <strong>BASE_PATH:</strong> <code><?= htmlspecialchars($BASE_PATH) ?></code> ·
    <strong>SCRIPT_NAME:</strong> <code><?= htmlspecialchars($_SERVER['SCRIPT_NAME'] ?? '') ?></code>
  </p>

  <details open>
    <summary><strong>Pruebas rápidas</strong></summary>
    <div style="margin:.5rem 0 1rem">
      <label>Probar ID:</label>
      <input id="testId" type="number" min="1" style="width:7rem" />
      <button class="btn" id="btnTest">Probar fetch</button>
      <span class="muted">GET <code><?= htmlspecialchars($ADMIN_PATH) ?>/ajax/machines_get.php?id=ID</code></span>
    </div>
  </details>

  <h2>Máquinas (muestra)</h2>
  <?php if (!$items): ?>
    <p class="muted">No hay máquinas para listar.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Estado</th>
          <th>Slug</th>
          <th>Probar</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $m): ?>
          <tr>
            <td><?= (int)($m['id'] ?? 0) ?></td>
            <td><?= htmlspecialchars($m['name'] ?? '') ?></td>
            <td><?= htmlspecialchars($m['status'] ?? '') ?></td>
            <td class="muted"><?= htmlspecialchars($m['slug'] ?? '') ?></td>
            <td>
              <div class="row-actions">
                <button class="btn" data-probe="<?= (int)($m['id'] ?? 0) ?>">Probar editar</button>
                <a class="btn" href="<?= htmlspecialchars($ADMIN_PATH) ?>/ajax/machines_get.php?id=<?= (int)($m['id'] ?? 0) ?>" target="_blank">Abrir JSON</a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <h2>Log</h2>
  <pre id="log">(esperando…)</pre>

  <script>
  const ADMIN = '<?= htmlspecialchars($ADMIN_PATH, ENT_QUOTES) ?>';
  const logEl = document.getElementById('log');
  const out = (msg)=> {
    const t = (new Date()).toLocaleTimeString();
    logEl.textContent = `[${t}] ${msg}\n` + (logEl.textContent || '');
  };

  async function probe(id){
    const url = `${ADMIN}/ajax/machines_get.php?id=${id}`;
    out(`GET ${url}`);
    try {
      const res = await fetch(url, { credentials: 'same-origin', headers: {'Accept':'application/json'} });
      const text = await res.text();
      out(`Status: ${res.status} ${res.ok?'OK':'ERROR'}`);
      try {
        const j = JSON.parse(text);
        out(`JSON: ${JSON.stringify(j).slice(0,300)}${text.length>300?'…':''}`);
      } catch(err) {
        out(`No-JSON. Preview: ${text.slice(0,180).replace(/\n/g,' ')}`);
      }
    } catch(e){
      out('Fetch error: ' + e.message);
    }
  }

  document.getElementById('btnTest')?.addEventListener('click', ()=>{
    const id = Number(document.getElementById('testId')?.value || 0) || 0;
    if (id > 0) probe(id); else out('Ingrese un ID válido');
  });

  document.querySelectorAll('[data-probe]')?.forEach(btn => {
    btn.addEventListener('click', ()=> probe(btn.getAttribute('data-probe')));
  });
  </script>
</body>
</html>

