<?php
require_once '../config/db.php';
require_once '../includes/lang.php';
require_once '../includes/auth_check.php';
check_auth('patient');

$patient_id = $_SESSION['user_id'];
$error = '';
$success = '';


$doctors_stmt = $pdo->query("
    SELECT d.doctor_id, d.specialty, d.consultation_fee, u.full_name, dept.dept_name 
    FROM doctors d 
    JOIN users u ON d.user_id = u.user_id 
    JOIN departments dept ON d.dept_id = dept.dept_id
    WHERE u.status = 'active'
    ORDER BY dept.dept_name ASC, u.full_name ASC
");
$doctors = $doctors_stmt->fetchAll();


$all_slots = [
    '08:00:00', '08:30:00', '09:00:00', '09:30:00',
    '10:00:00', '10:30:00', '11:00:00', '11:30:00',
    '12:00:00', '14:00:00', '14:30:00', '15:00:00',
    '15:30:00', '16:00:00', '16:30:00'
];

$selected_doctor_id = $_GET['doctor_id'] ?? ($_POST['doctor_id'] ?? '');
$selected_date = $_GET['date'] ?? ($_POST['appointment_date'] ?? date('Y-m-d'));
$available_slots = [];
$selected_doctor_info = null;
$is_on_leave = false;


if (!empty($selected_doctor_id) && !empty($selected_date)) {
    
    $doc_info_stmt = $pdo->prepare("
        SELECT d.*, u.full_name, dept.dept_name 
        FROM doctors d 
        JOIN users u ON d.user_id = u.user_id 
        JOIN departments dept ON d.dept_id = dept.dept_id 
        WHERE d.doctor_id = ?
    ");
    $doc_info_stmt->execute([$selected_doctor_id]);
    $selected_doctor_info = $doc_info_stmt->fetch();

    
    $leave_check = $pdo->prepare("
        SELECT COUNT(*) FROM doctor_leaves 
        WHERE doctor_id = ? AND ? BETWEEN start_date AND end_date AND status = 'Approved'
    ");
    $leave_check->execute([$selected_doctor_id, $selected_date]);
    $is_on_leave = $leave_check->fetchColumn() > 0;

    if (!$is_on_leave) {
        
        $booked_stmt = $pdo->prepare("
            SELECT appointment_time FROM appointments 
            WHERE doctor_id = ? AND appointment_date = ? AND status NOT IN ('Cancelled', 'No-Show')
        ");
        $booked_stmt->execute([$selected_doctor_id, $selected_date]);
        $booked_times = $booked_stmt->fetchAll(PDO::FETCH_COLUMN);

        
        $current_time = date('H:i:s');
        foreach ($all_slots as $slot) {
            if (!in_array($slot, $booked_times)) {
                if ($selected_date > date('Y-m-d') || ($selected_date === date('Y-m-d') && $slot > $current_time)) {
                    $available_slots[] = $slot;
                }
            }
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    $doctor_id = $_POST['doctor_id'];
    $appt_date = $_POST['appointment_date'];
    $appt_time = $_POST['appointment_time'];
    $reason = trim($_POST['reason']);

    if (empty($doctor_id) || empty($appt_date) || empty($appt_time)) {
        $error = "Please select a doctor, date, and an available time slot.";
    } else {
        try {
            $pdo->beginTransaction();

            
            $leave_check = $pdo->prepare("
                SELECT COUNT(*) FROM doctor_leaves 
                WHERE doctor_id = ? AND ? BETWEEN start_date AND end_date AND status = 'Approved'
            ");
            $leave_check->execute([$doctor_id, $appt_date]);
            if ($leave_check->fetchColumn() > 0) {
                $pdo->rollBack();
                $error = "Cannot book: This doctor is on approved leave on this date.";
            } else {
                
                $check = $pdo->prepare("
                    SELECT COUNT(*) FROM appointments 
                    WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? 
                    AND status NOT IN ('Cancelled', 'No-Show')
                    FOR UPDATE
                ");
                $check->execute([$doctor_id, $appt_date, $appt_time]);

                if ($check->fetchColumn() > 0) {
                    $pdo->rollBack();
                    $error = "Sorry, that time slot was just taken by another patient. Please pick another recommended slot below.";
                } else {
                    
                    $qr_token = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));

                    $insert = $pdo->prepare("
                        INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status, qr_code) 
                        VALUES (?, ?, ?, ?, ?, 'Booked', ?)
                    ");
                    $insert->execute([$patient_id, $doctor_id, $appt_date, $appt_time, $reason, $qr_token]);

                    
                    $pdo->prepare("INSERT INTO audit_logs (user_id, action, ip_address) VALUES (?, ?, ?)")
                        ->execute([$patient_id, "Booked smart appointment ($appt_date at $appt_time)", $_SERVER['REMOTE_ADDR']]);

                    $pdo->commit();
                    $success = "Appointment confirmed for $appt_date at " . date('g:i A', strtotime($appt_time)) . "! Check-In Code: $qr_token";
                }
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Booking error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <title>Smart Appointment Scheduling - Smart Clinic</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .slot-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(115px, 1fr)); gap: 10px; margin-top: 10px; }
        .slot-radio { display: none; }
        .slot-label { display: block; text-align: center; padding: 10px; border: 1px solid var(--primary); border-radius: 6px; cursor: pointer; font-weight: 500; color: var(--primary); transition: all 0.2s; }
        .slot-radio:checked + .slot-label { background: var(--primary); color: white; font-weight: bold; }
        .slot-label:hover { background: #e0f2fe; }
        .fee-box { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div style="background: white; padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <div class="logo">🏥 Smart Appointment Scheduler</div>
        <div class="nav-links">
            <a href="dashboard.php">My Appointments</a>
            <a href="book_appointment.php" style="color: var(--primary);">Book Appointment</a>
            <a href="records.php">Medical Records</a>
            <a href="profile.php">Profile</a>
            <a href="../logout.php" style="color: var(--danger);">Logout</a>
        </div>
    </div>

    <div class="container" style="margin-top: 30px; max-width: 750px;">
        <div class="card">
            <h2 style="color: var(--primary); margin-bottom: 5px;">📅 Smart Appointment Booking</h2>
            <p style="color: var(--text-light); margin-bottom: 20px;">Select a specialist and preferred date to view real-time available slots and transparent consultation fees.</p>

            <?php if (!empty($error)): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
            <?php if (!empty($success)): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

            
            <form action="book_appointment.php" method="GET" style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid var(--border); margin-bottom: 25px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px; align-items: end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Choose Specialist / Department</label>
                        <select name="doctor_id" class="form-control" required>
                            <option value="">-- Select Doctor --</option>
                            <?php foreach ($doctors as $doc): ?>
                                <option value="<?php echo $doc['doctor_id']; ?>" <?php echo $selected_doctor_id == $doc['doctor_id'] ? 'selected' : ''; ?>>
                                    Dr. <?php echo htmlspecialchars($doc['full_name']); ?> (<?php echo htmlspecialchars($doc['dept_name']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Preferred Date</label>
                        <input type="date" name="date" class="form-control" min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($selected_date); ?>" required>
                    </div>
                    <button type="submit" class="btn" style="width: auto; padding: 10px 18px;">Check Slots</button>
                </div>
            </form>

            
            <?php if ($selected_doctor_info): ?>
                <div class="fee-box">
                    <strong>👨‍⚕️ Specialist:</strong> Dr. <?php echo htmlspecialchars($selected_doctor_info['full_name']); ?> (<?php echo htmlspecialchars($selected_doctor_info['specialty']); ?>)<br>
                    <strong>💳 Standard Consultation Fee:</strong> KES <?php echo number_format($selected_doctor_info['consultation_fee'], 2); ?>
                </div>

                <?php if ($is_on_leave): ?>
                    <div class="alert alert-error">
                        <strong>Doctor on Leave:</strong> Dr. <?php echo htmlspecialchars($selected_doctor_info['full_name']); ?> is on approved leave on <?php echo htmlspecialchars($selected_date); ?>. Please select another date or choose a different specialist.
                    </div>
                <?php elseif (count($available_slots) > 0): ?>
                    <form action="book_appointment.php" method="POST">
                        <input type="hidden" name="doctor_id" value="<?php echo htmlspecialchars($selected_doctor_id); ?>">
                        <input type="hidden" name="appointment_date" value="<?php echo htmlspecialchars($selected_date); ?>">

                        <div class="form-group">
                            <label>Recommended Available Time Slots (<?php echo count($available_slots); ?> open):</label>
                            <div class="slot-grid">
                                <?php foreach ($available_slots as $index => $slot): ?>
                                    <div>
                                        <input type="radio" id="slot_<?php echo $index; ?>" name="appointment_time" value="<?php echo $slot; ?>" class="slot-radio" <?php echo $index === 0 ? 'checked' : ''; ?>>
                                        <label for="slot_<?php echo $index; ?>" class="slot-label">
                                            <?php echo date('g:i A', strtotime($slot)); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 20px;">
                            <label>Reason for Visit / Brief Symptoms</label>
                            <textarea name="reason" class="form-control" rows="3" placeholder="Briefly describe your symptoms or reason for consultation..." required></textarea>
                        </div>

                        <button type="submit" name="confirm_booking" class="btn">Confirm Smart Booking</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-error">
                        <strong>Fully Booked:</strong> Dr. <?php echo htmlspecialchars($selected_doctor_info['full_name']); ?> has no remaining open slots on <?php echo htmlspecialchars($selected_date); ?>. Please select another date or choose a different specialist.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>