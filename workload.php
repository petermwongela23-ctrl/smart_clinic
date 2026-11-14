<?php
require_once '../config/db.php';
require_once '../includes/lang.php';
require_once '../includes/auth_check.php';
check_auth('admin');


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_id'], $_POST['action'])) {
    $leave_id = $_POST['leave_id'];
    $action = $_POST['action']; 
    $pdo->prepare("UPDATE doctor_leaves SET status = ? WHERE leave_id = ?")->execute([$action, $leave_id]);
    header("Location: workload.php?updated=1");
    exit();
}


$workload_stmt = $pdo->query("
    SELECT d.doctor_id, u.full_name, dept.dept_name, d.specialty,
           COUNT(a.appointment_id) as today_count
    FROM doctors d
    JOIN users u ON d.user_id = u.user_id
    JOIN departments dept ON d.dept_id = dept.dept_id
    LEFT JOIN appointments a ON d.doctor_id = a.doctor_id 
         AND a.appointment_date = CURDATE() 
         AND a.status NOT IN ('Cancelled', 'No-Show')
    GROUP BY d.doctor_id
    ORDER BY today_count DESC
");
$doctors_workload = $workload_stmt->fetchAll();


$leaves_stmt = $pdo->query("
    SELECT l.*, u.full_name as doctor_name, dept.dept_name 
    FROM doctor_leaves l
    JOIN doctors d ON l.doctor_id = d.doctor_id
    JOIN users u ON d.user_id = u.user_id
    JOIN departments dept ON d.dept_id = dept.dept_id
    ORDER BY l.start_date DESC
");
$leaves = $leaves_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <title>Doctor Workload & Leave Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .workload-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .doc-card { background: white; padding: 20px; border-radius: 8px; border: 1px solid var(--border); position: relative; }
        .badge-load { position: absolute; top: 20px; right: 20px; padding: 5px 12px; border-radius: 20px; font-weight: bold; font-size: 0.85rem; }
        .load-normal { background: #dcfce7; color: #166534; }
        .load-heavy { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div style="background: white; padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <div class="logo">⚖️ Doctor Workload & Leave Management</div>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_doctors.php">Manage Doctors</a>
            <a href="workload.php" style="color: var(--primary);">Workload & Leaves</a>
            <a href="reports.php">Reports</a>
            <a href="profile.php">Profile</a>
            <a href="../logout.php" style="color: var(--danger);">Logout</a>
        </div>
    </div>

    <div class="container" style="margin-top: 30px; max-width: 1100px;">
        <h2 style="margin-bottom: 5px;">Today's Doctor Workload Distribution</h2>
        <p style="color: var(--text-light); margin-bottom: 20px;">Monitor appointment assignments to identify overloaded schedules and balance patient loads.</p>

        <div class="workload-grid">
            <?php foreach ($doctors_workload as $doc): ?>
                <div class="doc-card">
                    <?php 
                        $count = $doc['today_count'];
                        $load_class = $count > 5 ? 'load-heavy' : 'load-normal';
                        $load_label = $count > 5 ? 'High Workload ⚠️' : 'Balanced Schedule ✅';
                    ?>
                    <span class="badge-load <?php echo $load_class; ?>"><?php echo $load_label; ?></span>
                    <h3 style="color: var(--primary); margin-bottom: 4px;">Dr. <?php echo htmlspecialchars($doc['full_name']); ?></h3>
                    <p style="font-size: 0.85rem; color: var(--text-light); margin-bottom: 15px;"><?php echo htmlspecialchars($doc['specialty']); ?> (<?php echo htmlspecialchars($doc['dept_name']); ?>)</p>
                    <div style="font-size: 1.5rem; font-weight: bold; color: var(--text);">
                        <?php echo $count; ?> <span style="font-size: 0.9rem; font-weight: normal; color: var(--text-light);">appointments today</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <h3 style="margin-bottom: 15px;">Doctor Leave Requests & Status</h3>
        <div class="card" style="padding: 0; overflow: hidden;">
            <table class="table" style="margin-bottom: 0;">
                <thead>
                    <tr>
                        <th>Doctor</th>
                        <th>Department</th>
                        <th>Leave Dates</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($leaves) > 0): foreach ($leaves as $leave): ?>
                        <tr>
                            <td><strong>Dr. <?php echo htmlspecialchars($leave['doctor_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($leave['dept_name']); ?></td>
                            <td><?php echo $leave['start_date']; ?> to <?php echo $leave['end_date']; ?></td>
                            <td><?php echo htmlspecialchars($leave['reason']); ?></td>
                            <td>
                                <span class="badge" style="background: <?php echo $leave['status']=='Approved'?'#dcfce7':'($leave[status]==\'Pending\'?\'#fef9c3\':\'#fee2e2\');'; ?>">
                                    <?php echo $leave['status']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($leave['status'] === 'Pending'): ?>
                                    <form action="workload.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="leave_id" value="<?php echo $leave['leave_id']; ?>">
                                        <button type="submit" name="action" value="Approved" class="btn" style="padding: 4px 10px; font-size: 0.8rem; background: var(--success);">Approve</button>
                                        <button type="submit" name="action" value="Rejected" class="btn" style="padding: 4px 10px; font-size: 0.8rem; background: var(--danger);">Reject</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: var(--text-light); font-size: 0.85rem;">Processed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" class="text-center" style="padding: 20px;">No leave requests submitted.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>