<?php
require_once 'includes/header.php';
redirectIfNotLoggedIn();

// Get skill ID from URL
$skill_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch skill details
try {
    $stmt = $pdo->prepare("
        SELECT s.*, u.username as instructor_name, u.profile_photo 
        FROM skills s 
        JOIN users u ON s.user_id = u.id 
        WHERE s.id = ?
    ");
    $stmt->execute([$skill_id]);
    $skill = $stmt->fetch();

    if (!$skill) {
        $_SESSION['error'] = "Skill not found.";
        header('Location: browse.php');
        exit();
    }

    // Prevent booking your own skill
    if ($skill['user_id'] == $_SESSION['user_id']) {
        $_SESSION['error'] = "You cannot book your own skill.";
        header('Location: browse.php');
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Failed to fetch skill details.";
    header('Location: browse.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_date = $_POST['booking_date'];
    $booking_time = $_POST['booking_time'];
    $errors = [];

    // Validation
    if (empty($booking_date) || empty($booking_time)) {
        $errors[] = "Please select both date and time for the booking.";
    } else {
        $datetime = date('Y-m-d H:i:s', strtotime("$booking_date $booking_time"));
        if (strtotime($datetime) < time()) {
            $errors[] = "Please select a future date and time.";
        }
    }

    // Process booking
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Create booking
            $stmt = $pdo->prepare("
                INSERT INTO bookings (skill_id, user_id, booking_date, status) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$skill_id, $_SESSION['user_id'], $datetime, 'pending']);
            $booking_id = $pdo->lastInsertId();

            // If it's a paid skill, create payment record
            if ($skill['is_paid']) {
                $stmt = $pdo->prepare("
                    INSERT INTO payments (booking_id, card_number, payment_status) 
                    VALUES (?, ?, ?)
                ");
                // Mask card number for storage
                $masked_card = substr($_POST['card_number'], -4);
                $stmt->execute([$booking_id, "XXXX-XXXX-XXXX-" . $masked_card, 'completed']);
            }

            $pdo->commit();
            $_SESSION['success'] = "Booking successful!";
            header("Location: booking-confirmation.php?id=" . $booking_id);
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Booking failed. Please try again.";
        }
    }
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-4">
                        <img src="<?php echo $skill['profile_photo'] ?? 'assets/images/default-profile.png'; ?>" 
                             alt="Instructor" class="rounded-circle me-3" 
                             style="width: 60px; height: 60px; object-fit: cover;">
                        <div>
                            <h5 class="card-title mb-1"><?php echo htmlspecialchars($skill['title']); ?></h5>
                            <p class="text-muted mb-0">
                                Instructor: <?php echo htmlspecialchars($skill['instructor_name']); ?>
                            </p>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6>Description:</h6>
                        <p><?php echo nl2br(htmlspecialchars($skill['description'])); ?></p>
                    </div>

                    <div class="mb-4">
                        <h6>Availability:</h6>
                        <p><?php echo nl2br(htmlspecialchars($skill['availability'])); ?></p>
                    </div>

                    <div class="mb-4">
                        <h6>Price:</h6>
                        <p class="<?php echo $skill['is_paid'] ? 'text-primary' : 'text-success'; ?> fw-bold">
                            <?php echo $skill['is_paid'] ? '$'.number_format($skill['price'], 2) : 'Free'; ?> per session
                        </p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Book This Skill</h5>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" id="bookingForm">
                        <div class="mb-3">
                            <label for="booking_date" class="form-label">Select Date</label>
                            <input type="date" class="form-control" id="booking_date" name="booking_date" 
                                   min="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="booking_time" class="form-label">Select Time</label>
                            <input type="time" class="form-control" id="booking_time" name="booking_time" required>
                        </div>

                        <?php if ($skill['is_paid']): ?>
                            <div class="payment-section mt-4">
                                <h6 class="mb-3">Payment Information</h6>
                                
                                <div class="mb-3">
                                    <label for="card_number" class="form-label">Card Number</label>
                                    <input type="text" class="form-control" id="card_number" name="card_number" 
                                           placeholder="1234 5678 9012 3456" maxlength="19" required>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="card_expiry" class="form-label">Expiry Date</label>
                                        <input type="text" class="form-control" id="card_expiry" name="card_expiry" 
                                               placeholder="MM/YY" maxlength="5" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="card_cvv" class="form-label">CVV</label>
                                        <input type="text" class="form-control" id="card_cvv" name="card_cvv" 
                                               placeholder="123" maxlength="4" required>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $skill['is_paid'] ? 'Pay and Book' : 'Book Now'; ?>
                            </button>
                            <a href="browse.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Format card number with spaces
document.getElementById('card_number')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\s/g, '');
    if (value.length > 16) value = value.substr(0, 16);
    const parts = value.match(/.{1,4}/g) || [];
    e.target.value = parts.join(' ');
});

// Format expiry date
document.getElementById('card_expiry')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 4) value = value.substr(0, 4);
    if (value.length > 2) {
        value = value.substr(0, 2) + '/' + value.substr(2);
    }
    e.target.value = value;
});

// Validate CVV
document.getElementById('card_cvv')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 4) value = value.substr(0, 4);
    e.target.value = value;
});
</script>

<?php require_once 'includes/footer.php'; ?>
