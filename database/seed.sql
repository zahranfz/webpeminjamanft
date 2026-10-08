-- seed.sql

-- 1. Tambah Gedung
INSERT INTO buildings (code, name) VALUES 
('A', 'Gedung A'),
('B', 'Gedung B'),
('C', 'Gedung C');

-- 2. Tambah Ruangan
INSERT INTO rooms (code, name, building_id, floor, capacity, type) VALUES 
('A-101', 'Ruang Kelas A-101', 1, 1, 40, 'classroom'),
('A-102', 'Ruang Kelas A-102', 1, 1, 40, 'classroom'),
('A-201', 'Laboratorium Komputer', 1, 2, 30, 'laboratory'),
('B-101', 'Ruang Seminar', 2, 1, 100, 'hall'),
('B-205', 'Ruang Kelas B-205', 2, 2, 60, 'classroom'),
('C-301', 'Ruang Sidang Teknik', 3, 3, 25, 'meeting_room');

-- 3. Tambah Akun Pengelola dan Superadmin
INSERT INTO users (name, email, password_hash, role, status) VALUES 
('Bapendik', 'bapendik@unsoed.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 'active'),
('Admin', 'admin@unsoed.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin', 'active');
-- Password default adalah "password"

-- 4. Tambah Semester Aktif
INSERT INTO semesters (name, start_date, end_date, is_active) VALUES 
('Ganjil 2026/2027', '2026-08-01', '2026-12-31', 1);

-- 5. Tambah Jadwal Kelas Contoh
INSERT INTO class_schedules (semester_id, room_id, course_code, course_name, class_name, lecturer_name, weekday, start_time, end_time) VALUES 
(1, 1, 'TI101', 'Algoritma dan Pemrograman', 'A', 'Dosen A', 1, '08:00:00', '09:40:00'),
(1, 2, 'TI102', 'Struktur Data', 'B', 'Dosen B', 2, '10:00:00', '12:30:00');
