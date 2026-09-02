<?php

declare(strict_types=1);

require dirname(__DIR__) . '/tests/bootstrap.php';

use Digit\HostingAdmin\Support\Config;
use Digit\HostingAdmin\Support\Database;

if (!defined('PASSWORD_ARGON2ID')) {
    fwrite(STDERR, "Argon2id is not available.\n");
    exit(2);
}

$config = Config::load(
    '/etc/digit-hosting-admin/app.env'
);

$db = Database::connect($config);


/* Username */
fwrite(STDOUT, 'Username: ');

$username = trim(
    (string) fgets(STDIN)
);

if (
    preg_match(
        '/^[A-Za-z0-9._-]{3,64}$/',
        $username
    ) !== 1
) {
    fwrite(STDERR, "Invalid username.\n");
    exit(2);
}


/* Display name */
fwrite(STDOUT, 'Display name: ');

$displayName = trim(
    (string) fgets(STDIN)
);

if ($displayName === '') {
    fwrite(STDERR, "Display name is required.\n");
    exit(2);
}


/* Duplicate check */
$check = $db->prepare(
    'SELECT id
     FROM admin_users
     WHERE username = ?
     LIMIT 1'
);

$check->execute([
    $username
]);

if ($check->fetchColumn()) {
    fwrite(STDERR, "Username already exists.\n");
    exit(2);
}


/* Password */
fwrite(STDOUT, 'Password: ');

system('stty -echo');

$password = trim(
    (string) fgets(STDIN)
);

system('stty echo');

fwrite(STDOUT, PHP_EOL);

if (strlen($password) < 14) {
    fwrite(
        STDERR,
        "Password must be at least 14 characters.\n"
    );
    exit(2);
}


/* Hash and save */
$hash = password_hash(
    $password,
    PASSWORD_ARGON2ID
);

$password = '';

$stmt = $db->prepare(
    'INSERT INTO admin_users
     (
        username,
        password_hash,
        display_name
     )
     VALUES (?, ?, ?)'
);

$stmt->execute([
    $username,
    $hash,
    $displayName,
]);

echo "Admin created successfully.\n";
