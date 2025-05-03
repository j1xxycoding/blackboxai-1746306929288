<?php
require_once '../includes/header.php';
redirectIfNotAdmin();

// Handle skill deletion
if (isset($_POST['delete_skill'])) {
    $skill_id = (int)$_POST['skill_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM skills WHERE id = ?");
        $stmt->execute([$skill_id]);
        $_SESSION['success'] = "Skill deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to delete skill.";
    }
    header('Location: skills.php');
    exit();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$price_filter = isset($_GET['price']) ? $_GET['price'] : '';

// Build query
$where_clauses = [];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(s.title LIKE ? OR s.description LIKE ? OR s.tags LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

if (!empty($category)) {
    $where_clauses[] = "s.category = ?";
    $params[] = $category;
}

if ($price_filter === 'free') {
    $where_clauses[] = "s.is_paid = 0";
} elseif ($price_filter === 'paid') {
    $where_clauses[] = "s.is_paid = 1";
}

$where_clause = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

try {
    // Get total skills count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM skills s 
        $where_clause
    ");
    $stmt->execute($params);
    $total_skills = $stmt->fetchColumn();
    $total_pages = ceil($total_skills / $per_page);

    // Get skills for current page
    $stmt = $pdo->prepare("
        SELECT s.*, 
               u.username as instructor_name,
               (SELECT COUNT(*) FROM bookings WHERE skill_id = s.id) as bookings_count
        FROM skills s
        JOIN users u ON s.user_id = u.id
        $where_clause
        ORDER BY s.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute(array_merge($params, [$per_page, $offset]));
    $skills = $stmt->fetchAll();

    // Get categories for filter
    $categories = [
        'programming' => 'Programming & Technology',
        'design' => 'Design & Creative Arts',
        'business' => 'Business & Entrepreneurship',
        'marketing' => 'Marketing & Digital Media',
        'language' => 'Language Learning',
        'music' => 'Music & Performance',
        'fitness' => 'Health & Fitness',
        'cooking' => 'Cooking & Culinary Arts',
        'photography' => 'Photography & Video',
        'other' => 'Other'
    ];

} catch (PDOException $e) {
    $_SESSION['error'] = "Failed to fetch skills.";
    $skills = [];
    $total_pages = 0;
}
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage Skills</h2>
        <a href="dashboard.php" class="btn btn-outline-primary">Back to Dashboard</a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" 
                           placeholder="Search skills..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $key => $value): ?>
                            <option value="<?php echo $key; ?>" 
                                <?php echo $category === $key ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($value); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="price">
                        <option value="">All Prices</option>
                        <option value="free" <?php echo $price_filter === 'free' ? 'selected' : ''; ?>>Free</option>
                        <option value="paid" <?php echo $price_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <?php if (!empty($search) || !empty($category) || !empty($price_filter)): ?>
                        <a href="skills.php" class="btn btn-outline-secondary">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Skills Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Instructor</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Bookings</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($skills)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No skills found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($skills as $skill): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($skill['title']); ?></td>
                                    <td><?php echo htmlspecialchars($skill['instructor_name']); ?></td>
                                    <td><?php echo htmlspecialchars($categories[$skill['category']]); ?></td>
                                    <td>
                                        <?php if ($skill['is_paid']): ?>
                                            <span class="text-primary">$<?php echo number_format($skill['price'], 2); ?></span>
                                        <?php else: ?>
                                            <span class="text-success">Free</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $skill['bookings_count']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($skill['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="../book-skill.php?id=<?php echo $skill['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">View</a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="confirmDelete(<?php echo $skill['id']; ?>, '<?php echo htmlspecialchars($skill['title']); ?>')">
                                                Delete
                                            </button>
                                        </div>
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
                                <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&price=<?php echo urlencode($price_filter); ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&price=<?php echo urlencode($price_filter); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&price=<?php echo urlencode($price_filter); ?>">
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

<!-- Delete Skill Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete the skill "<span id="deleteSkillTitle"></span>"? 
                This action cannot be undone.
            </div>
            <div class="modal-footer">
                <form method="POST" action="">
                    <input type="hidden" name="skill_id" id="deleteSkillId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_skill" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(skillId, skillTitle) {
    document.getElementById('deleteSkillId').value = skillId;
    document.getElementById('deleteSkillTitle').textContent = skillTitle;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
