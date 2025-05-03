<?php
// SQLite database connection config
$db_file = __DIR__ . '/skillshare_hub.sqlite';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO("sqlite:" . $db_file, null, null, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function redirectIfNotAdmin() {
    if (!isAdmin()) {
        header('Location: index.php');
        exit();
    }
}
?>
