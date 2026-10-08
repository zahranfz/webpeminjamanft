-- schema.sql
-- Struktur Database untuk Sistem Pengelolaan Ruangan FT Unsoed
-- Berdasarkan PRD versi 1.0-draft

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";

-- Tabel Pengaturan (FR-093)
CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Pengguna (FR-001 - FR-006)
CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('student','lecturer','manager','superadmin') NOT NULL,
  `identity_number` varchar(50) DEFAULT NULL COMMENT 'NIM/NIP/NIDN',
  `status` enum('pending_email','pending_approval','active','disabled','locked') NOT NULL DEFAULT 'pending_email',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `failed_login_attempts` int(11) NOT NULL DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Token Reset/Verifikasi
CREATE TABLE `user_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `type` enum('email_verify','password_reset') NOT NULL,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_user_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Gedung
CREATE TABLE `buildings` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Ruangan (FR-050)
CREATE TABLE `rooms` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `building_id` int(11) UNSIGNED NOT NULL,
  `floor` int(11) NOT NULL,
  `capacity` int(11) NOT NULL,
  `type` enum('classroom','laboratory','hall','meeting_room','other') NOT NULL,
  `can_be_booked` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `inactive_reason` varchar(255) DEFAULT NULL,
  `inactive_until` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `building_id` (`building_id`),
  CONSTRAINT `fk_rooms_building` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Fasilitas Ruangan (FR-051)
CREATE TABLE `room_facilities` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id` int(11) UNSIGNED NOT NULL,
  `type` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `condition` enum('good','damaged','lost','under_repair') NOT NULL DEFAULT 'good',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `fk_room_facilities_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Semester (FR-053)
CREATE TABLE `semesters` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Periode Tanpa Kelas (FR-053)
CREATE TABLE `no_class_periods` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `semester_id` int(11) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `semester_id` (`semester_id`),
  CONSTRAINT `fk_no_class_term` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Riwayat Impor Jadwal (FR-040 - FR-045)
CREATE TABLE `schedule_imports` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `semester_id` int(11) UNSIGNED NOT NULL,
  `filename` varchar(255) NOT NULL,
  `uploaded_by` bigint(20) UNSIGNED NOT NULL,
  `total_rows` int(11) NOT NULL DEFAULT 0,
  `valid_rows` int(11) NOT NULL DEFAULT 0,
  `status` enum('preview','committed','rolled_back','failed') NOT NULL DEFAULT 'preview',
  `mode` enum('append','replace') NOT NULL DEFAULT 'replace',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `committed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `semester_id` (`semester_id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `fk_import_semester` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`),
  CONSTRAINT `fk_import_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Jadwal Kelas (FR-040 - FR-044)
CREATE TABLE `class_schedules` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `semester_id` int(11) UNSIGNED NOT NULL,
  `room_id` int(11) UNSIGNED NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `class_name` varchar(10) NOT NULL,
  `lecturer_name` varchar(255) NOT NULL,
  `lecturer_ref` varchar(50) DEFAULT NULL,
  `lecturer_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `weekday` tinyint(4) NOT NULL COMMENT '1=Senin, 7=Minggu',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `attendees` int(11) DEFAULT NULL,
  `import_batch` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `semester_id` (`semester_id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `fk_schedule_semester` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`),
  CONSTRAINT `fk_schedule_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`),
  CONSTRAINT `fk_schedule_lecturer` FOREIGN KEY (`lecturer_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Sesi Kelas (Kehadiran / Check-in)
CREATE TABLE `class_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `schedule_id` bigint(20) UNSIGNED NOT NULL,
  `moved_to_room_id` int(11) UNSIGNED DEFAULT NULL,
  `session_date` date NOT NULL,
  `status` enum('checked_in','cancelled_by_lecturer','cancelled_by_manager','confirmed_held_by_manager','ongoing','moved') NOT NULL,
  `actual_start_time` time DEFAULT NULL,
  `checked_in_at` timestamp NULL DEFAULT NULL,
  `decided_by` bigint(20) UNSIGNED DEFAULT NULL,
  `note` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `schedule_date` (`schedule_id`, `session_date`),
  CONSTRAINT `fk_session_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `class_schedules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_session_moved_room` FOREIGN KEY (`moved_to_room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Laporan Ketidakhadiran Dosen (FR-061)
CREATE TABLE `absence_reports` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `schedule_id` bigint(20) UNSIGNED NOT NULL,
  `session_date` date NOT NULL,
  `reporter_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('active','void') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `schedule_date_reporter` (`schedule_id`, `session_date`, `reporter_id`),
  CONSTRAINT `fk_absence_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `class_schedules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_absence_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Peminjaman (FR-070 - FR-074, FR-079)
CREATE TABLE `bookings` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id` int(11) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `activity_type` enum('public_lecture','seminar','meeting','organization','other') NOT NULL,
  `activity_name` varchar(255) NOT NULL,
  `participants_count` int(11) NOT NULL,
  `contact_person` varchar(100) NOT NULL,
  `notes` text DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `status` enum('submitted','approved','rejected','cancelled','displaced') NOT NULL DEFAULT 'submitted',
  `manager_id` bigint(20) UNSIGNED DEFAULT NULL,
  `decision_notes` text DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `needs_review` tinyint(1) NOT NULL DEFAULT 0,
  `recurring_group_id` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `room_id` (`room_id`),
  KEY `user_id` (`user_id`),
  KEY `recurring_group_id` (`recurring_group_id`),
  CONSTRAINT `fk_booking_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`),
  CONSTRAINT `fk_booking_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Laporan Fasilitas (FR-080)
CREATE TABLE `facility_reports` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id` int(11) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `category` enum('damage','issue','loss') NOT NULL,
  `facility_name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `urgency` enum('normal','urgent') NOT NULL DEFAULT 'normal',
  `status` enum('new','verified','in_progress','resolved','rejected') NOT NULL DEFAULT 'new',
  `manager_id` bigint(20) UNSIGNED DEFAULT NULL,
  `manager_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `room_id` (`room_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_report_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`),
  CONSTRAINT `fk_report_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Foto Laporan Fasilitas
CREATE TABLE `facility_report_photos` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_id` bigint(20) UNSIGNED NOT NULL,
  `path` varchar(255) NOT NULL,
  `mime` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `report_id` (`report_id`),
  CONSTRAINT `fk_report_photos_report` FOREIGN KEY (`report_id`) REFERENCES `facility_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Dukungan Laporan (Upvotes)
CREATE TABLE `facility_report_supports` (
  `report_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  PRIMARY KEY (`report_id`, `user_id`),
  CONSTRAINT `fk_report_supports_report` FOREIGN KEY (`report_id`) REFERENCES `facility_reports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_report_supports_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Riwayat Status Laporan
CREATE TABLE `facility_report_history` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_id` bigint(20) UNSIGNED NOT NULL,
  `from_status` varchar(50) DEFAULT NULL,
  `to_status` varchar(50) NOT NULL,
  `actor_id` bigint(20) UNSIGNED NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `report_id` (`report_id`),
  CONSTRAINT `fk_report_history_report` FOREIGN KEY (`report_id`) REFERENCES `facility_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel Notifikasi
CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(50) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `status` enum('queued','sent','failed') NOT NULL DEFAULT 'queued',
  `attempts` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sent_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit Logs (FR-092)
CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` varchar(50) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default settings (PRD Section 15.3)
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`) VALUES
('grace_minutes', '15', 'Masa tenggang T sejak jam mulai (BR-006)'),
('absent_report_threshold', '3', 'Ambang N pelapor berbeda (BR-007)'),
('absent_reports_per_user_per_day', '5', 'Batas laporan kehadiran per mahasiswa per hari'),
('checkin_open_minutes_before', '15', 'Check-in dibuka sebelum jam mulai'),
('status_poll_seconds', '60', 'Interval pembaruan status di halaman'),
('booking_min_lead_minutes', '120', 'Selisih minimum pengajuan dari jam mulai (menit)'),
('booking_max_advance_days', '90', 'Batas pengajuan ke depan (hari)'),
('booking_max_duration_hours', '8', 'Durasi maksimum peminjaman (jam)'),
('operating_start', '07:00', 'Jam operasional peminjaman mulai'),
('operating_end', '21:00', 'Jam operasional peminjaman selesai'),
('operating_days', '1,2,3,4,5,6', 'Hari operasional (1 = Senin, 6 = Sabtu)'),
('max_submitted_bookings_student', '3', 'Batas permohonan submitted bersamaan untuk mahasiswa'),
('max_submitted_bookings_lecturer', '10', 'Batas permohonan submitted bersamaan untuk dosen'),
('login_max_attempts', '5', 'Batas percobaan login sebelum terkunci'),
('login_lockout_minutes', '15', 'Lama akun terkunci setelah gagal berkali-kali (menit)'),
('session_idle_minutes', '120', 'Batas sesi tidak aktif sebelum logout (menit)'),
('email_verify_hours', '24', 'Masa berlaku token verifikasi email (jam)'),
('reset_password_minutes', '60', 'Masa berlaku token reset password (menit)'),
('upload_max_mb_photo', '2', 'Batas ukuran file foto laporan (MB)'),
('upload_max_mb_pdf', '2', 'Batas ukuran file lampiran permohonan PDF (MB)'),
('import_max_mb', '5', 'Batas ukuran file impor jadwal CSV (MB)'),
('report_reopen_days', '7', 'Batas membuka kembali laporan selesai (hari)'),
('notification_max_attempts', '3', 'Percobaan ulang pengiriman email')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

COMMIT;
