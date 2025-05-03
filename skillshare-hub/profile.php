<?php
require_once 'includes/header.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = trim($_POST['bio']);
    $social_links = trim($_POST['social_links']);

    // Handle profile photo upload
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_photo']['tmp_name'];
        $fileName = $_FILES['profile_photo']['name'];
        $fileSize = $_FILES['profile_photo']['size'];
        $fileType = $_FILES['profile_photo']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($fileExtension, $allowedfileExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $profile_photo_path = 'uploads/' . $newFileName;
            } else {
                $errors[] = 'Error moving the uploaded file.';
            }
        } else {
            $errors[] = 'Upload failed. Allowed file types: ' . implode(', ', $allowedfileExtensions);
        }
    }

    if (empty($errors)) {
        try {
            if (isset($profile_photo_path)) {
                $stmt = $pdo->prepare("UPDATE users SET bio = ?, social_links = ?, profile_photo = ? WHERE id = ?");
                $stmt->execute([$bio, $social_links, $profile_photo_path, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET bio = ?, social_links = ? WHERE id = ?");
                $stmt->execute([$bio, $social_links, $user_id]);
            }
            $success = 'Profile updated successfully.';
        } catch (PDOException $e) {
            $errors[] = 'Failed to update profile.';
        }
    }
}

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="form-container">
            <h2 class="mb-4">Edit Profile</h2>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="mb-3 text-center">
                    <?php if (!empty($user['profile_photo'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="Profile Photo" class="profile-photo mb-3">
                    <?php else: ?>
                        <div class="profile-photo d-flex align-items-center justify-content-center bg-light rounded-circle mx-auto mb-3" style="width: 150px; height: 150px;">
                            <i class="fas fa-user fa-5x text-secondary"></i>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="profile_photo" accept="image/*" class="form-control profile-photo-input">
                </div>

                <div class="mb-3">
                    <label for="bio" class="form-label">Bio</label>
                    <textarea name="bio" id="bio" class="form-control" rows="4"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="social_links" class="form-label">Social Links (comma separated URLs)</label>
                    <input type="text" name="social_links" id="social_links" class="form-control" value="<?php echo htmlspecialchars($user['social_links'] ?? ''); ?>">
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="dashboard.php" class="btn btn-secondary ms-2">Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
