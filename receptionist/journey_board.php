<?php
require_once '../config/db.php';
require_once '../includes/lang.php';
require_once '../includes/auth_check.php';
check_auth('receptionist'); 


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'], $_POST['new_status'])) {
    $appt_id = $_POST['appointment_id'];
    $new_status = $_POST['new_status'];
    
    $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
    $stmt->execute([$new_status, $appt_id]);
    
    header("Location: journey_board.php?updated=1");
    exit();
}


$stmt = $pdo->query("
    SELECT a.*, p.full_name as patient_name, p.phone as patient_phone, u.full_name as doctor_name, d.specialty
    FROM appointments a
    JOIN users p ON a.patient_id = p.user_id
    JOIN doctors d ON a.doctor_id = d.doctor_id
    JOIN users u ON d.user_id = u.user_id
    WHERE a.appointment_date = CURDATE() AND a.status NOT IN ('Completed', 'Cancelled', 'No-Show')
    ORDER BY a.appointment_time ASC
");
$active_patients = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <title>Patient Journey Tracking - Reception</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .journey-table { width: 100%; border-collapse: collapse; margin-top: 15px; background: white; border-radius: 8px; overflow: hidden; border: 1px solid var(--border); }
        .journey-table th, .journey-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        .journey-table th { background: #f8fafc; color: var(--primary); font-weight: 600; }
        .status-badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
        .status-Booked { background: #e0f2fe; color: #0369a1; }
        .status-Confirmed { background: #fef9c3; color: #854d0e; }
        .status-Arrived { background: #ffedd5; color: #c2410c; }
        .status-Checked-In { background: #dcfce7; color: #166534; }
        .status-Waiting { background: #f3e8ff; color: #6b21a8; }
        .status-In-Consultation { background: #ccfbf1; color: #115e59; }
    </style>
</head>
<body>
    <div style="background: white; padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <div class="logo">🏥 Live Patient Journey Tracking</div>
        <div class="nav-links">
            <a href="dashboard.php">Live Queue</a>
            <a href="journey_board.php" style="color: var(--primary);">Journey Board</a>
            <a href="walk_in.php">Register Walk-In</a>
            <a href="profile.php">Profile</a>
            <a href="../logout.php" style="color: var(--danger);">Logout</a>
        </div>
    </div>

    <div class="container" style="margin-top: 30px; max-width: 1200px;">
        <h2 style="margin-bottom: 5px;">Real-Time Patient Pipeline (Today)</h2>
        <p style="color: var(--text-light); margin-bottom: 20px;">Monitor and advance patient progress from arrival through consultation completion.</p>

        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;">Patient journey stage updated successfully!</div>
        <?php endif; ?>

        <div class="card" style="padding: 0; overflow: hidden;">
            <table class="journey-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Patient Name</th>
                        <th>Doctor Assigned</th>
                        <th>Current Journey Stage</th>
                        <th>Advance Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($active_patients) > 0): foreach ($active_patients as $pat): ?>
                        <tr>
                            <td><strong><?php echo $pat['appointment_time']; ?></strong></td>
                            <td>
                                <?php echo htmlspecialchars($pat['patient_name']); ?><br>
                                <small style="color: var(--text-light);"><?php echo htmlspecialchars($pat['patient_phone']); ?></small>
                            </td>
                            <td>
                                Dr. <?php echo htmlspecialchars($pat['doctor_name']); ?><br>
                                <small style="color: var(--text-light);"><?php echo htmlspecialchars($pat['specialty']); ?></small>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo str_replace(' ', '-', $pat['status']); ?>">
                                    <?php echo $pat['status']; ?>
                                </span>
                            </td>
                            <td>
                                <form action="journey_board.php" method="POST" style="display: flex; gap: 8px; align-items: center;">
                                    <input type="hidden" name="appointment_id" value="<?php echo $pat['appointment_id']; ?>">
                                    <select name="new_status" class="form-control" style="padding: 5px 10px; font-size: 0.85rem; width: auto;">
                                        <option value="Booked" <?php echo $pat['status']=='Booked'?'selected':''; ?>>Booked</option>
                                        <option value="Confirmed" <?php echo $pat['status']=='Confirmed'?'selected':''; ?>>Confirmed</option>
                                        <option value="Arrived" <?php echo $pat['status']=='Arrived'?'selected':''; ?>>Arrived</option>
                                        <option value="Checked-In" <?php echo $pat['status']=='Checked-In'?'selected':''; ?>>Checked-In</option>
                                        <option value="Waiting" <?php echo $pat['status']=='Waiting'?'selected':''; ?>>Waiting</option>
                                        <option value="In Consultation" <?php echo $pat['status']=='In Consultation'?'selected':''; ?>>In Consultation</option>
                                        <option value="Completed" <?php echo $pat['status']=='Completed'?'selected':''; ?>>Completed</option>
                                    </select>
                                    <button type="submit" class="btn" style="padding: 5px 12px; font-size: 0.85rem;">Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="5" style="text-align: center; padding: 30px; color: var(--text-light);">No active patients in clinic pipeline for today.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>