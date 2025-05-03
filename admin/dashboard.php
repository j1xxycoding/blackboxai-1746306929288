<?php
require_once '../includes/header.php';
redirectIfNotAdmin();

// Fetch statistics
try {
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0");
    $total_users = $stmt->fetchColumn();

    // Total skills
    $stmt = $pdo->query("SELECT COUNT(*) FROM skills");
    $total_skills = $stmt->fetchColumn();

    // Total bookings
    $stmt = $pdo->query("SELECT COUNT(*) FROM bookings");
    $total_bookings = $stmt->fetchColumn();

    // Total revenue
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(s.price), 0) as total_revenue
        FROM bookings b
        JOIN skills s ON b.skill_id = s.id
        JOIN payments p ON b.id = p.booking_id
        WHERE p.payment_status = 'completed'
    ");
    $total_revenue = $stmt->fetchColumn();

    // Recent users
    $stmt = $pdo->query("
        SELECT * FROM users 
        WHERE is_admin = 0 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recent_users = $stmt->fetchAll();

    // Recent bookings
    $stmt = $pdo->query("
        SELECT b.*, s.title, u.username, s.price
        FROM bookings b
        JOIN skills s ON b.skill_id = s.id
        JOIN users u ON b.user_id = u.id
        ORDER BY b.created_at DESC
        LIMIT 5
    ");
    $recent_bookings = $stmt->fetchAll();

} catch (PDOException $e) {
    $_SESSION['error'] = "Failed to fetch statistics.";
}
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Admin Dashboard</h2>
        <div>
            <a href="users.php" class="btn btn-outline-primary me-2">Manage Users</a>
            <a href="skills.php" class="btn btn-outline-primary me-2">Manage Skills</a>
            <a href="bookings.php" class="btn btn-outline-primary">Manage Bookings</a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Users</h5>
                    <h2 class="mb-0"><?php echo number_format($total_users); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Skills</h5>
                    <h2 class="mb-0"><?php echo number_format($total_skills); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Bookings</h5>
                    <h2 class="mb-0"><?php echo number_format($total_bookings); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Revenue</h5>
                    <h2 class="mb-0">$<?php echo number_format($total_revenue, 2); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Users -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Users</h5>
                    <a href="users.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <a href="edit-user.php?id=<?php echo $user['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">Edit</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Bookings</h5>
                    <a href="bookings.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Skill</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_bookings as $booking): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($booking['username']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['title']); ?></td>
                                        <td>$<?php echo number_format($booking['price'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $booking['status'] === 'completed' ? 'success' : 
                                                    ($booking['status'] === 'pending' ? 'warning' : 'primary'); 
                                            ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
