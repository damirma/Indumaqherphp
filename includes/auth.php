<?php
declare(strict_types=1);

/**
 * auth.php — Sistema de Autenticación Unificado para Indumaqher
 * - BASE_PATH dinámico para cookie y redirecciones
 * - Evita loops de login↔dashboard
 * - No depende de CSS/JS externos
 */

//
// ==== BASE_PATH dinámico (p.ej. "/indumaqherphp") ====
//
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$BASE_PATH = rtrim(str_replace('\\', '/', dirname($scriptDir)), '/');
if ($BASE_PATH === '' || $BASE_PATH === '.') {
    $BASE_PATH = '';
}

//
// ==== CARGA DE CONFIGURACIÓN / BASE DE DATOS ====
//
$rootFs = realpath(dirname(__DIR__));
if ($rootFs === false) {
    $rootFs = dirname(__DIR__);
}
$rootFs = rtrim($rootFs, "\\/");

$loaded = false;
$configCandidates = [];
if ($rootFs !== '') {
    $configCandidates = [
        $rootFs . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php',
        $rootFs . DIRECTORY_SEPARATOR . 'config.php',
        $rootFs . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php',
    ];
}

foreach ($configCandidates as $cfg) {
    if (is_file($cfg)) {
        require_once $cfg;
        $loaded = true;
        break;
    }
}
if (!$loaded) {
    http_response_code(500);
    die('No se encontró archivo de configuración en /config.');
}

//
// ==== SESIÓN: cookie con path fijo al proyecto ====
//
if (session_status() === PHP_SESSION_NONE) {
    $cookiePath = $BASE_PATH !== '' ? $BASE_PATH : '/';
    session_set_cookie_params([
        'lifetime' => 3600,       // 1 hora
        'path'     => $cookiePath,
        'domain'   => '',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_only_cookies', '1');
    session_start();

    // Regenerar ID cada 15 minutos
    if (!isset($_SESSION['_sid_refreshed'])) {
        $_SESSION['_sid_refreshed'] = time();
    } elseif (time() - $_SESSION['_sid_refreshed'] > 900) {
        session_regenerate_id(true);
        $_SESSION['_sid_refreshed'] = time();
    }
}

//
// ==== CLASE Auth ====
//
class Auth {
    private PDO    $pdo;
    private string $basePath;

    public function __construct(PDO $pdo, string $basePath) {
        $this->pdo      = $pdo;
        $this->basePath = $basePath;
    }

    /**
     * Intento de login
     * @return ['success'=>bool,'message'?:string]
     */
    public function login(string $userOrEmail, string $password): array {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, username, email, `password`, role, is_active
                 FROM users
                 WHERE (username = ? OR email = ?) LIMIT 1"
            );
            $stmt->execute([$userOrEmail, $userOrEmail]);
            $u = $stmt->fetch(PDO::FETCH_ASSOC);

            $ok = false;
            if ($u && (int)($u['is_active'] ?? 1) === 1) {
                $hash = (string)$u['password'];
                // Compatibilidad Bcrypt $2b$ → $2y$
                $pwHash = strpos($hash, '$2b$') === 0
                    ? '$2y$' . substr($hash, 4)
                    : $hash;
                $ok = password_verify($password, $pwHash);
            }
            if (!$ok) {
                return ['success' => false, 'message' => 'Credenciales incorrectas'];
            }

            // Regenerar sesión y setear datos
            session_regenerate_id(true);
            $_SESSION['user_id']    = (int)$u['id'];
            $_SESSION['username']   = $u['username'];
            $_SESSION['email']      = $u['email'] ?? '';
            $_SESSION['role']       = $u['role'] ?? 'admin';
            $_SESSION['logged_in']  = true;
            $_SESSION['login_time'] = time();

            return ['success' => true];
        } catch (Throwable $e) {
            error_log('Auth login error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno'];
        }
    }

    /** ¿Usuario logueado? */
    public function isLoggedIn(): bool {
        return !empty($_SESSION['logged_in']) && !empty($_SESSION['user_id']);
    }

    /** Datos básicos del usuario */
    public function getUser(): array {
        return [
            'id'        => (int)($_SESSION['user_id'] ?? 0),
            'username'  => $_SESSION['username'] ?? '',
            'email'     => $_SESSION['email'] ?? '',
            'role'      => $_SESSION['role'] ?? 'admin',
            'full_name' => $_SESSION['username'] ?? '',
        ];
    }

    /**
     * Protege páginas que requieran sesión
     * - Permite login.php sin sesión
     * - Evita loops redirigiendo login↔dashboard
     */
    public function requireAuth(): void {
        $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
        $login   = 'login.php';
        $dashboard = 'dashboard.php';

        // Ya logueado
        if ($this->isLoggedIn()) {
            // Si intenta login.php → enviar dashboard
            if ($script === $login) {
                header('Location: ' . $this->basePath . "/admin/$dashboard");
                exit;
            }
            return;
        }

        // No logueado: solo permitir login.php
        if ($script !== $login) {
            header('Location: ' . $this->basePath . "/admin/$login");
            exit;
        }
    }

    /** Cerrar sesión */
    public function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $p['path'], $p['domain'],
                $p['secure'], $p['httponly']
            );
        }
        session_destroy();
        // Volver al home
        header('Location: ' . $this->basePath . '/');
        exit;
    }
}

//
// ==== INSTANCIA GLOBAL ====
//
try {
    $db  = Database::getInstance();
    $pdo = $db->getConnection();
} catch (Throwable $e) {
    http_response_code(500);
    die('No fue posible conectar a la BD.');
}
$auth = new Auth($pdo, $BASE_PATH);
