<?php
require_once '../config/db.php';
require_once '../includes/lang.php';
require_once '../includes/auth_check.php';
check_auth('doctor');

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';


$doc_stmt = $pdo->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
$doc_stmt->execute([$user_id]);
$doctor = $doc_stmt->fetch();
$doctor_id = $doctor['doctor_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = trim($_POST['reason']);

    if (empty($start_date) || empty($end_date) || empty($reason)) {
        $error = "Please fill in all leave details.";
    } elseif ($start_date > $end_date) {
        $error = "Start date cannot be after end date.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO doctor_leaves (doctor_id, start_date, end_date, reason, status) VALUES (?, ?, ?, ?, 'Pending')");
        $stmt->execute([$doctor_id, $start_date, $end_date, $reason]);
        $success = "Leave request submitted successfully for administrator approval.";
    }
}


$history_stmt = $pdo->prepare("SELECT * FROM doctor_leaves WHERE doctor_id = ? ORDER BY start_date DESC");
$history_stmt->execute([$doctor_id]);
$my_leaves = $history_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <title>Request Leave - Doctor Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div style="background: white; padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <div class="logo">🏖️ Doctor Leave Request Portal</div>
        <div class="nav-links">
            <a href="dashboard.php">My Queue</a>
            <a href="leave_request.php" style="color: var(--primary);">Request Leave</a>
            <a href="profile.php">Profile</a>
            <a href="../logout.php" style="color: var(--danger);">Logout</a>
        </div>
    </div>

    <div class="container" style="margin-top: 30px; max-width: 700px;">
        <div class="card" style="margin-bottom: 25px;">
            <h2 style="color: var(--primary); margin-bottom: 15px;">Submit Time-Off / Leave Request</h2>
            <?php if (!empty($error)): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
            <?php if (!empty($success)): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

            <form action="leave_request.php" method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Reason for Leave</label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="e.g., Annual leave, medical conference, personal day..." required></textarea>
                </div>
                <button type="submit" class="btn">Submit Leave Application</button>
            </form>
        </div>

        <div class="card">
            <h3 style="margin-bottom: 15px;">My Leave History</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Reason</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($my_leaves) > 0): foreach ($my_leaves as $ml): ?>
                        <tr>
                            <td><?php echo $ml['start_date']; ?> to <?php echo $ml['end_date']; ?></td>
                            <td><?php echo htmlspecialchars($ml['reason']); ?></td>
                            <td><strong><?php echo $ml['status']; ?></strong></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="3" class="text-center">No leave requests found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>