<?php
require_once '../config/db.php';
require_once '../includes/lang.php';
require_once '../includes/auth_check.php';
check_auth('admin');


$logs_stmt = $pdo->query("
    SELECT l.*, u.full_name, u.email, u.role 
    FROM audit_logs l 
    LEFT JOIN users u ON l.user_id = u.user_id 
    ORDER BY l.created_at DESC 
    LIMIT 50
");
$logs = $logs_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <title>Security Audit Logs - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div style="background: white; padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <div class="logo">🔒 Security & Access Audit Trail</div>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_doctors.php">Manage Doctors</a>
            <a href="audit_logs.php" style="color: var(--primary);">Audit Logs</a>
            <a href="profile.php">Profile</a>
            <a href="../logout.php" style="color: var(--danger);">Logout</a>
        </div>
    </div>

    <div class="container" style="margin-top: 30px;">
        <h2 style="margin-bottom: 10px;">Information Assurance & System Logs</h2>
        <p style="color: var(--text-light); margin-bottom: 20px;">Monitoring user actions, authentication events, and administrative changes for security compliance.</p>

        <div class="card">
            <table class="table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Action Performed</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($logs) > 0): foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo $log['created_at']; ?></td>
                            <td><?php echo htmlspecialchars($log['full_name'] ?? 'Guest'); ?> (<?php echo htmlspecialchars($log['email'] ?? 'N/A'); ?>)</td>
                            <td><span class="badge"><?php echo htmlspecialchars($log['role'] ?? 'system'); ?></span></td>
                            <td><?php echo htmlspecialchars($log['action']); ?></td>
                            <td><code><?php echo $log['ip_address']; ?></code></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="5" class="text-center">No security logs recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>