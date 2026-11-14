<?php
require_once 'config/db.php';
require_once 'includes/lang.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $role = 'patient';

    if (empty($full_name) || empty($email) || empty($phone) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $error = "Email is already registered.";
        } else {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$full_name, $email, $phone, $password_hash, $role])) {
                $success = "Registration successful! You can now log in.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo __('register'); ?> - <?php echo __('app_title'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="container nav-container">
            <a href="index.php" class="logo">🏥 <?php echo __('app_title'); ?></a>
            <div class="nav-links">
                <select class="lang-select" onchange="location = this.value;">
                    <option value="register.php?lang=en" <?php echo $_SESSION['lang']=='en'?'selected':''; ?>>English</option>
                    <option value="register.php?lang=sw" <?php echo $_SESSION['lang']=='sw'?'selected':''; ?>>Kiswahili</option>
                </select>
                <a href="login.php"><?php echo __('login'); ?></a>
            </div>
        </div>
    </header>

    <div class="auth-wrapper">
        <div class="card auth-card">
            <h2 style="margin-bottom: 20px; text-align: center; color: var(--primary);"><?php echo __('register'); ?></h2>
            <?php if (!empty($error)): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
            <?php if (!empty($success)): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
            <form action="register.php" method="POST">
                <div class="form-group">
                    <label><?php echo __('full_name'); ?></label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label><?php echo __('email'); ?></label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label><?php echo __('phone'); ?></label>
                    <input type="text" name="phone" class="form-control" required placeholder="0712345678">
                </div>
                <div class="form-group">
                    <label><?php echo __('password'); ?></label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn"><?php echo __('register_btn'); ?></button>
            </form>
            <p class="text-center mt-3">
                <?php echo __('has_account'); ?> <a href="login.php" style="color: var(--primary);"><?php echo __('login'); ?></a>
            </p>
        </div>
    </div>
</body>
</html>