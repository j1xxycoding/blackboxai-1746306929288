<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
<div class="bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="display-4 fw-bold mb-4">Share Your Skills, Learn from Others</h1>
                <p class="lead mb-4">Connect with skilled instructors or share your expertise with eager learners. Join our community of knowledge sharing today!</p>
                <?php if (!isLoggedIn()): ?>
                    <div class="d-grid gap-2 d-md-flex">
                        <a href="register.php" class="btn btn-light btn-lg px-4 me-md-2">Get Started</a>
                        <a href="browse.php" class="btn btn-outline-light btn-lg px-4">Browse Skills</a>
                    </div>
                <?php else: ?>
                    <div class="d-grid gap-2 d-md-flex">
                        <a href="add-skill.php" class="btn btn-light btn-lg px-4 me-md-2">Share Your Skills</a>
                        <a href="browse.php" class="btn btn-outline-light btn-lg px-4">Find Skills</a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-6 d-none d-md-block text-center">
                <i class="fas fa-graduation-cap fa-10x text-white opacity-75"></i>
            </div>
        </div>
    </div>
</div>

<!-- Featured Categories -->
<div class="container py-5">
    <h2 class="text-center mb-4">Popular Categories</h2>
    <div class="row g-4">
        <?php
        $categories = [
            ['icon' => 'fas fa-laptop-code', 'name' => 'Programming', 'link' => 'browse.php?category=programming'],
            ['icon' => 'fas fa-palette', 'name' => 'Design', 'link' => 'browse.php?category=design'],
            ['icon' => 'fas fa-chart-line', 'name' => 'Business', 'link' => 'browse.php?category=business'],
            ['icon' => 'fas fa-bullhorn', 'name' => 'Marketing', 'link' => 'browse.php?category=marketing'],
            ['icon' => 'fas fa-language', 'name' => 'Languages', 'link' => 'browse.php?category=language'],
            ['icon' => 'fas fa-music', 'name' => 'Music', 'link' => 'browse.php?category=music'],
            ['icon' => 'fas fa-heartbeat', 'name' => 'Fitness', 'link' => 'browse.php?category=fitness'],
            ['icon' => 'fas fa-utensils', 'name' => 'Cooking', 'link' => 'browse.php?category=cooking']
        ];

        foreach ($categories as $category):
        ?>
            <div class="col-6 col-md-3">
                <a href="<?php echo $category['link']; ?>" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm hover-card">
                        <div class="card-body text-center">
                            <i class="<?php echo $category['icon']; ?> fa-2x text-primary mb-3"></i>
                            <h5 class="card-title mb-0"><?php echo $category['name']; ?></h5>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Featured Skills -->
<div class="bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-4">Featured Skills</h2>
        <div class="row g-4">
            <?php
            try {
                $stmt = $pdo->query("
                    SELECT s.*, u.username, u.profile_photo 
                    FROM skills s 
                    JOIN users u ON s.user_id = u.id 
                    ORDER BY RAND() 
                    LIMIT 6
                ");
                $featured_skills = $stmt->fetchAll();

                foreach ($featured_skills as $skill):
            ?>
                <div class="col-md-4">
                    <div class="card h-100 skill-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?php echo $skill['profile_photo'] ?? 'assets/images/default-profile.png'; ?>" 
                                     alt="Instructor" class="rounded-circle me-2" 
                                     style="width: 40px; height: 40px; object-fit: cover;">
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($skill['username']); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($skill['category']); ?></small>
                                </div>
                            </div>
                            <h5 class="card-title"><?php echo htmlspecialchars($skill['title']); ?></h5>
                            <p class="card-text">
                                <?php echo nl2br(htmlspecialchars(substr($skill['description'], 0, 100) . '...')); ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="<?php echo $skill['is_paid'] ? 'text-primary' : 'text-success'; ?> fw-bold">
                                    <?php echo $skill['is_paid'] ? '$'.number_format($skill['price'], 2) : 'Free'; ?>
                                </span>
                                <a href="book-skill.php?id=<?php echo $skill['id']; ?>" 
                                   class="btn btn-outline-primary">Learn More</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php 
                endforeach;
            } catch (PDOException $e) {
                // Handle error silently
            }
            ?>
        </div>
        <div class="text-center mt-4">
            <a href="browse.php" class="btn btn-primary btn-lg">Explore All Skills</a>
        </div>
    </div>
</div>

<!-- How It Works -->
<div class="container py-5">
    <h2 class="text-center mb-5">How It Works</h2>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="text-center">
                <div class="circle-icon mb-3">
                    <i class="fas fa-search fa-2x text-primary"></i>
                </div>
                <h4>1. Find a Skill</h4>
                <p>Browse through our diverse range of skills and choose what you want to learn.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="text-center">
                <div class="circle-icon mb-3">
                    <i class="fas fa-calendar-check fa-2x text-primary"></i>
                </div>
                <h4>2. Book a Session</h4>
                <p>Select your preferred time slot and book a session with the instructor.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="text-center">
                <div class="circle-icon mb-3">
                    <i class="fas fa-graduation-cap fa-2x text-primary"></i>
                </div>
                <h4>3. Start Learning</h4>
                <p>Connect with your instructor and begin your learning journey.</p>
            </div>
        </div>
    </div>
</div>

<!-- Call to Action -->
<div class="bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="mb-3">Ready to Share Your Skills?</h2>
                <p class="lead mb-0">Join our community of instructors and start teaching today!</p>
            </div>
            <div class="col-md-4 text-md-end">
                <?php if (!isLoggedIn()): ?>
                    <a href="register.php" class="btn btn-light btn-lg">Get Started</a>
                <?php else: ?>
                    <a href="add-skill.php" class="btn btn-light btn-lg">Share Your Skills</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.hover-card {
    transition: transform 0.2s;
}

.hover-card:hover {
    transform: translateY(-5px);
}

.circle-icon {
    width: 80px;
    height: 80px;
    background-color: #f8f9fa;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}
</style>

<?php require_once 'includes/footer.php'; ?>
