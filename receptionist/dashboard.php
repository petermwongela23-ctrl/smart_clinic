<?php
require_once '../config/db.php';
require_once '../includes/lang.php';
require_once '../includes/auth_check.php';
check_auth('receptionist');

$success = '';
$error = '';
$today = date('Y-m-d');

function checkInAppointment($pdo, $apt_id, $today) {
    $q_stmt = $pdo->prepare("SELECT MAX(queue_number) FROM appointments WHERE appointment_date = ?");
    $q_stmt->execute([$today]);
    $max_q = $q_stmt->fetchColumn();
    $next_q = $max_q ? $max_q + 1 : 1;

    // FIX: Use exact ENUM value 'Checked-In'
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'Checked-In', queue_number = ? WHERE appointment_id = ?");
    $stmt->execute([$next_q, $apt_id]);
    return $next_q;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_checkin'])) {
    $qr_token = trim($_POST['qr_token']);
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE qr_token = ?");
    $stmt->execute([$qr_token]);
    $apt = $stmt->fetch();

    if (!$apt) {
        $error = "Invalid QR Token. No matching appointment found.";
    } elseif (in_array($apt['status'], ['Checked-In', 'Waiting', 'In Consultation', 'Completed'])) {
        $error = "This appointment is already marked as '{$apt['status']}'.";
    } else {
        $next_q = checkInAppointment($pdo, $apt['appointment_id'], $today);
        $success = "QR Check-In Successful! Assigned Queue Number: #{$next_q}";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $apt_id = $_POST['appointment_id'];
    $new_status = $_POST['status'];

    if ($new_status === 'Checked-In') {
        $next_q = checkInAppointment($pdo, $apt_id, $today);
        $success = "Patient checked in successfully! Assigned Queue Number: #{$next_q}";
    } else {
        $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
        $stmt->execute([$new_status, $apt_id]);
        $success = "Appointment status updated to {$new_status}.";
    }
}

$stmt = $pdo->prepare("
    SELECT a.*, p.full_name as patient_name, p.phone as patient_phone, u.full_name as doctor_name, d.specialty
    FROM appointments a
    JOIN users p ON a.patient_id = p.user_id
    JOIN doctors d ON a.doctor_id = d.doctor_id
    JOIN users u ON d.user_id = u.user_id
    WHERE a.appointment_date = ?
    ORDER BY a.queue_number ASC, a.appointment_time ASC
");
$stmt->execute([$today]);
$todays_appointments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <title>Receptionist Dashboard - Live Queue</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div style="background: white; padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <div class="logo">🏥 Receptionist Portal - Live Queue Manager</div>
        <div class="nav-links">
            <a href="dashboard.php" style="color: var(--primary);">Live Queue</a>
            <a href="walk_in.php" class="btn" style="padding: 6px 15px; color: white;">Register Walk-In</a>
            <a href="../staff/hub.php">Staff Hub</a>
            <a href="profile.php">Profile</a>
            <a href="../logout.php" style="color: var(--danger);">Logout</a>
        </div>
    </div>

    <div class="container" style="margin-top: 30px;">
        <?php if (!empty($error)): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
        <?php if (!empty($success)): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

        <div class="card" style="margin-bottom: 25px; padding: 20px;">
            <h3 style="margin-bottom: 10px; color: var(--primary);">⚡ Express QR Token Check-In</h3>
            <form action="dashboard.php" method="POST" style="display: flex; gap: 10px;">
                <input type="hidden" name="qr_checkin" value="1">
                <input type="text" name="qr_token" class="form-control" placeholder="Scan or paste QR Token (e.g., QR-A1B2C3...)" required style="max-width: 400px;">
                <button type="submit" class="btn" style="width: auto; padding: 10px 20px;">Verify & Check In</button>
            </form>
        </div>

        <h2 style="margin-bottom: 15px;">Today's Live Queue (<?php echo date('d M Y'); ?>)</h2>
        <div class="card">
            <table class="table">
                <thead>
                    <tr>
                        <th>Queue #</th>
                        <th>Patient Name</th>
                        <th>Phone</th>
                        <th>Doctor</th>
                        <th>Time</th>
                        <th>QR Token</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($todays_appointments) > 0): foreach ($todays_appointments as $apt): ?>
                        <tr>
                            <td><strong><?php echo $apt['queue_number'] ? '#' . $apt['queue_number'] : '-'; ?></strong></td>
                            <td><?php echo htmlspecialchars($apt['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($apt['patient_phone']); ?></td>
                            <td>Dr. <?php echo htmlspecialchars($apt['doctor_name']); ?></td>
                            <td><?php echo $apt['appointment_time']; ?></td>
                            <td><code><?php echo $apt['qr_token']; ?></code></td>
                            <td>
                                <?php $badgeClass = strtolower(str_replace([' ', '_'], '-', $apt['status'])); ?>
                                <span class="badge badge-<?php echo $badgeClass; ?>"><?php echo $apt['status']; ?></span>
                            </td>
                            <td>
                                <form action="dashboard.php" method="POST" style="display: flex; gap: 5px;">
                                    <input type="hidden" name="update_status" value="1">
                                    <input type="hidden" name="appointment_id" value="<?php echo $apt['appointment_id']; ?>">
                                    
                                    <?php if ($apt['status'] === 'Booked' || $apt['status'] === 'Confirmed'): ?>
                                        <button type="submit" name="status" value="Checked-In" class="btn" style="padding: 4px 10px; font-size: 0.8rem;">Check In</button>
                                    <?php elseif ($apt['status'] === 'Checked-In'): ?>
                                        <button type="submit" name="status" value="Waiting" class="btn" style="padding: 4px 10px; font-size: 0.8rem; background: #ca8a04;">Send to Waiting</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="8" class="text-center">No appointments scheduled for today.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>