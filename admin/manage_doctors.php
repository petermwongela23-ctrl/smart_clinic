<?php
require_once '../config/db.php';
require_once '../includes/lang.php';
require_once '../includes/auth_check.php';
check_auth('admin');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_doctor'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $dept_id = $_POST['dept_id'];
    $specialty = trim($_POST['specialty']);
    $fee = $_POST['consultation_fee'];
    $max_patients = $_POST['max_daily_patients'];

    if (empty($full_name) || empty($email) || empty($password) || empty($dept_id) || empty($specialty)) {
        $error = "All required fields must be filled.";
    } else {
        try {
            $pdo->beginTransaction();
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'doctor')");
            $stmt->execute([$full_name, $email, $phone, $password_hash]);
            $user_id = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO doctors (user_id, dept_id, specialty, consultation_fee, max_daily_patients) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $dept_id, $specialty, $fee, $max_patients]);
            $pdo->commit();
            $success = "Doctor added successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error adding doctor: " . $e->getMessage();
        }
    }
}

$departments = $pdo->query("SELECT * FROM departments")->fetchAll();
$doctors = $pdo->query("
    SELECT d.*, u.full_name, u.email, u.phone, dept.dept_name 
    FROM doctors d 
    JOIN users u ON d.user_id = u.user_id 
    JOIN departments dept ON d.dept_id = dept.dept_id
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <title>Manage Doctors</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div style="background: white; padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <div class="logo">🏥 Admin Portal - Manage Doctors</div>
      <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_doctors.php" style="color: var(--primary);">Manage Doctors</a>
            <a href="profile.php">Profile</a>
            <a href="../logout.php" style="color: var(--danger);">Logout</a>
        </div>
    </div>

    <div class="container" style="margin-top: 30px;">
        <h2 style="margin-bottom: 20px;">Doctor Management</h2>
        <?php if (!empty($error)): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
        <?php if (!empty($success)): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

        <div class="card" style="margin-bottom: 30px;">
            <h3>Add New Doctor</h3>
            <form action="manage_doctors.php" method="POST" style="margin-top: 15px;">
                <input type="hidden" name="add_doctor" value="1">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group"><label>Full Name</label><input type="text" name="full_name" class="form-control" required></div>
                    <div class="form-group"><label>Email Address</label><input type="email" name="email" class="form-control" required></div>
                    <div class="form-group"><label>Phone Number</label><input type="text" name="phone" class="form-control" required placeholder="0712345678"></div>
                    <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control" required></div>
                    <div class="form-group">
                        <label>Department</label>
                        <select name="dept_id" class="form-control" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['dept_id']; ?>"><?php echo htmlspecialchars($dept['dept_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Specialty</label><input type="text" name="specialty" class="form-control" required placeholder="e.g. Cardiologist"></div>
                    <div class="form-group"><label>Consultation Fee (KES)</label><input type="number" step="0.01" name="consultation_fee" class="form-control" value="1000.00" required></div>
                    <div class="form-group"><label>Max Daily Patients</label><input type="number" name="max_daily_patients" class="form-control" value="15" required></div>
                </div>
                <button type="submit" class="btn" style="margin-top: 15px;">Save Doctor</button>
            </form>
        </div>

        <div class="card">
            <h3>Registered Doctors</h3>
            <table class="table">
                <thead><tr><th>Name</th><th>Email</th><th>Department</th><th>Specialty</th><th>Fee (KES)</th><th>Max Patients</th></tr></thead>
                <tbody>
                    <?php if (count($doctors) > 0): foreach ($doctors as $doc): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($doc['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($doc['email']); ?></td>
                            <td><?php echo htmlspecialchars($doc['dept_name']); ?></td>
                            <td><?php echo htmlspecialchars($doc['specialty']); ?></td>
                            <td><?php echo number_format($doc['consultation_fee'], 2); ?></td>
                            <td><?php echo $doc['max_daily_patients']; ?></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" class="text-center">No doctors registered yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>