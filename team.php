<?php
require_once 'config/db.php';
require_once 'includes/lang.php';

$staff_stmt = $pdo->query("
    SELECT u.full_name, u.role, u.profile_picture, d.specialty, dept.dept_name 
    FROM users u 
    LEFT JOIN doctors d ON u.user_id = d.user_id 
    LEFT JOIN departments dept ON d.dept_id = dept.dept_id
    WHERE u.role IN ('admin', 'doctor', 'receptionist') 
    ORDER BY u.role DESC, u.full_name ASC
");
$staff_members = $staff_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <title>Our Medical Team - Smart Clinic</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .team-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-top: 25px; }
        .team-card { background: white; padding: 20px; border-radius: 8px; border: 1px solid var(--border); text-align: center; }
        .team-avatar { width: 85px; height: 85px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary); margin-bottom: 12px; }
        .badge-role { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; background: #e0f2fe; color: #0369a1; text-transform: uppercase; margin-top: 6px; }
    </style>
</head>
<body>
    <div style="background: white; padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <div class="logo">🏥 Smart Clinic Management System</div>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="team.php" style="color: var(--primary);">Our Team</a>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        </div>
    </div>

    <div class="container" style="margin-top: 40px; max-width: 1000px;">
        <div class="card" style="text-align: center;">
            <h2 style="color: var(--primary);">🩺 Meet Our Medical & Administrative Staff</h2>
            <p style="color: var(--text-light); margin-top: 8px;">Dedicated healthcare professionals committed to quality patient care.</p>
        </div>

        <div class="team-grid">
            <?php foreach ($staff_members as $member): ?>
                <div class="team-card">
                    <?php $m_pic = !empty($member['profile_picture']) ? $member['profile_picture'] : 'default.png'; ?>
                    <img src="uploads/<?php echo htmlspecialchars($m_pic); ?>" alt="Staff Photo" class="team-avatar">
                    <h4 style="margin-bottom: 4px;"><?php echo htmlspecialchars($member['full_name']); ?></h4>
                    <?php if (!empty($member['specialty'])): ?>
                        <p style="font-size: 0.85rem; color: var(--text-light); margin-bottom: 2px;"><?php echo htmlspecialchars($member['specialty']); ?></p>
                        <p style="font-size: 0.8rem; color: var(--primary); margin-bottom: 6px;"><?php echo htmlspecialchars($member['dept_name']); ?></p>
                    <?php endif; ?>
                    <span class="badge-role"><?php echo htmlspecialchars($member['role']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>