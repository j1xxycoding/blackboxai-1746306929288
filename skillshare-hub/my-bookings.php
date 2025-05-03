<?php
require_once 'includes/header.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT b.*, s.title, s.price, s.is_paid, u.username as instructor_name
        FROM bookings b
        JOIN skills s ON b.skill_id = s.id
        JOIN users u ON s.user_id = u.id
        WHERE b.user_id = ?
        ORDER BY b.booking_date DESC
    ");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    $bookings = [];
    $_SESSION['error'] = "Failed to fetch your bookings.";
}
?>

<div class="container">
    <h2 class="mb-4">My Bookings</h2>

    <?php if (empty($bookings)): ?>
        <p>You have no bookings yet. <a href="browse.php">Browse skills to book a session.</a></p>
    <?php else: ?>
        <div class="list-group">
            <?php foreach ($bookings as $booking): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <h5><?php echo htmlspecialchars($booking['title']); ?></h5>
                        <p class="mb-1">Instructor: <?php echo htmlspecialchars($booking['instructor_name']); ?></p>
                        <small>
                            Date: <?php echo date('M d, Y g:i A', strtotime($booking['booking_date'])); ?>
                            <span class="badge bg-<?php 
                                echo $booking['status'] === 'completed' ? 'success' : 
                                     ($booking['status'] === 'pending' ? 'warning' : 'primary'); 
                            ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                        </small>
                    </div>
                    <div>
                        <strong class="<?php echo $booking['is_paid'] ? 'text-primary' : 'text-success'; ?>">
                            <?php echo $booking['is_paid'] ? '$'.number_format($booking['price'], 2) : 'Free'; ?>
                        </strong>
                        <a href="booking-confirmation.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-primary ms-3">Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
