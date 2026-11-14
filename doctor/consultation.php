<?php
require_once '../config/db.php';
require_once '../includes/lang.php';
require_once '../includes/auth_check.php';
check_auth('doctor');

$error = '';
$success = '';
$apt_id = $_GET['apt_id'] ?? null;

if (!$apt_id) {
    header("Location: dashboard.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT a.*, p.full_name as patient_name, d.consultation_fee
    FROM appointments a
    JOIN users p ON a.patient_id = p.user_id
    JOIN doctors d ON a.doctor_id = d.doctor_id
    WHERE a.appointment_id = ?
");
$stmt->execute([$apt_id]);
$apt = $stmt->fetch();

if (!$apt) {
    die("Appointment not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $symptoms = trim($_POST['symptoms']);
    $diagnosis = trim($_POST['diagnosis']);
    $prescription = trim($_POST['prescription']);
    $follow_up = $_POST['follow_up_date'] ?: null;
    $payment_method = $_POST['payment_method'];

    if (empty($symptoms) || empty($diagnosis)) {
        $error = "Symptoms and diagnosis are required.";
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO consultations (appointment_id, symptoms, diagnosis, prescription, follow_up_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$apt_id, $symptoms, $diagnosis, $prescription, $follow_up]);

            $receipt_no = 'REC-' . strtoupper(bin2hex(random_bytes(5)));
            $trans_ref = ($payment_method === 'M-Pesa') ? 'MP-' . rand(10000000, 99999999) : 'CASH-' . rand(1000, 9999);
            
            $pay_stmt = $pdo->prepare("INSERT INTO payments (appointment_id, amount, payment_method, transaction_ref, receipt_number, payment_status) VALUES (?, ?, ?, ?, ?, 'paid')");
            $pay_stmt->execute([$apt_id, $apt['consultation_fee'], $payment_method, $trans_ref, $receipt_no]);

            $up_stmt = $pdo->prepare("UPDATE appointments SET status = 'completed' WHERE appointment_id = ?");
            $up_stmt->execute([$apt_id]);
            $pdo->commit();
            $success = "Consultation completed successfully! Payment recorded. Receipt: <strong>{$receipt_no}</strong>";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error recording consultation: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <title>Consultation Room</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div style="background: white; padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <div class="logo">🏥 Doctor Portal - Consultation</div>
        <div class="nav-links">
            <a href="dashboard.php">Back to Queue</a>
            <a href="../logout.php" style="color: var(--danger);">Logout</a>
        </div>
    </div>

    <div class="container" style="margin-top: 30px; max-width: 750px;">
        <div class="card">
            <h2 style="margin-bottom: 10px; color: var(--primary);">Patient: <?php echo htmlspecialchars($apt['patient_name']); ?></h2>
            <p style="color: var(--text-light); margin-bottom: 20px;">Initial Reason / Symptoms: <strong><?php echo htmlspecialchars($apt['reason']); ?></strong></p>

            <?php if (!empty($error)): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <p><a href="dashboard.php" class="btn" style="text-align: center; margin-top: 15px;">Return to Queue</a></p>
            <?php else: ?>
            <form action="consultation.php?apt_id=<?php echo $apt_id; ?>" method="POST">
                <div class="form-group"><label>Observed Symptoms</label><textarea name="symptoms" class="form-control" rows="3" required></textarea></div>
                <div class="form-group"><label>Diagnosis</label><textarea name="diagnosis" class="form-control" rows="3" required></textarea></div>
                <div class="form-group"><label>Prescription / Medication</label><textarea name="prescription" class="form-control" rows="3" placeholder="Medication dosage..."></textarea></div>
                <div class="form-group"><label>Follow-Up Date (Optional)</label><input type="date" name="follow_up_date" class="form-control"></div>
                <div class="form-group">
                    <label>Payment Method (Fee: KES <?php echo number_format($apt['consultation_fee'], 2); ?>)</label>
                    <select name="payment_method" class="form-control" required>
                        <option value="Cash">Cash</option>
                        <option value="M-Pesa">M-Pesa</option>
                    </select>
                </div>
                <button type="submit" class="btn">Complete Consultation & Record Payment</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>