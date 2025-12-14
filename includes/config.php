<?php

// 1. ALLOW header.php & footer.php to be included safely
defined('ACCESS_GRANTED') OR define('ACCESS_GRANTED', true);

// 2. Prevent direct access to this file (extra safety)
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access denied.');
}

// 3. AUTO DETECT BASE_URL & DEFINE ROOT_PATH (Moved to top)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? "https://" : "http://";
$host     = $_SERVER['HTTP_HOST'];

$project_root_server_path = realpath(__DIR__ . '/../');
define('ROOT_PATH', $project_root_server_path . '/');

$doc_root = realpath($_SERVER['DOCUMENT_ROOT']);

$uri_path = str_replace($doc_root, '', $project_root_server_path);
$uri_path = str_replace('\\', '/', $uri_path);

define('BASE_URL', $protocol . $host . rtrim($uri_path, '/') . '/');


// === VAPID PUSH NOTIFICATION KEYS SECURE & SILENT ===
$envFile = ROOT_PATH . '.env';
$VAPID_PUBLIC_KEY = '';
$VAPID_PRIVATE_KEY = '';
$VAPID_SUBJECT = 'mailto:admin@edulux.com';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");

            switch ($key) {
                case 'VAPID_PUBLIC_KEY':
                    $VAPID_PUBLIC_KEY = $value;
                    break;
                case 'VAPID_PRIVATE_KEY':
                    $VAPID_PRIVATE_KEY = $value;
                    break;
                case 'VAPID_SUBJECT':
                    $VAPID_SUBJECT = $value;
                    break;
            }
        }
    }
}

define('VAPID_PUBLIC_KEY', $VAPID_PUBLIC_KEY);
define('VAPID_PRIVATE_KEY', $VAPID_PRIVATE_KEY);
define('VAPID_SUBJECT', $VAPID_SUBJECT);

// 5. ENVIRONMENT
$env = getenv('ENV') ?: 'development';


// 6. DATABASE CONFIG
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');      
define('DB_NAME', getenv('DB_NAME') ?: 'edulux');
define('DB_CHARSET', 'utf8mb4');


// 7. ERROR REPORTING
if ($env === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT_PATH . 'logs/error.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// 8. SECURE SESSION SETTINGS
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $protocol === 'https://');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 9. DATABASE CONNECTION
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE              => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES     => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    $pdo->exec("SET time_zone = '+00:00'");
} catch (PDOException $e) {
    if ($env === 'production') {
        error_log('DB Error: ' . $e->getMessage());
        http_response_code(500);
        exit('Service unavailable.');
    } else {
        die('Database Connection Failed: ' . $e->getMessage());
    }
}

// 10. HELPER FUNCTIONS
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);

    if (function_exists('iconv')) {
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    }

    $text = preg_replace('~[^-\w]+~', '', $text);

    $text = trim($text, '-');
    $text = strtolower($text);

    if (empty($text)) {
        return 'n-a';
    }

    return $text;

}


// 12. SECURITY HEADERS (production only)
if ($env === 'production') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

// 13. MAKE PDO GLOBALLY AVAILABLE
$GLOBALS['pdo'] = $pdo;