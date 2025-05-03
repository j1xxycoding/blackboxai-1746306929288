<?php
require_once 'config.php';

try {
    // Read and execute the SQLite database schema
    $schema = file_get_contents('sqlite_schema.sql');
    $pdo->exec($schema);
    echo "SQLite database schema created successfully.<br>";

    // Delete existing admin user if exists to avoid UNIQUE constraint error
    $pdo->exec("DELETE FROM users WHERE email = 'admin@skillshare-hub.com'");

    // Read and execute the admin setup
    $admin_setup = file_get_contents('admin-setup.sql');
    $pdo->exec($admin_setup);
    echo "Admin user created successfully.<br>";
    echo "<div style='margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;'>";
    echo "<h3>Admin Credentials:</h3>";
    echo "<p><strong>Username:</strong> admin<br>";
    echo "<strong>Password:</strong> admin123</p>";
    echo "<p><a href='login.php' class='btn btn-primary'>Go to Login Page</a></p>";
    echo "</div>";

} catch (PDOException $e) {
    die("Setup failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skillshare Hub Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h1>Skillshare Hub Setup</h1>
    <hr>
</body>
</html>
