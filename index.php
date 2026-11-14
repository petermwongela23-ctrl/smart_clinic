<?php
require_once 'config/db.php';
require_once 'includes/lang.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo __('app_title'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="container nav-container">
            <a href="index.php" class="logo">🏥 <?php echo __('app_title'); ?></a>
            <div class="nav-links">
                <a href="team.php">Our Team</a>
                <select class="lang-select" onchange="location = this.value;">
                    <option value="index.php?lang=en" <?php echo $_SESSION['lang']=='en'?'selected':''; ?>>English</option>
                    <option value="index.php?lang=sw" <?php echo $_SESSION['lang']=='sw'?'selected':''; ?>>Kiswahili</option>
                </select>
                <a href="login.php"><?php echo __('login'); ?></a>
                <a href="register.php" class="btn" style="padding: 6px 15px; color: white;"><?php echo __('register'); ?></a>
            </div>
        </div>
    </header>

    <div class="auth-wrapper">
        <div class="card text-center" style="max-width: 600px;">
            <h1 style="color: var(--primary); margin-bottom: 15px;"><?php echo __('welcome'); ?></h1>
            <p style="color: var(--text-light); margin-bottom: 25px;">
                Experience an advanced clinic management system featuring live queue tracking, automated appointment scheduling, and offline-friendly resilience.
            </p>
            <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                <a href="login.php" class="btn"><?php echo __('login'); ?></a>
                <a href="register.php" class="btn" style="background: var(--surface); color: var(--primary); border: 1px solid var(--primary);"><?php echo __('register'); ?></a>
                <a href="team.php" class="btn" style="background: #f0f9ff; color: var(--primary); border: 1px solid var(--border);">🩺 Meet Our Team</a>
            </div>
        </div>
    </div>
</body>
</html>