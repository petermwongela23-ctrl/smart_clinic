<?php
require_once '../config/db.php';
require_once '../includes/lang.php';
require_once '../includes/auth_check.php';
check_auth('admin');

$user_id = $_SESSION['user_id'];


$user_stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$user_stmt->execute([$user_id]);
$current_user = $user_stmt->fetch();

$patients_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'patient'")->fetchColumn();
$doctors_count = $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
$appointments_count = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
$departments_count = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; border: 1px solid var(--border); text-align: center; }
        .stat-card h3 { font-size: 2rem; color: var(--primary); margin-bottom: 5px; }
    </style>
</head>
<body>
    <div style="background: white; padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <?php 
                $pic = !empty($current_user['profile_picture']) ? $current_user['profile_picture'] : 'default.png'; 
            ?>
            <img src="../uploads/<?php echo htmlspecialchars($pic); ?>" alt="Avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border);">
            <div class="logo">🏥 Admin Portal - <?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
        </div>
        <div class="nav-links">
            <a href="dashboard.php" style="color: var(--primary);">Dashboard</a>
            <a href="manage_doctors.php">Manage Doctors</a>
            <a href="reports.php">Reports</a>
            <a href="../staff/hub.php">Staff Hub</a>
            <a href="profile.php">Profile</a>
            <a href="../logout.php" style="color: var(--danger);">Logout</a>
        </div>
    </div>

    <div class="container" style="margin-top: 30px;">
        <h2 style="margin-bottom: 20px;">Clinic Overview & Statistics</h2>
        <div class="dashboard-grid">
            <div class="stat-card"><h3><?php echo $patients_count; ?></h3><p>Total Patients</p></div>
            <div class="stat-card"><h3><?php echo $doctors_count; ?></h3><p>Active Doctors</p></div>
            <div class="stat-card"><h3><?php echo $appointments_count; ?></h3><p>Total Appointments</p></div>
            <div class="stat-card"><h3><?php echo $departments_count; ?></h3><p>Departments</p></div>
        </div>
        <div class="card">
            <h3>Quick Actions</h3>
            <p style="margin: 15px 0;">Use the management tools to register new doctors, assign departments, and oversee clinic operations.</p>
            <a href="manage_doctors.php" class="btn" style="display: inline-block; width: auto; padding: 10px 20px;">Manage Doctors & Schedules</a>
        </div>
    </div>
</body>
</html>