<?php
require_once 'includes/header.php';

// Get categories (same as in add-skill.php)
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

// Handle search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$price_filter = isset($_GET['price']) ? $_GET['price'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build the query
$query = "
    SELECT s.*, u.username, u.profile_photo 
    FROM skills s 
    JOIN users u ON s.user_id = u.id 
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $query .= " AND (s.title LIKE ? OR s.description LIKE ? OR s.tags LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($category)) {
    $query .= " AND s.category = ?";
    $params[] = $category;
}

if ($price_filter === 'free') {
    $query .= " AND s.is_paid = 0";
} elseif ($price_filter === 'paid') {
    $query .= " AND s.is_paid = 1";
}

// Add sorting
switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY s.price ASC, s.created_at DESC";
        break;
    case 'price_high':
        $query .= " ORDER BY s.price DESC, s.created_at DESC";
        break;
    case 'oldest':
        $query .= " ORDER BY s.created_at ASC";
        break;
    default: // newest
        $query .= " ORDER BY s.created_at DESC";
}

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $skills = $stmt->fetchAll();
} catch (PDOException $e) {
    $skills = [];
    $_SESSION['error'] = "Failed to fetch skills. Please try again.";
}
?>

<!-- Search and Filter Section -->
<div class="search-container">
    <div class="container">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" name="search" placeholder="Search skills..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
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
                    <option value="all" <?php echo $price_filter === 'all' ? 'selected' : ''; ?>>All Prices</option>
                    <option value="free" <?php echo $price_filter === 'free' ? 'selected' : ''; ?>>Free</option>
                    <option value="paid" <?php echo $price_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <select class="form-select" name="sort">
                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                </select>
            </div>
            
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Results Section -->
<div class="container mt-4">
    <?php if (empty($skills)): ?>
        <div class="text-center">
            <h3>No skills found</h3>
            <p class="text-muted">Try adjusting your search criteria or browse all available skills.</p>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($skills as $skill): ?>
                <div class="col">
                    <div class="card h-100 skill-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle me-2 d-flex align-items-center justify-content-center bg-light" 
                                     style="width: 40px; height: 40px;">
                                    <i class="fas fa-user text-secondary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($skill['username']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($categories[$skill['category']]); ?>
                                    </small>
                                </div>
                            </div>
                            
                            <h5 class="card-title"><?php echo htmlspecialchars($skill['title']); ?></h5>
                            <p class="card-text">
                                <?php echo nl2br(htmlspecialchars(substr($skill['description'], 0, 150) . '...')); ?>
                            </p>
                            
                            <?php if (!empty($skill['tags'])): ?>
                                <div class="mb-3">
                                    <?php foreach (explode(',', $skill['tags']) as $tag): ?>
                                        <span class="skill-tag"><?php echo htmlspecialchars(trim($tag)); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong class="<?php echo $skill['is_paid'] ? 'text-primary' : 'text-success'; ?>">
                                        <?php echo $skill['is_paid'] ? '$'.number_format($skill['price'], 2) : 'Free'; ?>
                                    </strong>
                                    <small class="text-muted ms-2">per session</small>
                                </div>
                                <a href="book-skill.php?id=<?php echo $skill['id']; ?>" 
                                   class="btn btn-outline-primary">Book Now</a>
                            </div>
                        </div>
                        <div class="card-footer text-muted">
                            <small>
                                <i class="far fa-clock"></i> 
                                Available: <?php echo htmlspecialchars($skill['availability']); ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
