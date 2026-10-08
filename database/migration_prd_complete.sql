-- Migration to sync database with 100% of PRD Section 11 & 15.3

-- 1. Alter rooms table to add inactive_until
ALTER TABLE `rooms` ADD COLUMN IF NOT EXISTS `inactive_until` date DEFAULT NULL AFTER `inactive_reason`;

-- 2. Alter bookings table to add needs_review
ALTER TABLE `bookings` ADD COLUMN IF NOT EXISTS `needs_review` tinyint(1) NOT NULL DEFAULT 0 AFTER `decided_at`;

-- 3. Create room_facilities (PRD line 951)
CREATE TABLE IF NOT EXISTS `room_facilities` (
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

-- 4. Create no_class_periods (PRD line 953)
CREATE TABLE IF NOT EXISTS `no_class_periods` (
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

-- 5. Create schedule_imports (PRD line 954)
CREATE TABLE IF NOT EXISTS `schedule_imports` (
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

-- 6. Insert settings defaults (PRD Section 15.3)
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
