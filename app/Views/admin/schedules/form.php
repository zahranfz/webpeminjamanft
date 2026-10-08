<div class="container" style="padding-top: 1.5rem; padding-bottom: 4rem;">
<!-- Breadcrumb -->
<div style="margin-bottom: 1.5rem;">
    <a href="<?= \App\Support\Security::escape($baseUrl) ?>/admin/schedules" style="text-decoration: none; color: var(--primary); font-size: 0.85rem; font-weight: 600;">
        ← Kembali ke Daftar Jadwal
    </a>
    <h1 class="page-title" style="margin-top: 0.5rem; margin-bottom: 0.25rem;">
        <?= $schedule ? 'Edit Jadwal: ' . \App\Support\Security::escape($schedule['course_name']) : 'Tambah Jadwal Kuliah Manual (FR-054)' ?>
    </h1>
    <p class="page-subtitle" style="margin: 0;">Entri jadwal akan divalidasi bentrok waktu & ruangan otomatis (BR-004).</p>
</div>

<div class="card" style="max-width: 720px; padding: 2rem;">
    <form method="POST" action="<?= \App\Support\Security::escape($baseUrl) ?>/admin/schedules/save">
        <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
        <?php if ($schedule): ?>
            <input type="hidden" name="id" value="<?= (int)$schedule['id'] ?>">
        <?php endif; ?>

        <!-- Semester & Ruangan -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                    Semester Akademik <span style="color: var(--danger);">*</span>
                </label>
                <select name="semester_id" class="filter-select" style="width: 100%;" required>
                    <?php foreach ($semesters as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ((int)($schedule['semester_id'] ?? 0) === (int)$s['id'] || (!isset($schedule) && (int)$s['is_active'] === 1)) ? 'selected' : '' ?>>
                            <?= \App\Support\Security::escape($s['name']) ?> <?= ((int)$s['is_active'] === 1) ? '(Aktif)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                    Ruangan Kuliah <span style="color: var(--danger);">*</span>
                </label>
                <select name="room_id" class="filter-select" style="width: 100%;" required>
                    <option value="">Pilih Ruangan...</option>
                    <?php foreach ($rooms as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= ((int)($schedule['room_id'] ?? 0) === (int)$r['id']) ? 'selected' : '' ?>>
                            <?= \App\Support\Security::escape($r['code']) ?> - <?= \App\Support\Security::escape($r['name']) ?> (Kapasitas: <?= (int)$r['capacity'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Mata Kuliah & Kelas -->
        <div style="display: grid; grid-template-columns: 1fr 2fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                    Kode MK <span style="color: var(--danger);">*</span>
                </label>
                <input type="text" name="course_code" class="search-input" style="padding: 0.6rem 0.75rem; width: 100%; box-sizing: border-box;" 
                       placeholder="Contoh: TI101" 
                       value="<?= \App\Support\Security::escape($schedule['course_code'] ?? '') ?>" required>
            </div>

            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                    Nama Mata Kuliah <span style="color: var(--danger);">*</span>
                </label>
                <input type="text" name="course_name" class="search-input" style="padding: 0.6rem 0.75rem; width: 100%; box-sizing: border-box;" 
                       placeholder="Contoh: Algoritma dan Pemrograman" 
                       value="<?= \App\Support\Security::escape($schedule['course_name'] ?? '') ?>" required>
            </div>

            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                    Kelas <span style="color: var(--danger);">*</span>
                </label>
                <input type="text" name="class_name" class="search-input" style="padding: 0.6rem 0.75rem; width: 100%; box-sizing: border-box;" 
                       placeholder="A / B / C" 
                       value="<?= \App\Support\Security::escape($schedule['class_name'] ?? 'A') ?>" required>
            </div>
        </div>

        <!-- Dosen Pengampu & Tautan Akun -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                    Nama Dosen Pengampu <span style="color: var(--danger);">*</span>
                </label>
                <input type="text" name="lecturer_name" id="lecturerNameInput" class="search-input" style="padding: 0.6rem 0.75rem; width: 100%; box-sizing: border-box;" 
                       placeholder="Contoh: Dr. Ir. Budi Santoso, M.T." 
                       value="<?= \App\Support\Security::escape($schedule['lecturer_name'] ?? '') ?>" required>
            </div>

            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                    Tautkan Akun Dosen (Untuk Check-in)
                </label>
                <select name="lecturer_user_id" id="lecturerUserSelect" class="filter-select" style="width: 100%;" onchange="autoFillLecturerName()">
                    <option value="">-- Tanpa Tautan Akun --</option>
                    <?php foreach ($lecturers as $lec): ?>
                        <option value="<?= $lec['id'] ?>" data-name="<?= \App\Support\Security::escape($lec['name']) ?>" <?= ((int)($schedule['lecturer_user_id'] ?? 0) === (int)$lec['id']) ? 'selected' : '' ?>>
                            <?= \App\Support\Security::escape($lec['name']) ?> (<?= \App\Support\Security::escape($lec['identity_number'] ?? $lec['email']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small style="font-size: 0.75rem; color: var(--text-muted);">Memungkinkan dosen melakukan check-in via halaman Kelas Saya.</small>
            </div>
        </div>

        <!-- Waktu & Hari -->
        <div style="display: grid; grid-template-columns: 1.2fr 1fr 1fr 0.8fr; gap: 1rem; margin-bottom: 1.5rem;">
            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                    Hari <span style="color: var(--danger);">*</span>
                </label>
                <select name="weekday" class="filter-select" style="width: 100%;" required>
                    <?php
                    $days = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
                    $currentDay = (int)($schedule['weekday'] ?? 1);
                    foreach ($days as $num => $dayName):
                    ?>
                        <option value="<?= $num ?>" <?= ($currentDay === $num) ? 'selected' : '' ?>><?= $dayName ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                    Jam Mulai <span style="color: var(--danger);">*</span>
                </label>
                <input type="time" name="start_time" class="search-input" style="padding: 0.55rem; width: 100%; box-sizing: border-box;" 
                       value="<?= substr($schedule['start_time'] ?? '08:00', 0, 5) ?>" required>
            </div>

            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                    Jam Selesai <span style="color: var(--danger);">*</span>
                </label>
                <input type="time" name="end_time" class="search-input" style="padding: 0.55rem; width: 100%; box-sizing: border-box;" 
                       value="<?= substr($schedule['end_time'] ?? '09:40', 0, 5) ?>" required>
            </div>

            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                    Peserta
                </label>
                <input type="number" name="attendees" class="search-input" style="padding: 0.55rem; width: 100%; box-sizing: border-box;" 
                       min="0" max="500" value="<?= (int)($schedule['attendees'] ?? 40) ?>">
            </div>
        </div>

        <div style="border-top: 1px solid var(--border); padding-top: 1.25rem; margin-bottom: 1.5rem;">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem;">
                <input type="checkbox" name="is_active" value="1" <?= (!isset($schedule) || (int)$schedule['is_active'] === 1) ? 'checked' : '' ?>>
                <span><strong>Jadwal Aktif Berjalan</strong> (Slot ruangan akan terkunci selama jadwal ini aktif)</span>
            </label>
        </div>

        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <a href="<?= \App\Support\Security::escape($baseUrl) ?>/admin/schedules" class="btn btn-outline">Batal</a>
            <button type="submit" class="btn btn-primary">
                <?= $schedule ? 'Simpan Perubahan' : 'Tambah Jadwal' ?>
            </button>
        </div>
    </form>
</div>

<script>
function autoFillLecturerName() {
    const sel = document.getElementById('lecturerUserSelect');
    const nameInput = document.getElementById('lecturerNameInput');
    const selectedOpt = sel.options[sel.selectedIndex];
    if (selectedOpt && selectedOpt.dataset.name && (!nameInput.value || nameInput.value.trim() === '')) {
        nameInput.value = selectedOpt.dataset.name;
    }
}
</script>

</div>
