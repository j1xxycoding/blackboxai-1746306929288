<?php
require_once 'includes/header.php';
redirectIfNotLoggedIn();

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $category = $_POST['category'];
    $description = trim($_POST['description']);
    $availability = trim($_POST['availability']);
    $tags = trim($_POST['tags']);
    $is_paid = isset($_POST['is_paid']) ? 1 : 0;
    $price = $is_paid ? floatval($_POST['price']) : 0.00;
    
    $errors = [];

    // Validation
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if (empty($category) || !array_key_exists($category, $categories)) {
        $errors[] = "Please select a valid category";
    }
    
    if (empty($description)) {
        $errors[] = "Description is required";
    }
    
    if (empty($availability)) {
        $errors[] = "Availability information is required";
    }
    
    if ($is_paid && ($price <= 0 || $price > 999.99)) {
        $errors[] = "Please enter a valid price between $0.01 and $999.99";
    }

    // If no errors, insert the skill
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO skills (user_id, title, category, description, availability, tags, is_paid, price) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $title,
                $category,
                $description,
                $availability,
                $tags,
                $is_paid,
                $price
            ]);

            $_SESSION['success'] = "Skill added successfully!";
            header('Location: dashboard.php');
            exit();
        } catch (PDOException $e) {
            $errors[] = "Failed to add skill. Please try again.";
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="form-container">
            <h2 class="text-center mb-4">Add New Skill</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="title" class="form-label">Skill Title</label>
                    <input type="text" class="form-control" id="title" name="title" 
                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                           required>
                </div>

                <div class="mb-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $key => $value): ?>
                            <option value="<?php echo $key; ?>" 
                                <?php echo (isset($_POST['category']) && $_POST['category'] === $key) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($value); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4" required><?php 
                        echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; 
                    ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="availability" class="form-label">Availability</label>
                    <textarea class="form-control" id="availability" name="availability" rows="2" 
                              placeholder="e.g., Weekdays 6-9 PM, Weekends 10 AM-5 PM" required><?php 
                        echo isset($_POST['availability']) ? htmlspecialchars($_POST['availability']) : ''; 
                    ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="tags" class="form-label">Tags</label>
                    <input type="text" class="form-control" id="tags" name="tags" 
                           value="<?php echo isset($_POST['tags']) ? htmlspecialchars($_POST['tags']) : ''; ?>" 
                           placeholder="e.g., beginner, web development, javascript (comma separated)">
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="is_paid" name="is_paid" 
                               <?php echo isset($_POST['is_paid']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_paid">This is a paid skill</label>
                    </div>
                </div>

                <div class="mb-3" id="price-field" style="display: none;">
                    <label for="price" class="form-label">Price per Session ($)</label>
                    <input type="number" class="form-control" id="price" name="price" step="0.01" min="0.01" max="999.99"
                           value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Add Skill</button>
                    <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('is_paid').addEventListener('change', function() {
    const priceField = document.getElementById('price-field');
    priceField.style.display = this.checked ? 'block' : 'none';
    const priceInput = document.getElementById('price');
    if (this.checked) {
        priceInput.required = true;
    } else {
        priceInput.required = false;
        priceInput.value = '';
    }
});

// Show/hide price field on page load based on checkbox state
document.addEventListener('DOMContentLoaded', function() {
    const isPaidCheckbox = document.getElementById('is_paid');
    const priceField = document.getElementById('price-field');
    priceField.style.display = isPaidCheckbox.checked ? 'block' : 'none';
});
</script>

<?php require_once 'includes/footer.php'; ?>
