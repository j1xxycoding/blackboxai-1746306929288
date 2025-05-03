<?php
require_once 'includes/header.php';
redirectIfNotLoggedIn();

// Get user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get user's skills
$stmt = $pdo->prepare("SELECT * FROM skills WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$skills = $stmt->fetchAll();

// Get user's bookings (as a student)
$stmt = $pdo->prepare("
    SELECT b.*, s.title, s.price, u.username as instructor_name 
    FROM bookings b 
    JOIN skills s ON b.skill_id = s.id 
    JOIN users u ON s.user_id = u.id 
    WHERE b.user_id = ? 
    ORDER BY b.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll();

// Get bookings for user's skills (as an instructor)
$stmt = $pdo->prepare("
    SELECT b.*, s.title, s.price, u.username as student_name 
    FROM bookings b 
    JOIN skills s ON b.skill_id = s.id 
    JOIN users u ON b.user_id = u.id 
    WHERE s.user_id = ? 
    ORDER BY b.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$received_bookings = $stmt->fetchAll();
?>

<div class="dashboard-container">
    <!-- Profile Summary -->
    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3 text-center text-md-start">
                    <div class="profile-photo mb-3 mb-md-0 d-flex align-items-center justify-content-center bg-light rounded-circle">
                        <i class="fas fa-user fa-3x text-secondary"></i>
                    </div>
                </div>
                <div class="col-md-9">
                    <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                    <p class="lead"><?php echo htmlspecialchars($user['bio'] ?? 'No bio added yet.'); ?></p>
                    <a href="profile.php" class="btn btn-light">Edit Profile</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Stats -->
    <div class="dashboard-stats">
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><?php echo count($skills); ?></h3>
                    <p>Skills Offered</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><?php echo count($bookings); ?></h3>
                    <p>Classes Booked</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><?php echo count($received_bookings); ?></h3>
                    <p>Teaching Sessions</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3>
                        <?php
                        $completed = array_filter($received_bookings, function($booking) {
                            return $booking['status'] === 'completed';
                        });
                        echo count($completed);
                        ?>
                    </h3>
                    <p>Completed Sessions</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Quick Actions</h5>
                    <a href="add-skill.php" class="btn btn-primary me-2">Add New Skill</a>
                    <a href="browse.php" class="btn btn-outline-primary me-2">Browse Skills</a>
                    <a href="my-bookings.php" class="btn btn-outline-primary">View All Bookings</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Your Skills -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Your Skills</h5>
                    <?php if (empty($skills)): ?>
                        <p class="text-muted">You haven't added any skills yet. <a href="add-skill.php">Add your first skill!</a></p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($skills as $skill): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($skill['title']); ?></td>
                                            <td><?php echo htmlspecialchars($skill['category']); ?></td>
                                            <td>
                                                <?php echo $skill['is_paid'] ? '$'.number_format($skill['price'], 2) : 'Free'; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">Active</span>
                                            </td>
                                            <td>
                                                <a href="edit-skill.php?id=<?php echo $skill['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">Edit</a>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteSkill(<?php echo $skill['id']; ?>)">Delete</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Bookings -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Your Recent Bookings</h5>
                    <?php if (empty($bookings)): ?>
                        <p class="text-muted">You haven't booked any sessions yet. 
                            <a href="browse.php">Browse available skills!</a>
                        </p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach (array_slice($bookings, 0, 5) as $booking): ?>
                                <div class="list-group-item">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($booking['title']); ?></h6>
                                    <p class="mb-1">Instructor: <?php echo htmlspecialchars($booking['instructor_name']); ?></p>
                                    <small>
                                        Date: <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                                        <span class="badge bg-<?php 
                                            echo $booking['status'] === 'completed' ? 'success' : 
                                                ($booking['status'] === 'pending' ? 'warning' : 'primary'); 
                                        ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($bookings) > 5): ?>
                            <div class="text-center mt-3">
                                <a href="my-bookings.php" class="btn btn-link">View All Bookings</a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Teaching Sessions</h5>
                    <?php if (empty($received_bookings)): ?>
                        <p class="text-muted">No one has booked your skills yet.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach (array_slice($received_bookings, 0, 5) as $booking): ?>
                                <div class="list-group-item">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($booking['title']); ?></h6>
                                    <p class="mb-1">Student: <?php echo htmlspecialchars($booking['student_name']); ?></p>
                                    <small>
                                        Date: <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                                        <span class="badge bg-<?php 
                                            echo $booking['status'] === 'completed' ? 'success' : 
                                                ($booking['status'] === 'pending' ? 'warning' : 'primary'); 
                                        ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($received_bookings) > 5): ?>
                            <div class="text-center mt-3">
                                <a href="teaching-sessions.php" class="btn btn-link">View All Sessions</a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function deleteSkill(skillId) {
    if (confirm('Are you sure you want to delete this skill? This action cannot be undone.')) {
        window.location.href = `delete-skill.php?id=${skillId}`;
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
