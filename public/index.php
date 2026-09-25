<?php

declare(strict_types=1);

use Digit\HostingAdmin\Auth\AuthRepository;
use Digit\HostingAdmin\Auth\LoginAttemptRepository;
use Digit\HostingAdmin\Auth\LoginManager;
use Digit\HostingAdmin\Auth\SessionManager;
use Digit\HostingAdmin\Auth\SessionRepository;

use Digit\HostingAdmin\Controllers\AuthController;
use Digit\HostingAdmin\Controllers\BackupController;
use Digit\HostingAdmin\Controllers\DashboardController;
use Digit\HostingAdmin\Controllers\ManualController;
use Digit\HostingAdmin\Controllers\StudentsController;
use Digit\HostingAdmin\Controllers\SystemController;

use Digit\HostingAdmin\Hosting\MultiHostStatusRepository;

use Digit\HostingAdmin\Support\Config;
use Digit\HostingAdmin\Support\Database;

require dirname(__DIR__)
    . '/app/bootstrap.php';


$config = Config::load(
    '/etc/digit-hosting-admin/app.env'
);

$db = Database::connect(
    $config
);


$authRepository =
    new AuthRepository($db);

$attempts =
    new LoginAttemptRepository($db);

$sessions =
    new SessionRepository($db);


$lifetime = (int)(
    $config['SESSION_IDLE_SECONDS']
    ?? 1800
);

$maxFailures = (int)(
    $config['LOGIN_MAX_FAILURES']
    ?? 5
);

$windowSeconds = (int)(
    $config['LOGIN_WINDOW_SECONDS']
    ?? 900
);

$windowMinutes = max(
    1,
    (int)ceil(
        $windowSeconds / 60
    )
);


