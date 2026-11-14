<?php
require_once '../config/db.php';
require_once '../includes/lang.php';
require_once '../includes/auth_check.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'patient') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();
$pic = !empty($current_user['profile_picture']) ? $current_user['profile_picture'] : 'default.png';


$staff_stmt = $pdo->query("
    SELECT u.full_name, u.role, u.profile_picture, d.specialty 
    FROM users u 
    LEFT JOIN doctors d ON u.user_id = d.user_id 
    WHERE u.role IN ('admin', 'doctor', 'receptionist') 
    ORDER BY u.role DESC, u.full_name ASC
");
$staff_members = $staff_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <title>Staff Portal Hub - Smart Clinic</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .hub-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-top: 20px; margin-bottom: 40px; }
        .hub-card { background: white; padding: 25px; border-radius: 8px; border: 1px solid var(--border); text-align: center; transition: transform 0.2s, box-shadow 0.2s; position: relative; overflow: hidden; }
        .hub-card:hover { transform: translateY(-3px); box-shadow: 0 6px 15px rgba(0,0,0,0.08); }
        .hub-card h3 { color: var(--primary); margin-bottom: 10px; }
        .hub-card p { color: var(--text-light); font-size: 0.9rem; margin-bottom: 20px; }
        
        .team-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-top: 15px; }
        .team-card { background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid var(--border); text-align: center; }
        .team-avatar { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary); margin-bottom: 10px; }
        
        .medical-banner { background: linear-gradient(135deg, #0284c7, #0369a1); color: white; padding: 25px; border-radius: 8px; margin-bottom: 30px; display: flex; align-items: center; justify-content: space-between; }
        .badge-role { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; background: #e0f2fe; color: #0369a1; text-transform: uppercase; margin-top: 5px; }
    </style>
</head>
<body>
    <div style="background: white; padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <img src="../uploads/<?php echo htmlspecialchars($pic); ?>" alt="Avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border);">
            <div class="logo">🏥 Smart Clinic Medical Hub</div>
        </div>
        <div class="nav-links">
            <span style="font-weight: 500; margin-right: 15px;">Dr./Staff: <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            <a href="../logout.php" style="color: var(--danger);">Logout</a>
        </div>
    </div>

    <div class="container" style="margin-top: 30px; max-width: 1000px;">
        
        
        <div class="medical-banner">
            <div>
                <h2>🩺 Welcome to Clinical Operations</h2>
                <p style="margin-top: 5px; opacity: 0.9;">Select your portal workspace or review active on-duty medical staff below.</p>
            </div>
            <div style="font-size: 3rem;">⚕️</div>
        </div>

        <h3 style="margin-bottom: 15px; color: var(--text);">Select Portal Access</h3>
        <div class="hub-grid">
            <?php if ($role === 'admin'): ?>
                <div class="hub-card">
                    <div style="font-size: 2.5rem; margin-bottom: 10px;">⚙️</div>
                    <h3>Admin Portal</h3>
                    <p>Manage doctors, department schedules, and oversee comprehensive hospital metrics.</p>
                    <a href="../admin/dashboard.php" class="btn">Open Admin Panel</a>
                </div>
            <?php endif; ?>

            <?php if ($role === 'doctor' || $role === 'admin'): ?>
                <div class="hub-card">
                    <div style="font-size: 2.5rem; margin-bottom: 10px;">🩺</div>
                    <h3>Doctor Portal</h3>
                    <p>Access active consultation queues, examine medical history, and issue prescriptions.</p>
                    <a href="../doctor/dashboard.php" class="btn">Open Doctor Portal</a>
                </div>
            <?php endif; ?>

            <?php if ($role === 'receptionist' || $role === 'admin'): ?>
                <div class="hub-card">
                    <div style="font-size: 2.5rem; margin-bottom: 10px;">📋</div>
                    <h3>Reception Portal</h3>
                    <p>Handle live queue tokens, verify QR appointment check-ins, and register walk-ins.</p>
                    <a href="../receptionist/dashboard.php" class="btn">Open Reception Portal</a>
                </div>
            <?php endif; ?>
        </div>

        
        <div class="card" style="margin-top: 40px; padding: 25px;">
            <h3 style="margin-bottom: 15px; color: var(--primary);">🏥 On-Duty Staff Directory & Profiles</h3>
            <p style="color: var(--text-light); font-size: 0.9rem; margin-bottom: 20px;">Registered medical personnel and administrative staff currently active in the system.</p>
            
            <div class="team-grid">
                <?php foreach ($staff_members as $member): ?>
                    <div class="team-card">
                        <?php 
                            $m_pic = !empty($member['profile_picture']) ? $member['profile_picture'] : 'default.png'; 
                        ?>
                        <img src="../uploads/<?php echo htmlspecialchars($m_pic); ?>" alt="Staff Avatar" class="team-avatar">
                        <h4 style="font-size: 0.95rem; margin-bottom: 4px;"><?php echo htmlspecialchars($member['full_name']); ?></h4>
                        <?php if (!empty($member['specialty'])): ?>
                            <p style="font-size: 0.8rem; color: var(--text-light); margin-bottom: 4px;"><?php echo htmlspecialchars($member['specialty']); ?></p>
                        <?php endif; ?>
                        <span class="badge-role"><?php echo htmlspecialchars($member['role']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</body>
</html>