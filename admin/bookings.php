<?php
require_once '../includes/header.php';
redirectIfNotAdmin();

// Handle booking status update
if (isset($_POST['update_status'])) {
    $booking_id = (int)$_POST['booking_id'];
    $new_status = $_POST['status'];
    $valid_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];

    if (in_array($new_status, $valid_statuses)) {
        try {
            $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $booking_id]);
            $_SESSION['success'] = "Booking status updated successfully.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to update booking status.";
        }
    }
    header('Location: bookings.php');
    exit();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// Build query
$where_clauses = [];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(s.title LIKE ? OR u.username LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term]);
}

if (!empty($status)) {
    $where_clauses[] = "b.status = ?";
    $params[] = $status;
}

if (!empty($date_from)) {
    $where_clauses[] = "DATE(b.booking_date) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_clauses[] = "DATE(b.booking_date) <= ?";
    $params[] = $date_to;
}

$where_clause = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

try {
    // Get total bookings count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM bookings b
        JOIN skills s ON b.skill_id = s.id
        JOIN users u ON b.user_id = u.id
        $where_clause
    ");
    $stmt->execute($params);
    $total_bookings = $stmt->fetchColumn();
    $total_pages = ceil($total_bookings / $per_page);

    // Get bookings for current page
    $stmt = $pdo->prepare("
        SELECT b.*, 
               s.title as skill_title, s.price,
               u.username as student_name,
               i.username as instructor_name,
               p.payment_status
        FROM bookings b
        JOIN skills s ON b.skill_id = s.id
        JOIN users u ON b.user_id = u.id
        JOIN users i ON s.user_id = i.id
        LEFT JOIN payments p ON b.id = p.booking_id
        $where_clause
        ORDER BY b.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute(array_merge($params, [$per_page, $offset]));
    $bookings = $stmt->fetchAll();

} catch (PDOException $e) {
    $_SESSION['error'] = "Failed to fetch bookings.";
    $bookings = [];
    $total_pages = 0;
}
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage Bookings</h2>
        <a href="dashboard.php" class="btn btn-outline-primary">Back to Dashboard</a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" name="search" 
                           placeholder="Search by skill or student..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" name="date_from" 
                           placeholder="From Date"
                           value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" name="date_to" 
                           placeholder="To Date"
                           value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <?php if (!empty($search) || !empty($status) || !empty($date_from) || !empty($date_to)): ?>
                        <a href="bookings.php" class="btn btn-outline-secondary">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Bookings Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Skill</th>
                            <th>Student</th>
                            <th>Instructor</th>
                            <th>Date & Time</th>
                            <th>Price</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="9" class="text-center">No bookings found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td>#<?php echo $booking['id']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['skill_title']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['instructor_name']); ?></td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($booking['booking_date'])); ?></td>
                                    <td>$<?php echo number_format($booking['price'], 2); ?></td>
                                    <td>
                                        <?php if ($booking['price'] > 0): ?>
                                            <span class="badge bg-<?php 
                                                echo $booking['payment_status'] === 'completed' ? 'success' : 'warning'; 
                                            ?>">
                                                <?php echo ucfirst($booking['payment_status']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Free</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $booking['status'] === 'completed' ? 'success' : 
                                                ($booking['status'] === 'pending' ? 'warning' : 
                                                ($booking['status'] === 'cancelled' ? 'danger' : 'primary')); 
                                        ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="showStatusModal(<?php echo $booking['id']; ?>, '<?php echo $booking['status']; ?>')">
                                            Update Status
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                                    Next
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Booking Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="booking_id" id="statusBookingId">
                    <div class="mb-3">
                        <label for="status" class="form-label">New Status</label>
                        <select class="form-select" name="status" id="statusSelect" required>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showStatusModal(bookingId, currentStatus) {
    document.getElementById('statusBookingId').value = bookingId;
    document.getElementById('statusSelect').value = currentStatus;
    new bootstrap.Modal(document.getElementById('statusModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
