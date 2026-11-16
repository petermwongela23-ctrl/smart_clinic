<?php
require_once '../config/db.php';
require_once '../includes/lang.php';
require_once '../includes/auth_check.php';
check_auth('patient');

$patient_id = $_SESSION['user_id'];


$stmt = $pdo->prepare("
    SELECT a.*, u.full_name as doctor_name, d.specialty, dept.dept_name
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.doctor_id
    JOIN users u ON d.user_id = u.user_id
    JOIN departments dept ON d.dept_id = dept.dept_id
    WHERE a.patient_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt->execute([$patient_id]);
$appointments = $stmt->fetchAll();


function getQueueMetrics($pdo, $doctor_id, $appointment_id, $appointment_time, $appointment_date) {
    if ($appointment_date !== date('Y-m-d')) {
        return ['position' => '-', 'wait_time' => 'N/A (Scheduled for future/past)'];
    }

    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM appointments 
        WHERE doctor_id = ? 
        AND appointment_date = CURDATE() 
        AND appointment_time <= ? 
        AND status IN ('Arrived', 'Checked-In', 'Waiting', 'In Consultation')
        AND appointment_id <= ?
    ");
    $stmt->execute([$doctor_id, $appointment_time, $appointment_id]);
    $position = $stmt->fetchColumn();

    
    $wait_minutes = max(0, ($position - 1) * 15);
    
    return [
        'position' => $position > 0 ? $position : 1,
        'wait_time' => $wait_minutes == 0 ? 'Up next or currently being seen' : "~{$wait_minutes} minutes"
    ];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <title>Patient Dashboard - Smart Clinic</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .wait-box { background: #f0fdf4; border: 1px solid #bbf7d0; padding: 15px; border-radius: 8px; margin-top: 15px; display: flex; justify-content: space-between; align-items: center; }
        .badge-status { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; background: #e0f2fe; color: #0369a1; }
    </style>
</head>
<body>
    <div style="background: white; padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <div class="logo">🏥 Patient Portal - Dashboard</div>
        <div class="nav-links">
            <a href="dashboard.php" style="color: var(--primary);">My Appointments</a>
            <a href="book_appointment.php">Book Appointment</a>
            <a href="records.php">Medical Records</a>
            <!-- <a href="profile.php">Profile</a> -->
            <a href="../logout.php" style="color: var(--danger);">Logout</a>
        </div>
    </div>

    <div class="container" style="margin-top: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2>My Appointments & Queue Status</h2>
            <a href="book_appointment.php" class="btn" style="width: auto; padding: 10px 20px;">+ Book New Appointment</a>
        </div>

        <?php if (count($appointments) > 0): foreach ($appointments as $appt): ?>
            <?php 
                $metrics = getQueueMetrics($pdo, $appt['doctor_id'], $appt['appointment_id'], $appt['appointment_time'], $appt['appointment_date']);
            ?>
            <div class="card" style="margin-bottom: 20px; border-left: 4px solid var(--primary);">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 12px; margin-bottom: 15px;">
                    <div>
                        <h3 style="color: var(--primary); margin-bottom: 3px;">Dr. <?php echo htmlspecialchars($appt['doctor_name']); ?></h3>
                        <p style="font-size: 0.85rem; color: var(--text-light);"><?php echo htmlspecialchars($appt['specialty']); ?> (<?php echo htmlspecialchars($appt['dept_name']); ?>)</p>
                    </div>
                    <div>
                        <span class="badge-status"><?php echo $appt['status']; ?></span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; font-size: 0.95rem;">
                    <div><strong>📅 Date:</strong> <?php echo $appt['appointment_date']; ?></div>
                    <div><strong>⏰ Time Slot:</strong> <?php echo date('g:i A', strtotime($appt['appointment_time'])); ?></div>
               <div><strong>🎫 Check-In Code:</strong> <code><?php echo htmlspecialchars($appt['qr_code'] ?? 'N/A'); ?></code></div>
                </div>

                <div style="margin-top: 15px;">
                    <strong>Reason / Symptoms:</strong>
                    <p style="color: var(--text-light); margin-top: 3px;"><?php echo nl2br(htmlspecialchars($appt['reason'])); ?></p>
                </div>

                
                <?php if ($appt['appointment_date'] === date('Y-m-d') && !in_array($appt['status'], ['Completed', 'Cancelled', 'No-Show'])): ?>
                    <div class="wait-box">
                        <div>
                            <span style="font-size: 1.1rem; font-weight: bold; color: #166534;">⏱️ Live Wait-Time Estimate</span>
                            <p style="font-size: 0.85rem; color: #15803d; margin-top: 2px;">Based on current consultation progress and queue position.</p>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 1.2rem; font-weight: bold; color: #166534;"><?php echo $metrics['wait_time']; ?></div>
                            <small style="color: #15803d;">Queue Position: #<?php echo $metrics['position']; ?></small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; else: ?>
            <div class="card text-center" style="padding: 40px;">
                <p style="color: var(--text-light); margin-bottom: 15px;">You have no appointments booked yet.</p>
                <a href="book_appointment.php" class="btn" style="display: inline-block; width: auto; padding: 10px 20px;">Book Your First Appointment</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>