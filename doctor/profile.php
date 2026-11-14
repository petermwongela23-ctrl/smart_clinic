<?php
require_once '../config/db.php';
require_once '../includes/lang.php';
require_once '../includes/auth_check.php';
check_auth('doctor');

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $specialty = trim($_POST['specialty']);
    $consultation_fee = $_POST['consultation_fee'];
    $new_password = $_POST['password'];

    try {
        $pdo->beginTransaction();

        
        if (!empty($new_password)) {
            $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, password_hash = ? WHERE user_id = ?")
                ->execute([$full_name, $phone, $password_hash, $user_id]);
        } else {
            $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE user_id = ?")
                ->execute([$full_name, $phone, $user_id]);
        }

        $pdo->prepare("UPDATE doctors SET specialty = ?, consultation_fee = ? WHERE user_id = ?")
            ->execute([$specialty, $consultation_fee, $user_id]);

        
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['profile_picture']['tmp_name'];
            $file_name = time() . '_' . basename($_FILES['profile_picture']['name']);
            $upload_dir = '../uploads/';
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            if (move_uploaded_file($file_tmp, $upload_dir . $file_name)) {
                $pdo->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?")->execute([$file_name, $user_id]);
            }
        }

        $pdo->commit();
        $_SESSION['full_name'] = $full_name;
        $success = "Doctor profile updated successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error updating profile: " . $e->getMessage();
    }
}

$stmt = $pdo->prepare("
    SELECT u.*, d.specialty, d.consultation_fee, dept.dept_name 
    FROM users u 
    JOIN doctors d ON u.user_id = d.user_id 
    JOIN departments dept ON d.dept_id = dept.dept_id 
    WHERE u.user_id = ?
");
$stmt->execute([$user_id]);
$data = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <title>Doctor Profile</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div style="background: white; padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <?php 
                $pic = !empty($data['profile_picture']) ? $data['profile_picture'] : 'default.png'; 
            ?>
            <img src="../uploads/<?php echo htmlspecialchars($pic); ?>" alt="Avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border);">
            <div class="logo">🏥 Doctor Portal - Profile</div>
        </div>
        <div class="nav-links">
            <a href="dashboard.php">My Queue</a>
            <a href="profile.php" style="color: var(--primary);">Profile</a>
            <a href="../logout.php" style="color: var(--danger);">Logout</a>
        </div>
    </div>

    <div class="container" style="margin-top: 30px; max-width: 600px;">
        <div class="card">
            <h2 style="margin-bottom: 20px; color: var(--primary);">Doctor Profile & Professional Details</h2>
            <?php if (!empty($error)): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
            <?php if (!empty($success)): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
            
            <form action="profile.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Profile Picture</label>
                    <input type="file" name="profile_picture" class="form-control" accept="image/*">
                </div>
                <div class="form-group"><label>Full Name</label><input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($data['full_name']); ?>" required></div>
                <div class="form-group"><label>Email Address (Locked)</label><input type="email" class="form-control" value="<?php echo htmlspecialchars($data['email']); ?>" disabled></div>
                <div class="form-group"><label>Phone Number</label><input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($data['phone']); ?>" required></div>
                <div class="form-group"><label>Department (Locked)</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($data['dept_name']); ?>" disabled></div>
                <div class="form-group"><label>Specialty</label><input type="text" name="specialty" class="form-control" value="<?php echo htmlspecialchars($data['specialty']); ?>" required></div>
                <div class="form-group"><label>Consultation Fee (KES)</label><input type="number" step="0.01" name="consultation_fee" class="form-control" value="<?php echo $data['consultation_fee']; ?>" required></div>
                <div class="form-group"><label>New Password (leave blank to keep current)</label><input type="password" name="password" class="form-control" placeholder="••••••••"></div>
                <button type="submit" class="btn">Update Profile</button>
            </form>
        </div>
    </div>
</body>
</html>