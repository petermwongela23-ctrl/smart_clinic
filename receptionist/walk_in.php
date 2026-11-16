<?php
require_once '../config/db.php';
require_once '../includes/lang.php';
require_once '../includes/auth_check.php';
check_auth('receptionist');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']) ?: 'walkin_' . time() . '@clinic.com';
    $doctor_id = $_POST['doctor_id'];
    $reason = trim($_POST['reason']);
    $today = date('Y-m-d');
    $now = date('H:i:s');

    if (empty($full_name) || empty($phone) || empty($doctor_id)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $pdo->beginTransaction();
            $password_hash = password_hash('Walkin@123', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'patient')");
            $stmt->execute([$full_name, $email, $phone, $password_hash]);
            $patient_id = $pdo->lastInsertId();

            $q_stmt = $pdo->prepare("SELECT MAX(queue_number) FROM appointments WHERE appointment_date = ?");
            $q_stmt->execute([$today]);
            $max_q = $q_stmt->fetchColumn();
            $next_q = $max_q ? $max_q + 1 : 1;

            $qr_token = 'QR-WALK-' . strtoupper(bin2hex(random_bytes(5)));
            
            // FIX: Changed 'checked_in' to 'Checked-In' to match the database ENUM
            $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, booking_type, status, qr_token, queue_number, reason) VALUES (?, ?, ?, ?, 'walk_in', 'Checked-In', ?, ?, ?)");
            $stmt->execute([$patient_id, $doctor_id, $today, $now, $qr_token, $next_q, $reason]);
            
            $pdo->commit();
            $success = "Walk-in patient registered successfully! Assigned Queue Number: #{$next_q}";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error registering walk-in: " . $e->getMessage();
        }
    }
}

$doctors = $pdo->query("
    SELECT d.doctor_id, d.specialty, u.full_name, dept.dept_name 
    FROM doctors d 
    JOIN users u ON d.user_id = u.user_id 
    JOIN departments dept ON d.dept_id = dept.dept_id
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <title>Register Walk-In Patient</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div style="background: white; padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <div class="logo">🏥 Receptionist Portal - Walk-In Registration</div>
        <div class="nav-links">
            <a href="dashboard.php">Live Queue</a>
            <a href="../logout.php" style="color: var(--danger);">Logout</a>
        </div>
    </div>

    <div class="container" style="margin-top: 30px; max-width: 650px;">
        <div class="card">
            <h2 style="margin-bottom: 20px; color: var(--primary);">Register Walk-In Patient</h2>
            <?php if (!empty($error)): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
            <?php if (!empty($success)): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
            <form action="walk_in.php" method="POST">
                <div class="form-group"><label>Patient Full Name</label><input type="text" name="full_name" class="form-control" required></div>
                <div class="form-group"><label>Phone Number</label><input type="text" name="phone" class="form-control" required placeholder="0712345678"></div>
                <div class="form-group"><label>Email (Optional)</label><input type="email" name="email" class="form-control" placeholder="patient@example.com"></div>
                <div class="form-group">
                    <label>Assign Doctor</label>
                    <select name="doctor_id" class="form-control" required>
                        <option value="">-- Choose Doctor --</option>
                        <?php foreach ($doctors as $doc): ?>
                            <option value="<?php echo $doc['doctor_id']; ?>">
                                Dr. <?php echo htmlspecialchars($doc['full_name']); ?> (<?php echo htmlspecialchars($doc['specialty']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Reason for Visit / Symptoms</label><textarea name="reason" class="form-control" rows="3" placeholder="Symptoms..."></textarea></div>
                <button type="submit" class="btn">Register & Issue Queue #</button>
            </form>
        </div>
    </div>
</body>
</html>