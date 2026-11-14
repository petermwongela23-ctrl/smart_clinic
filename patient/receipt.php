<?php
require_once '../config/db.php';
require_once '../includes/lang.php';
require_once '../includes/auth_check.php';
check_auth('patient');

$apt_id = $_GET['apt_id'] ?? null;
$patient_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT a.*, p.full_name as patient_name, p.phone as patient_phone,
           u.full_name as doctor_name, d.specialty, dept.dept_name,
           c.symptoms, c.diagnosis, c.prescription, c.follow_up_date,
           pay.amount, pay.payment_method, pay.transaction_ref, pay.receipt_number, pay.paid_at
    FROM appointments a
    JOIN users p ON a.patient_id = p.user_id
    JOIN doctors d ON a.doctor_id = d.doctor_id
    JOIN users u ON d.user_id = u.user_id
    JOIN departments dept ON d.dept_id = dept.dept_id
    LEFT JOIN consultations c ON a.appointment_id = c.appointment_id
    LEFT JOIN payments pay ON a.appointment_id = pay.appointment_id
    WHERE a.appointment_id = ? AND a.patient_id = ?
");
$stmt->execute([$apt_id, $patient_id]);
$data = $stmt->fetch();

if (!$data) {
    die("Receipt not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt - <?php echo htmlspecialchars($data['receipt_number']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        @media print { .no-print { display: none; } body { background: white; } .card { border: none; box-shadow: none; } }
    </style>
</head>
<body>
    <div class="container" style="margin-top: 30px; max-width: 700px;">
        <div class="card">
            <div style="display: flex; justify-content: space-between; border-bottom: 2px solid var(--primary); padding-bottom: 15px; margin-bottom: 20px;">
                <div>
                    <h2 style="color: var(--primary);">🏥 Smart Clinic System</h2>
                    <p style="color: var(--text-light); font-size: 0.9rem;">Official Medical & Payment Receipt</p>
                </div>
                <div style="text-align: right;">
                    <p><strong>Receipt #:</strong> <?php echo htmlspecialchars($data['receipt_number']); ?></p>
                    <p style="font-size: 0.85rem; color: var(--text-light);">Date: <?php echo $data['appointment_date']; ?></p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                <div>
                    <p><strong>Patient:</strong> <?php echo htmlspecialchars($data['patient_name']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($data['patient_phone']); ?></p>
                </div>
                <div>
                    <p><strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($data['doctor_name']); ?></p>
                    <p><strong>Department:</strong> <?php echo htmlspecialchars($data['dept_name']); ?></p>
                </div>
            </div>

            <div style="background: #f8fafc; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                <h4 style="margin-bottom: 10px; color: var(--primary);">Clinical Summary</h4>
                <p><strong>Diagnosis:</strong> <?php echo nl2br(htmlspecialchars($data['diagnosis'])); ?></p>
                <p style="margin-top: 8px;"><strong>Prescription:</strong> <?php echo nl2br(htmlspecialchars($data['prescription'])); ?></p>
                <?php if ($data['follow_up_date']): ?>
                    <p style="margin-top: 8px;"><strong>Follow-Up Date:</strong> <?php echo $data['follow_up_date']; ?></p>
                <?php endif; ?>
            </div>

            <table class="table" style="margin-bottom: 20px;">
                <thead><tr><th>Description</th><th>Payment Method</th><th>Ref #</th><th>Amount (KES)</th></tr></thead>
                <tbody>
                    <tr>
                        <td>Medical Consultation Fee</td>
                        <td><?php echo htmlspecialchars($data['payment_method']); ?></td>
                        <td><code><?php echo htmlspecialchars($data['transaction_ref']); ?></code></td>
                        <td><strong><?php echo number_format($data['amount'], 2); ?></strong></td>
                    </tr>
                </tbody>
            </table>

            <div class="no-print" style="display: flex; gap: 10px; justify-content: flex-end;">
                <a href="dashboard.php" class="btn" style="background: var(--text-light); width: auto;">Back</a>
                <button onclick="window.print()" class="btn" style="width: auto;">🖨️ Print Receipt</button>
            </div>
        </div>
    </div>
</body>
</html>