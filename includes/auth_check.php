<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function check_auth($required_role = null) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit();
    }

    if ($required_role && $_SESSION['role'] !== $required_role) {
        switch ($_SESSION['role']) {
            case 'admin': header("Location: ../admin/dashboard.php"); break;
            case 'doctor': header("Location: ../doctor/dashboard.php"); break;
            case 'receptionist': header("Location: ../receptionist/dashboard.php"); break;
            case 'patient': header("Location: ../patient/dashboard.php"); break;
            default: header("Location: ../index.php");
        }
        exit();
    }
}
?>