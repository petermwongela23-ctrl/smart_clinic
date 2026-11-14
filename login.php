<?php
require_once 'config/db.php';
require_once 'includes/lang.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];

            
            $ip = $_SERVER['REMOTE_ADDR'];
            $action = "User logged in successfully (" . $user['role'] . ")";
            $pdo->prepare("INSERT INTO audit_logs (user_id, action, ip_address) VALUES (?, ?, ?)")
                ->execute([$user['user_id'], $action, $ip]);

            
            if ($user['role'] === 'patient') {
                header("Location: patient/dashboard.php");
            } elseif (in_array($user['role'], ['admin', 'doctor', 'receptionist'])) {
                header("Location: staff/hub.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo __('login'); ?> - <?php echo __('app_title'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="container nav-container">
            <a href="index.php" class="logo">🏥 <?php echo __('app_title'); ?></a>
            <div class="nav-links">
                <a href="team.php">Our Team</a>
                <select class="lang-select" onchange="location = this.value;">
                    <option value="login.php?lang=en" <?php echo $_SESSION['lang']=='en'?'selected':''; ?>>English</option>
                    <option value="login.php?lang=sw" <?php echo $_SESSION['lang']=='sw'?'selected':''; ?>>Kiswahili</option>
                </select>
                <a href="register.php"><?php echo __('register'); ?></a>
            </div>
        </div>
    </header>

    <div class="auth-wrapper">
        <div class="card auth-card">
            <h2 style="margin-bottom: 20px; text-align: center; color: var(--primary);"><?php echo __('login'); ?></h2>
            <?php if (!empty($error)): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label><?php echo __('email'); ?></label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label><?php echo __('password'); ?></label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn"><?php echo __('login_btn'); ?></button>
            </form>
            <p class="text-center mt-3">
                <?php echo __('no_account'); ?> <a href="register.php" style="color: var(--primary);"><?php echo __('register'); ?></a>
            </p>
        </div>
    </div>
</body>
</html>