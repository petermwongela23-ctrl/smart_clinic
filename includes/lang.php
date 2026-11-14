<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'sw'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit();
}

$translations = [
    'en' => [
        'app_title' => 'Smart Clinic System',
        'login' => 'Login',
        'register' => 'Register',
        'email' => 'Email Address',
        'password' => 'Password',
        'full_name' => 'Full Name',
        'phone' => 'Phone Number',
        'role' => 'Role',
        'patient' => 'Patient',
        'doctor' => 'Doctor',
        'receptionist' => 'Receptionist',
        'admin' => 'Administrator',
        'login_btn' => 'Sign In',
        'register_btn' => 'Create Account',
        'no_account' => "Don't have an account?",
        'has_account' => 'Already have an account?',
        'welcome' => 'Welcome to Smart Clinic',
    ],
    'sw' => [
        'app_title' => 'Mfumo wa Kliniki Bora',
        'login' => 'Ingia',
        'register' => 'Jisajili',
        'email' => 'Barua pepe',
        'password' => 'Nenosiri',
        'full_name' => 'Jina Kamili',
        'phone' => 'Namba ya Simu',
        'role' => 'Nafasi',
        'patient' => 'Mgonjwa',
        'doctor' => 'Daktari',
        'receptionist' => 'Mhudumu wa Mapokezi',
        'admin' => 'Msimamizi',
        'login_btn' => 'Ingia Kwenye Mfumo',
        'register_btn' => 'Fungua Akaunti',
        'no_account' => 'Huna akaunti?',
        'has_account' => 'Una akaunti tayari?',
        'welcome' => 'Karibu kwenye Kliniki Bora',
    ]
];

function __($key) {
    global $translations;
    $lang = $_SESSION['lang'];
    return $translations[$lang][$key] ?? $key;
}
?>