$sessionManager =
    new SessionManager(
        storeSession:
            static function (
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

        findSession:
            static fn(
                string $hash
            ): ?array
                => $sessions
                    ->findValidByHash(
                        $hash
                    ),

        deleteSession:
            static function (
                string $hash
            ) use ($sessions): void {
                $sessions->deleteByHash(
                    $hash
                );
            },

        lifetimeSeconds:
            $lifetime
    );


$loginManager =
    new LoginManager(
        findUser:
            static fn(
                string $username
            ): ?array
                => $authRepository
                    ->findByUsername(
                        $username
                    ),

        canAttempt:
            static fn(
                string $ip
            ): bool
                => $attempts
                    ->canAttempt(
                        $ip,
                        $maxFailures,
                        $windowMinutes
                    ),

        recordFailure:
            static function (
                string $ip,
                string $userAgent
            ) use ($attempts): void {
                $attempts->recordFailure(
                    $ip,
                    $userAgent
                );
            },

        createSession:
            static fn(
                int $userId,
                string $ip,
                string $userAgent
            ): string
                => $sessionManager
                    ->create(
                        $userId,
                        $ip,
                        $userAgent
                    )
    );


$authController =
    new AuthController();

$dashboardController =
    new DashboardController();

$studentsController =
    new StudentsController();

$systemController =
    new SystemController();

$backupController =
    new BackupController();

$manualController =
    new ManualController();


$method = strtoupper(
    $_SERVER['REQUEST_METHOD']
    ?? 'GET'
);

$path = parse_url(
    $_SERVER['REQUEST_URI']
    ?? '/',
    PHP_URL_PATH
) ?: '/';


$ip =
    $_SERVER['REMOTE_ADDR']
    ?? '0.0.0.0';

$userAgent = substr(
    $_SERVER['HTTP_USER_AGENT']
    ?? 'Unknown',
    0,
    255
);


$cookieName =
    'digit_hosting_session';

$token =
    $_COOKIE[$cookieName]
    ?? '';

$currentUser =
    $sessionManager->resolve(
        $token
    );


if (
    $method === 'GET'
    && $path === '/'
) {
    header(
        'Location: '
        . (
            $currentUser
            ? '/dashboard'
            : '/login'
        )
    );

    exit;
}


if (
    $method === 'GET'
    && $path === '/login'
) {
    if ($currentUser) {
        header(
            'Location: /dashboard'
        );

        exit;
    }

    echo $authController
        ->loginPage();

    exit;
}


if (
    $method === 'POST'
    && $path === '/login'
) {
    $username = trim(
        (string)(
            $_POST['username']
            ?? ''
        )
    );

    $password = (string)(
        $_POST['password']
        ?? ''
    );

    $result = $loginManager
        ->login(
            username: $username,
            password: $password,
            ipAddress: $ip,
            userAgent: $userAgent
        );

    $password = '';

    if (!$result['success']) {
        http_response_code(
            $result['reason']
                === 'RATE_LIMITED'
                ? 429
                : 401
        );

        $error =
            $result['reason']
                === 'RATE_LIMITED'
                ? 'มีการเข้าสู่ระบบผิดพลาดหลายครั้ง กรุณารอสักครู่'
                : 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';

        echo $authController
            ->loginPage(
                $error
            );

        exit;
    }


    $secure = (
        ($_SERVER['HTTPS'] ?? '')
            === 'on'

        ||

        strtolower(
            $_SERVER[
                'HTTP_X_FORWARDED_PROTO'
            ] ?? ''
        ) === 'https'
    );


    setcookie(
        $cookieName,
        $result['token'],
        [
            'expires'
                => time()
                    + $lifetime,

            'path'
                => '/',

            'secure'
                => $secure,

            'httponly'
                => true,

            'samesite'
                => 'Lax',
        ]
    );


    header(
        'Location: /dashboard',
        true,
        303
    );

    exit;
}


$protectedRoutes = [
    '/dashboard',
    '/students',
    '/system',
    '/backup',
    '/manual',
];


if (
    $method === 'GET'
    && in_array(
        $path,
        $protectedRoutes,
        true
    )
    && !$currentUser
) {
    header(
        'Location: /login'
    );

    exit;
}


if (
    $method === 'GET'
    && $path === '/dashboard'
) {
    try {
        $monitoringRepository = new MultiHostStatusRepository(
            $config['HOST_REGISTRY_FILE']
                ?? '/etc/digit-hosting-admin/hosts.json',
            $config['STATUS_CACHE_DIR']
                ?? '/var/lib/digit-hosting-admin/status'
        );

        $monitoringHosts = $monitoringRepository->all();

    } catch (\RuntimeException $e) {
        http_response_code(503);
        echo 'Monitoring data temporarily unavailable.';
        exit;
    }

    try {
        $monitoringHtml = $dashboardController
            ->monitoringPage(
                $currentUser,
                $monitoringHosts,
                $_GET['host'] ?? null
            );

    } catch (\InvalidArgumentException $e) {
        http_response_code(400);
        echo 'Invalid host selection.';
        exit;
    }

    echo $monitoringHtml;

    exit;
}


if (
    $method === 'GET'
    && $path === '/students'
) {
    try {
        $studentsRepository = new MultiHostStatusRepository(
            $config['HOST_REGISTRY_FILE']
                ?? '/etc/digit-hosting-admin/hosts.json',
            $config['STATUS_CACHE_DIR']
                ?? '/var/lib/digit-hosting-admin/status'
        );

        $studentHosts = $studentsRepository->all();

    } catch (\RuntimeException $e) {
        http_response_code(503);
        echo 'Monitoring data temporarily unavailable.';
        exit;
    }

    try {
        $studentsHtml = $studentsController
            ->monitoringPage(
                $currentUser,
                $studentHosts,
                $_GET['host'] ?? 'all'
            );

    } catch (\InvalidArgumentException $e) {
        http_response_code(400);
        echo 'Invalid host selection.';
        exit;
    }

    echo $studentsHtml;

    exit;
}


if (
    $method === 'GET'
    && $path === '/system'
) {
    try {
        $systemRepository = new MultiHostStatusRepository(
            $config['HOST_REGISTRY_FILE']
                ?? '/etc/digit-hosting-admin/hosts.json',
            $config['STATUS_CACHE_DIR']
                ?? '/var/lib/digit-hosting-admin/status'
        );

        $systemHosts = $systemRepository->all();

    } catch (\RuntimeException $e) {
        http_response_code(503);
        echo 'Monitoring data temporarily unavailable.';
        exit;
    }

    try {
        $systemHtml = $systemController
            ->monitoringPage(
                $currentUser,
                $systemHosts,
                $_GET['host'] ?? null
            );

    } catch (\InvalidArgumentException $e) {
        http_response_code(400);
        echo 'Invalid host selection.';
        exit;
    }

    echo $systemHtml;

    exit;
}


if (
    $method === 'GET'
    && $path === '/backup'
) {
    try {
        $backupRepository = new MultiHostStatusRepository(
            $config['HOST_REGISTRY_FILE']
                ?? '/etc/digit-hosting-admin/hosts.json',
            $config['STATUS_CACHE_DIR']
                ?? '/var/lib/digit-hosting-admin/status'
        );

        $backupHosts = $backupRepository->all();

    } catch (\RuntimeException $e) {
        http_response_code(503);
        echo 'Monitoring data temporarily unavailable.';
        exit;
    }

    try {
        $backupHtml = $backupController
            ->monitoringPage(
                $currentUser,
                $backupHosts,
                $_GET['host'] ?? null
            );

    } catch (\InvalidArgumentException $e) {
        http_response_code(400);
        echo 'Invalid host selection.';
        exit;
    }

    echo $backupHtml;

    exit;
}


if (
    $method === 'GET'
    && $path === '/manual'
) {
    echo $manualController
        ->page(
            $currentUser
        );

    exit;
}


if (
    $method === 'POST'
    && $path === '/logout'
) {
    if ($token !== '') {
        $sessionManager
            ->destroy(
                $token
            );
    }

    setcookie(
        $cookieName,
        '',
        [
            'expires'
                => time() - 3600,

            'path'
                => '/',

            'httponly'
                => true,

            'samesite'
                => 'Lax',
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
