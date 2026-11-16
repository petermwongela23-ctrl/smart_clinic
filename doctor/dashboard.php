<?php
require_once '../config/db.php';
require_once '../includes/lang.php';
require_once '../includes/auth_check.php';
check_auth('doctor');

$user_id = $_SESSION['user_id'];
$doc_stmt = $pdo->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
$doc_stmt->execute([$user_id]);
$doctor = $doc_stmt->fetch();
$doctor_id = $doctor['doctor_id'];

$today = date('Y-m-d');


$stmt = $pdo->prepare("
    SELECT a.*, p.full_name as patient_name, p.phone as patient_phone
    FROM appointments a
    JOIN users p ON a.patient_id = p.user_id
    WHERE a.doctor_id = ? AND a.appointment_date = ? AND a.status IN ('Checked-In', 'Waiting', 'In Consultation', 'Arrived')
    ORDER BY a.queue_number ASC
");
$stmt->execute([$doctor_id, $today]);
$queue_patients = $stmt->fetchAll();


$upcoming_stmt = $pdo->prepare("
    SELECT a.*, p.full_name as patient_name, p.phone as patient_phone
    FROM appointments a
    JOIN users p ON a.patient_id = p.user_id
    WHERE a.doctor_id = ? AND a.appointment_date >= ? AND a.status IN ('Booked', 'Confirmed')
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
");
$upcoming_stmt->execute([$doctor_id, $today]);
$upcoming_patients = $upcoming_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <title>Doctor Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div style="background: white; padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <div class="logo">🏥 Doctor Portal - Dr. <?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
        <div class="nav-links">
            <a href="dashboard.php" style="color: var(--primary);">My Queue</a>
            <a href="../staff/hub.php">Staff Hub</a>
            <a href="profile.php">Profile</a>
            <a href="../logout.php" style="color: var(--danger);">Logout</a>
        </div>
    </div>

    <div class="container" style="margin-top: 30px;">
        <h2 style="margin-bottom: 15px;">Today's Active Consultation Queue (<?php echo date('d M Y'); ?>)</h2>
        <div class="card" style="margin-bottom: 30px;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Queue #</th>
                        <th>Patient Name</th>
                        <th>Phone</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($queue_patients) > 0): foreach ($queue_patients as $qp): ?>
                        <tr>
                            <td><strong>#<?php echo $qp['queue_number']; ?></strong></td>
                            <td><?php echo htmlspecialchars($qp['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($qp['patient_phone']); ?></td>
                            <td><?php echo htmlspecialchars($qp['reason']); ?></td>
                            <td>
                                <!-- FIX: Normalize CSS class name to lowercase with dashes -->
                                <?php $badgeClass = strtolower(str_replace([' ', '_'], '-', $qp['status'])); ?>
                                <span class="badge badge-<?php echo $badgeClass; ?>"><?php echo $qp['status']; ?></span>
                            </td>
                            <td>
                                <a href="consultation.php?apt_id=<?php echo $qp['appointment_id']; ?>" class="btn" style="padding: 6px 12px; font-size: 0.85rem; width: auto;">Start Consultation</a>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" class="text-center">No patients checked into your queue yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h3 style="margin-bottom: 15px; color: var(--text-light);">Upcoming Booked Appointments (Awaiting Reception Check-In)</h3>
        <div class="card">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Patient Name</th>
                        <th>Phone</th>
                        <th>Reason</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($upcoming_patients) > 0): foreach ($upcoming_patients as $up): ?>
                        <tr>
                            <td><?php echo $up['appointment_date']; ?></td>
                            <td><?php echo $up['appointment_time']; ?></td>
                            <td><?php echo htmlspecialchars($up['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($up['patient_phone']); ?></td>
                            <td><?php echo htmlspecialchars($up['reason']); ?></td>
                            <td>
                                <?php $upBadgeClass = strtolower(str_replace([' ', '_'], '-', $up['status'])); ?>
                                <span class="badge badge-<?php echo $upBadgeClass; ?>"><?php echo $up['status']; ?></span>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" class="text-center">No upcoming booked appointments.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>