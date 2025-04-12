<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'devawakening1');  // Ensure this matches your actual DB name
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Error Reporting (for development)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}



// Timezone Configuration
date_default_timezone_set('UTC');

// Establish Database Connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("We're experiencing technical difficulties. Please try again later.");
}

// Application Constants
define('SITE_NAME', 'DevAwakening');
define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST']);
define('ADMIN_EMAIL', 'admin@devawakening.com');

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; script-src 'self' cdn.tailwindcss.com cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' cdn.tailwindcss.com cdnjs.cloudflare.com; img-src 'self' data:; font-src 'self' cdnjs.cloudflare.com;");

// Custom Functions
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function format_date($timestamp) {
    return date('M j, Y g:i a', strtotime($timestamp));
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function handle_db_error($e) {
    error_log("Database error: " . $e->getMessage());
    die("A database error occurred. Please try again later.");
}
function safe_display($data, $default = '') {
    if (is_array($data)) {
        return $default;
    }
    return htmlspecialchars((string)$data, ENT_QUOTES,'UTF-8');
}
?>