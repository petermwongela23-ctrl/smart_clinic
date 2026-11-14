<?php
require_once '../config/db.php';
require_once '../includes/lang.php';
require_once '../includes/auth_check.php';
check_auth('admin');


$rev_stmt = $pdo->query("SELECT SUM(amount) FROM payments");
$total_revenue = $rev_stmt->fetchColumn() ?: 0;


$status_stmt = $pdo->query("SELECT status, COUNT(*) as count FROM appointments GROUP BY status");
$status_counts = $status_stmt->fetchAll(PDO::FETCH_KEY_PAIR);


$payments_stmt = $pdo->query("
    SELECT p.*, u.full_name as patient_name 
    FROM payments p 
    JOIN appointments a ON p.appointment_id = a.appointment_id
    JOIN users u ON a.patient_id = u.user_id 
    ORDER BY p.paid_at DESC 
    LIMIT 20
");
$payments = $payments_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <title>Financial & Clinic Reports</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .report-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
    </style>
</head>
<body>
    <div style="background: white; padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <div class="logo">📊 Admin Financial & Operational Reports</div>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_doctors.php">Manage Doctors</a>
            <a href="audit_logs.php">Audit Logs</a>
            <a href="reports.php" style="color: var(--primary);">Reports</a>
            <a href="profile.php">Profile</a>
            <a href="../logout.php" style="color: var(--danger);">Logout</a>
        </div>
    </div>

    <div class="container" style="margin-top: 30px;">
        <h2 style="margin-bottom: 20px;">Clinic Performance & Financial Summary</h2>

        <div class="report-grid">
            <div class="card" style="text-align: center;">
                <h3 style="font-size: 2rem; color: var(--success); margin-bottom: 5px;">KES <?php echo number_format($total_revenue, 2); ?></h3>
                <p style="color: var(--text-light);">Total Consultation Revenue</p>
            </div>
            <div class="card" style="text-align: center;">
                <h3 style="font-size: 2rem; color: var(--primary); margin-bottom: 5px;"><?php echo array_sum($status_counts); ?></h3>
                <p style="color: var(--text-light);">Total Lifetime Appointments</p>
            </div>
            <div class="card" style="text-align: center;">
                <h3 style="font-size: 2rem; color: #ca8a04; margin-bottom: 5px;"><?php echo $status_counts['completed'] ?? 0; ?></h3>
                <p style="color: var(--text-light);">Completed Consultations</p>
            </div>
        </div>

        <h3 style="margin-bottom: 15px;">Recent Payment Transactions</h3>
        <div class="card">
            <table class="table">
                <thead>
                    <tr>
                        <th>Receipt #</th>
                        <th>Patient Name</th>
                        <th>Method</th>
                        <th>Transaction Ref</th>
                        <th>Amount (KES)</th>
                        <th>Paid At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($payments) > 0): foreach ($payments as $pay): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($pay['receipt_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($pay['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($pay['payment_method']); ?></td>
                            <td><code><?php echo htmlspecialchars($pay['transaction_ref']); ?></code></td>
                            <td><strong><?php echo number_format($pay['amount'], 2); ?></strong></td>
                            <td><?php echo $pay['paid_at']; ?></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" class="text-center">No payment transactions recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>