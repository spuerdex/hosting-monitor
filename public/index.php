<?php

declare(strict_types=1);

use Digit\HostingAdmin\Auth\AuthRepository;
use Digit\HostingAdmin\Auth\LoginAttemptRepository;
use Digit\HostingAdmin\Auth\LoginManager;
use Digit\HostingAdmin\Auth\SessionManager;
use Digit\HostingAdmin\Auth\SessionRepository;
use Digit\HostingAdmin\Controllers\AuthController;
use Digit\HostingAdmin\Controllers\DashboardController;
use Digit\HostingAdmin\Support\Config;
use Digit\HostingAdmin\Support\Database;

require dirname(__DIR__) . '/app/bootstrap.php';

$config = Config::load(
    '/etc/digit-hosting-admin/app.env'
);

$db = Database::connect($config);

$authRepository = new AuthRepository($db);
$attempts = new LoginAttemptRepository($db);
$sessions = new SessionRepository($db);

$lifetime = (int)(
    $config['SESSION_IDLE_SECONDS'] ?? 1800
);

$maxFailures = (int)(
    $config['LOGIN_MAX_FAILURES'] ?? 5
);

$windowSeconds = (int)(
    $config['LOGIN_WINDOW_SECONDS'] ?? 900
);

$windowMinutes = max(
    1,
    (int)ceil($windowSeconds / 60)
);

$sessionManager = new SessionManager(
    storeSession: static function (
        int $userId,
        string $hash,
        string $ip,
        string $userAgent,
        DateTimeImmutable $expiresAt
    ) use ($sessions): void {
        $sessions->create(
            $userId,
            $hash,
            $ip,
            $userAgent,
            $expiresAt
        );
    },

    findSession: static fn(
        string $hash
    ): ?array => $sessions->findValidByHash($hash),

    deleteSession: static function (
        string $hash
    ) use ($sessions): void {
        $sessions->deleteByHash($hash);
    },

    lifetimeSeconds: $lifetime
);

$loginManager = new LoginManager(
    findUser: static fn(
        string $username
    ): ?array => $authRepository->findByUsername($username),

    canAttempt: static fn(
        string $ip
    ): bool => $attempts->canAttempt(
        $ip,
        $maxFailures,
        $windowMinutes
    ),

    recordFailure: static function (
        string $ip,
        string $userAgent
    ) use ($attempts): void {
        $attempts->recordFailure(
            $ip,
            $userAgent
        );
    },

    createSession: static fn(
        int $userId,
        string $ip,
        string $userAgent
    ): string => $sessionManager->create(
        $userId,
        $ip,
        $userAgent
    )
);

$authController = new AuthController();
$dashboardController = new DashboardController();

$method = strtoupper(
    $_SERVER['REQUEST_METHOD'] ?? 'GET'
);

$path = parse_url(
    $_SERVER['REQUEST_URI'] ?? '/',
    PHP_URL_PATH
) ?: '/';

$ip = $_SERVER['REMOTE_ADDR']
    ?? '0.0.0.0';

$userAgent = substr(
    $_SERVER['HTTP_USER_AGENT']
        ?? 'Unknown',
    0,
    255
);

$cookieName = 'digit_hosting_session';

$token = $_COOKIE[$cookieName]
    ?? '';

$currentUser = $sessionManager->resolve(
    $token
);


/* Root */

if ($method === 'GET' && $path === '/') {
    header(
        'Location: '
        . ($currentUser
            ? '/dashboard'
            : '/login')
    );

    exit;
}


/* Login page */

if (
    $method === 'GET'
    && $path === '/login'
) {
    if ($currentUser) {
        header('Location: /dashboard');
        exit;
    }

    echo $authController->loginPage();
    exit;
}


/* Login submit */

if (
    $method === 'POST'
    && $path === '/login'
) {
    $username = trim(
        (string)($_POST['username'] ?? '')
    );

    $password = (string)(
        $_POST['password'] ?? ''
    );

    $result = $loginManager->login(
        username: $username,
        password: $password,
        ipAddress: $ip,
        userAgent: $userAgent
    );

    $password = '';

    if (!$result['success']) {
        http_response_code(
            $result['reason'] === 'RATE_LIMITED'
                ? 429
                : 401
        );

        $error = $result['reason']
            === 'RATE_LIMITED'
            ? 'มีการเข้าสู่ระบบผิดพลาดหลายครั้ง กรุณารอสักครู่'
            : 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';

        echo $authController->loginPage(
            $error
        );

        exit;
    }

    $secure = (
        ($_SERVER['HTTPS'] ?? '') === 'on'
        || strtolower(
            $_SERVER['HTTP_X_FORWARDED_PROTO']
                ?? ''
        ) === 'https'
    );

    setcookie(
        $cookieName,
        $result['token'],
        [
            'expires' => time() + $lifetime,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );

    header(
        'Location: /dashboard',
        true,
        303
    );

    exit;
}


/* Dashboard */

if (
    $method === 'GET'
    && $path === '/dashboard'
) {
    if (!$currentUser) {
        header('Location: /login');
        exit;
    }

    $statusFile = $config['STATUS_FILE']
        ?? '/var/lib/digit-hosting-admin/status/status.json';

    $status = [];

    if (is_file($statusFile)) {
        $status = json_decode(
            file_get_contents($statusFile),
            true
        ) ?: [];
    }

    echo $dashboardController->page(
        $currentUser,
        $status
    );

    exit;
}


/* Logout */

if (
    $method === 'POST'
    && $path === '/logout'
) {
    if ($token !== '') {
        $sessionManager->destroy(
            $token
        );
    }

    setcookie(
        $cookieName,
        '',
        [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );

    header(
        'Location: /login',
        true,
        303
    );

    exit;
}


http_response_code(404);

echo '404 Not Found';
