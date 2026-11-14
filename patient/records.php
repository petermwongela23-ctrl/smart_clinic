<?php
require_once '../config/db.php';
require_once '../includes/lang.php';
require_once '../includes/auth_check.php';
check_auth('patient');

$patient_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT c.*, a.appointment_date, u.full_name as doctor_name, d.specialty, dept.dept_name
    FROM consultations c
    JOIN appointments a ON c.appointment_id = a.appointment_id
    JOIN doctors d ON a.doctor_id = d.doctor_id
    JOIN users u ON d.user_id = u.user_id
    JOIN departments dept ON d.dept_id = dept.dept_id
    WHERE a.patient_id = ?
    ORDER BY a.appointment_date DESC
");
$stmt->execute([$patient_id]);
$records = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <title>My Medical Records & Prescriptions</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div style="background: white; padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <div class="logo">🏥 Patient Portal - Medical Records</div>
        <div class="nav-links">
            <a href="dashboard.php">My Appointments</a>
            <a href="records.php" style="color: var(--primary);">Medical Records</a>
            <a href="profile.php">Profile</a>
            <a href="../logout.php" style="color: var(--danger);">Logout</a>
        </div>
    </div>

    <div class="container" style="margin-top: 30px;">
        <h2 style="margin-bottom: 10px;">My Consultation & Prescription History</h2>
        <p style="color: var(--text-light); margin-bottom: 20px;">Review past doctor diagnoses, prescribed medications, and follow-up schedules.</p>

        <?php if (count($records) > 0): foreach ($records as $rec): ?>
            <div class="card" style="margin-bottom: 20px; border-left: 4px solid var(--primary);">
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border); padding-bottom: 10px; margin-bottom: 15px;">
                    <div>
                        <strong>Dr. <?php echo htmlspecialchars($rec['doctor_name']); ?></strong> 
                        <span style="color: var(--text-light); font-size: 0.85rem;">(<?php echo htmlspecialchars($rec['specialty']); ?> - <?php echo htmlspecialchars($rec['dept_name']); ?>)</span>
                    </div>
                    <div style="font-weight: 500; color: var(--primary);">Visit Date: <?php echo $rec['appointment_date']; ?></div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
                    <div>
                        <p style="font-size: 0.85rem; color: var(--text-light); text-transform: uppercase; font-weight: bold;">Symptoms Reported</p>
                        <p style="margin-top: 5px;"><?php echo nl2br(htmlspecialchars($rec['symptoms'])); ?></p>
                    </div>
                    <div>
                        <p style="font-size: 0.85rem; color: var(--text-light); text-transform: uppercase; font-weight: bold;">Doctor's Diagnosis</p>
                        <p style="margin-top: 5px;"><?php echo nl2br(htmlspecialchars($rec['diagnosis'])); ?></p>
                    </div>
                </div>

                <div style="background: #f8fafc; padding: 12px; border-radius: 6px; margin-bottom: 10px;">
                    <p style="font-size: 0.85rem; color: var(--primary); text-transform: uppercase; font-weight: bold;">Prescription / Medication</p>
                    <p style="margin-top: 3px; font-weight: 500;"><?php echo nl2br(htmlspecialchars($rec['prescription'])); ?></p>
                </div>

                <?php if (!empty($rec['follow_up_date'])): ?>
                    <p style="font-size: 0.9rem; color: #ca8a04;"><strong>⚠️ Follow-Up Scheduled:</strong> <?php echo $rec['follow_up_date']; ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; else: ?>
            <div class="card text-center"><p>No medical consultation records found yet.</p></div>
        <?php endif; ?>
    </div>
</body>
</html>