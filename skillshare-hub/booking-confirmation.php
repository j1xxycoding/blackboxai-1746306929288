<?php
require_once 'includes/header.php';
redirectIfNotLoggedIn();

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    // Fetch booking details with related information
    $stmt = $pdo->prepare("
        SELECT b.*, 
               s.title, s.description, s.price, s.is_paid,
               u.username as instructor_name, u.profile_photo,
               p.payment_status, p.card_number
        FROM bookings b
        JOIN skills s ON b.skill_id = s.id
        JOIN users u ON s.user_id = u.id
        LEFT JOIN payments p ON b.id = p.booking_id
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $stmt->fetch();

    if (!$booking) {
        $_SESSION['error'] = "Booking not found.";
        header('Location: dashboard.php');
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Failed to fetch booking details.";
    header('Location: dashboard.php');
    exit();
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                        <h2 class="mt-3">Booking Confirmed!</h2>
                        <p class="text-muted">Your session has been successfully booked.</p>
                    </div>

                    <div class="booking-details">
                        <h5 class="card-title mb-4">Booking Details</h5>
                        
                        <div class="d-flex align-items-center mb-4">
                            <img src="<?php echo $booking['profile_photo'] ?? 'assets/images/default-profile.png'; ?>" 
                                 alt="Instructor" class="rounded-circle me-3" 
                                 style="width: 60px; height: 60px; object-fit: cover;">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($booking['title']); ?></h6>
                                <p class="text-muted mb-0">
                                    Instructor: <?php echo htmlspecialchars($booking['instructor_name']); ?>
                                </p>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="detail-item">
                                    <label class="text-muted">Date & Time:</label>
                                    <p class="mb-0">
                                        <?php echo date('F j, Y g:i A', strtotime($booking['booking_date'])); ?>
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="detail-item">
                                    <label class="text-muted">Status:</label>
                                    <p class="mb-0">
                                        <span class="badge bg-<?php 
                                            echo $booking['status'] === 'completed' ? 'success' : 
                                                ($booking['status'] === 'pending' ? 'warning' : 'primary'); 
                                        ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <?php if ($booking['is_paid']): ?>
                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <label class="text-muted">Amount Paid:</label>
                                        <p class="mb-0">$<?php echo number_format($booking['price'], 2); ?></p>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="detail-item">
                                        <label class="text-muted">Payment Status:</label>
                                        <p class="mb-0">
                                            <span class="badge bg-success">
                                                <?php echo ucfirst($booking['payment_status']); ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="detail-item">
                                        <label class="text-muted">Card Used:</label>
                                        <p class="mb-0"><?php echo htmlspecialchars($booking['card_number']); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <hr class="my-4">

                        <div class="booking-instructions">
                            <h6>Next Steps:</h6>
                            <ol class="mb-0">
                                <li>Check your email for booking confirmation and details.</li>
                                <li>The instructor will contact you to confirm the session.</li>
                                <li>Prepare any questions or specific topics you'd like to cover.</li>
                                <li>Be ready at the scheduled time for your session.</li>
                            </ol>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <a href="my-bookings.php" class="btn btn-primary me-2">View My Bookings</a>
                        <a href="browse.php" class="btn btn-outline-primary">Browse More Skills</a>
                    </div>
                </div>
            </div>

            <!-- Calendar Add Buttons -->
            <div class="text-center mt-4">
                <p class="mb-3">Add to your calendar:</p>
                <a href="#" class="btn btn-outline-secondary me-2" onclick="addToGoogleCalendar()">
                    <i class="fab fa-google me-2"></i>Google Calendar
                </a>
                <a href="#" class="btn btn-outline-secondary" onclick="addToICalendar()">
                    <i class="far fa-calendar-alt me-2"></i>iCalendar
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function addToGoogleCalendar() {
    const event = {
        title: <?php echo json_encode($booking['title']); ?>,
        details: 'Session with ' + <?php echo json_encode($booking['instructor_name']); ?>,
        location: 'Online',
        start: <?php echo json_encode(date('c', strtotime($booking['booking_date']))); ?>
    };

    const url = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(event.title)}&details=${encodeURIComponent(event.details)}&location=${encodeURIComponent(event.location)}&dates=${encodeURIComponent(event.start)}/${encodeURIComponent(event.start)}`;
    
    window.open(url, '_blank');
}

function addToICalendar() {
    // Implementation for downloading .ics file would go here
    alert('Feature coming soon!');
}
</script>

<?php require_once 'includes/footer.php'; ?>
