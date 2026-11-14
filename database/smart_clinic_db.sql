SET
  SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

START TRANSACTION;

SET
  time_zone = "+00:00";

CREATE TABLE
  `appointments` (
    `appointment_id` int (11) NOT NULL,
    `patient_id` int (11) NOT NULL,
    `doctor_id` int (11) NOT NULL,
    `appointment_date` date NOT NULL,
    `appointment_time` time NOT NULL,
    `booking_type` enum ('online', 'walk_in') DEFAULT 'online',
    `status` enum (
      'Booked',
      'Confirmed',
      'Arrived',
      'Checked-In',
      'Waiting',
      'In Consultation',
      'Completed',
      'Cancelled',
      'No-Show'
    ) DEFAULT 'Booked',
    `qr_token` varchar(64) NOT NULL,
    `queue_number` int (11) DEFAULT NULL,
    `reason` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO
  `appointments` (
    `appointment_id`,
    `patient_id`,
    `doctor_id`,
    `appointment_date`,
    `appointment_time`,
    `booking_type`,
    `status`,
    `qr_token`,
    `queue_number`,
    `reason`,
    `created_at`
  )
VALUES
  (
    1,
    7,
    1,
    '2026-11-12',
    '14:22:00',
    'online',
    'Completed',
    'QR-9F66D0E0F199',
    1,
    'Health Issues',
    '2026-11-12 14:29:28'
  ),
  (
    2,
    7,
    1,
    '8888-08-08',
    '20:08:00',
    'online',
    'Cancelled',
    'QR-3FFB14637D1C',
    NULL,
    'To Check my dental Issues',
    '2026-11-12 14:31:53'
  ),
  (
    3,
    8,
    1,
    '2026-11-12',
    '15:39:59',
    'walk_in',
    '',
    'QR-WALK-B625DD06DA',
    2,
    'Headache',
    '2026-11-12 14:39:59'
  );

CREATE TABLE
  `audit_logs` (
    `log_id` int (11) NOT NULL,
    `user_id` int (11) DEFAULT NULL,
    `action` varchar(255) NOT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO
  `audit_logs` (
    `log_id`,
    `user_id`,
    `action`,
    `ip_address`,
    `created_at`
  )
VALUES
  (
    1,
    4,
    'User logged in successfully (admin)',
    '::1',
    '2026-11-14 06:40:49'
  ),
  (
    2,
    4,
    'User logged in successfully (admin)',
    '::1',
    '2026-11-14 06:41:10'
  ),
  (
    3,
    6,
    'User logged in successfully (doctor)',
    '::1',
    '2026-11-14 06:42:48'
  ),
  (
    4,
    4,
    'User logged in successfully (admin)',
    '::1',
    '2026-11-14 06:43:11'
  ),
  (
    5,
    5,
    'User logged in successfully (receptionist)',
    '::1',
    '2026-11-14 06:43:44'
  ),
  (
    6,
    7,
    'User logged in successfully (patient)',
    '::1',
    '2026-11-14 08:03:21'
  );

CREATE TABLE
  `consultations` (
    `consultation_id` int (11) NOT NULL,
    `appointment_id` int (11) NOT NULL,
    `symptoms` text NOT NULL,
    `diagnosis` text NOT NULL,
    `prescription` text DEFAULT NULL,
    `follow_up_date` date DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO
  `consultations` (
    `consultation_id`,
    `appointment_id`,
    `symptoms`,
    `diagnosis`,
    `prescription`,
    `follow_up_date`,
    `created_at`
  )
VALUES
  (
    1,
    1,
    'fever',
    'Diagnosis',
    '1*2',
    '2222-02-22',
    '2026-11-12 14:41:44'
  );

CREATE TABLE
  `departments` (
    `dept_id` int (11) NOT NULL,
    `dept_name` varchar(100) NOT NULL,
    `description` text DEFAULT NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO
  `departments` (`dept_id`, `dept_name`, `description`)
VALUES
  (
    1,
    'General Medicine',
    'Primary care and general consultations'
  ),
  (
    2,
    'Pediatrics',
    'Medical care for infants, children, and adolescents'
  ),
  (
    3,
    'Dental Care',
    'Oral health and dental procedures'
  );

CREATE TABLE
  `doctors` (
    `doctor_id` int (11) NOT NULL,
    `user_id` int (11) NOT NULL,
    `dept_id` int (11) NOT NULL,
    `specialty` varchar(100) NOT NULL,
    `consultation_fee` decimal(10, 2) NOT NULL DEFAULT 1000.00,
    `max_daily_patients` int (11) NOT NULL DEFAULT 15
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO
  `doctors` (
    `doctor_id`,
    `user_id`,
    `dept_id`,
    `specialty`,
    `consultation_fee`,
    `max_daily_patients`
  )
VALUES
  (1, 6, 3, 'Cardiogist', 1000.00, 20);

CREATE TABLE
  `doctor_leaves` (
    `leave_id` int (11) NOT NULL,
    `doctor_id` int (11) NOT NULL,
    `start_date` date NOT NULL,
    `end_date` date NOT NULL,
    `reason` varchar(255) NOT NULL,
    `status` enum ('Approved', 'Pending', 'Rejected') DEFAULT 'Approved',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE
  `doctor_schedules` (
    `schedule_id` int (11) NOT NULL,
    `doctor_id` int (11) NOT NULL,
    `day_of_week` enum (
      'Monday',
      'Tuesday',
      'Wednesday',
      'Thursday',
      'Friday',
      'Saturday',
      'Sunday'
    ) NOT NULL,
    `start_time` time NOT NULL,
    `end_time` time NOT NULL,
    `slot_duration_mins` int (11) NOT NULL DEFAULT 30,
    `is_available` tinyint (1) DEFAULT 1
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE
  `notifications` (
    `notif_id` int (11) NOT NULL,
    `user_id` int (11) NOT NULL,
    `message` text NOT NULL,
    `is_read` tinyint (1) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE
  `payments` (
    `payment_id` int (11) NOT NULL,
    `appointment_id` int (11) NOT NULL,
    `amount` decimal(10, 2) NOT NULL,
    `payment_method` enum ('Cash', 'M-Pesa') NOT NULL,
    `transaction_ref` varchar(50) NOT NULL,
    `receipt_number` varchar(50) NOT NULL,
    `payment_status` enum ('paid', 'pending') DEFAULT 'paid',
    `paid_at` timestamp NOT NULL DEFAULT current_timestamp()
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO
  `payments` (
    `payment_id`,
    `appointment_id`,
    `amount`,
    `payment_method`,
    `transaction_ref`,
    `receipt_number`,
    `payment_status`,
    `paid_at`
  )
VALUES
  (
    1,
    1,
    1000.00,
    'M-Pesa',
    'MP-13718468',
    'REC-B010AE8706',
    'paid',
    '2026-11-12 14:41:44'
  );

CREATE TABLE
  `users` (
    `user_id` int (11) NOT NULL,
    `full_name` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL,
    `phone` varchar(20) NOT NULL,
    `password_hash` varchar(255) NOT NULL,
    `role` enum ('admin', 'doctor', 'receptionist', 'patient') NOT NULL,
    `status` enum ('active', 'inactive') DEFAULT 'active',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `profile_picture` varchar(255) DEFAULT 'default.png'
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO
  `users` (
    `user_id`,
    `full_name`,
    `email`,
    `phone`,
    `password_hash`,
    `role`,
    `status`,
    `created_at`,
    `profile_picture`
  )
VALUES
  (
    4,
    'Admin',
    'administrator@clinic.com',
    '070000000',
    '$2y$10$dlpiDuDrTlxJAOVm7Wug7.6y2o2QCv.BF.agdZnz9i2RQ1ZJXc05S',
    'admin',
    'active',
    '2026-11-12 14:09:50',
    '1794635767_WhatsApp Image 2026-10-18 at 12.45.13.jpeg'
  ),
  (
    5,
    'Receptionist',
    'receptionist@clinic.com',
    '0711111111',
    '$2y$10$XgujfdB7Bfw5FDhp00K0j.MArVxke1U3oLGdP4tpJ5r2hOrVKzPyK',
    'receptionist',
    'active',
    '2026-11-12 14:16:38',
    '1794635874_WhatsApp Image 2026-09-19 at 20.07.39.jpeg'
  ),
  (
    6,
    'Doctor',
    'doctor@clinic.com',
    '0722222222',
    '$2y$10$jwltpaDGWTJtfc33/YTmDebi3g6DDlhIck8NmgTW6AuadY4Ez3Mia',
    'doctor',
    'active',
    '2026-11-12 14:24:56',
    '1794635816_WhatsApp Image 2026-09-19 at 20.07.38.jpeg'
  ),
  (
    7,
    'Patient',
    'patient@clinic.com',
    '0733333333',
    '$2y$10$hpT6bLrzuc7035QsBGcZNex/3gONplNgaNimCg1lF.zuphVZUNGd6',
    'patient',
    'active',
    '2026-11-12 14:27:27',
    'default.png'
  ),
  (
    8,
    'Patient2',
    'patient1@clinic.com',
    '0744444444',
    '$2y$10$BYmpjJQaL6MXF./GIlADC.VaRmVkaTald/jSKPhNzkhFAIesM2Isu',
    'patient',
    'active',
    '2026-11-12 14:39:59',
    'default.png'
  );

CREATE TABLE
  `waiting_list` (
    `wait_id` int (11) NOT NULL,
    `patient_id` int (11) NOT NULL,
    `doctor_id` int (11) NOT NULL,
    `preferred_date` date NOT NULL,
    `status` enum ('waiting', 'offered', 'fulfilled', 'expired') DEFAULT 'waiting',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

ALTER TABLE `appointments` ADD PRIMARY KEY (`appointment_id`),
ADD UNIQUE KEY `qr_token` (`qr_token`),
ADD KEY `patient_id` (`patient_id`),
ADD KEY `idx_doc_date_time` (
  `doctor_id`,
  `appointment_date`,
  `appointment_time`
);

ALTER TABLE `audit_logs` ADD PRIMARY KEY (`log_id`);

ALTER TABLE `consultations` ADD PRIMARY KEY (`consultation_id`),
ADD UNIQUE KEY `appointment_id` (`appointment_id`);

ALTER TABLE `departments` ADD PRIMARY KEY (`dept_id`),
ADD UNIQUE KEY `dept_name` (`dept_name`);

ALTER TABLE `doctors` ADD PRIMARY KEY (`doctor_id`),
ADD UNIQUE KEY `user_id` (`user_id`),
ADD KEY `dept_id` (`dept_id`);

ALTER TABLE `doctor_leaves` ADD PRIMARY KEY (`leave_id`),
ADD KEY `doctor_id` (`doctor_id`);

ALTER TABLE `doctor_schedules` ADD PRIMARY KEY (`schedule_id`),
ADD UNIQUE KEY `unique_doc_day` (`doctor_id`, `day_of_week`);

ALTER TABLE `notifications` ADD PRIMARY KEY (`notif_id`),
ADD KEY `user_id` (`user_id`);

ALTER TABLE `payments` ADD PRIMARY KEY (`payment_id`),
ADD UNIQUE KEY `appointment_id` (`appointment_id`),
ADD UNIQUE KEY `receipt_number` (`receipt_number`);

ALTER TABLE `users` ADD PRIMARY KEY (`user_id`),
ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `waiting_list` ADD PRIMARY KEY (`wait_id`),
ADD KEY `patient_id` (`patient_id`),
ADD KEY `doctor_id` (`doctor_id`);

ALTER TABLE `appointments` MODIFY `appointment_id` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 4;

ALTER TABLE `audit_logs` MODIFY `log_id` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 7;

ALTER TABLE `consultations` MODIFY `consultation_id` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 2;

ALTER TABLE `departments` MODIFY `dept_id` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 4;

ALTER TABLE `doctors` MODIFY `doctor_id` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 2;

ALTER TABLE `doctor_leaves` MODIFY `leave_id` int (11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `doctor_schedules` MODIFY `schedule_id` int (11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `notifications` MODIFY `notif_id` int (11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `payments` MODIFY `payment_id` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 2;

ALTER TABLE `users` MODIFY `user_id` int (11) NOT NULL AUTO_INCREMENT,
AUTO_INCREMENT = 9;

ALTER TABLE `waiting_list` MODIFY `wait_id` int (11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `appointments` ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE;

ALTER TABLE `consultations` ADD CONSTRAINT `consultations_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE;

ALTER TABLE `doctors` ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
ADD CONSTRAINT `doctors_ibfk_2` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`);

ALTER TABLE `doctor_leaves` ADD CONSTRAINT `doctor_leaves_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE;

ALTER TABLE `doctor_schedules` ADD CONSTRAINT `doctor_schedules_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE;

ALTER TABLE `notifications` ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `payments` ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE;

ALTER TABLE `waiting_list` ADD CONSTRAINT `waiting_list_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
ADD CONSTRAINT `waiting_list_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE;

COMMIT;