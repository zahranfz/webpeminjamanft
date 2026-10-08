<div class="container" style="padding-top: 1.5rem; padding-bottom: 4rem;">
<!-- Breadcrumb & Header -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 class="page-title" style="margin-bottom: 0.25rem;">Semester & Periode Tanpa Kelas (FR-053)</h1>
        <p class="page-subtitle" style="margin: 0;">Kelola kalender akademik semester aktif dan periode libur/minggu tenang (BR-003).</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="<?= \App\Support\Security::escape($baseUrl) ?>/admin" class="btn btn-outline" style="font-size: 0.85rem;">← Kembali ke Dashboard</a>
    </div>
</div>

<!-- SECTION 1: SEMESTER -->
<div style="margin-bottom: 2.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0;">1. Data Semester Akademik</h2>
        <span style="font-size: 0.8rem; color: var(--text-muted); background: var(--surface); padding: 0.25rem 0.6rem; border-radius: 4px; border: 1px solid var(--border);">
            ⚠️ Aturan FR-053 AC 2: Hanya satu semester yang dapat aktif pada satu waktu
        </span>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1.2fr; gap: 1.5rem; align-items: start;">
        <!-- Semester Table -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
                    <thead>
                        <tr style="background: var(--surface); border-bottom: 2px solid var(--border); color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase;">
                            <th style="padding: 0.85rem 1rem;">Nama Semester</th>
                            <th style="padding: 0.85rem 1rem;">Periode Berjalan</th>
                            <th style="padding: 0.85rem 1rem; text-align: center;">Jadwal Kelas</th>
                            <th style="padding: 0.85rem 1rem; text-align: center;">Status</th>
                            <th style="padding: 0.85rem 1rem; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($semesters)): ?>
                            <tr>
                                <td colspan="5" style="padding: 2rem 1rem; text-align: center; color: var(--text-muted);">
                                    Belum ada data semester.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($semesters as $s): ?>
                                <tr style="border-bottom: 1px solid var(--border); <?= ((int)$s['is_active'] === 1) ? 'background: rgba(37, 99, 235, 0.03);' : '' ?>">
                                    <td style="padding: 0.85rem 1rem; font-weight: 700;">
                                        <?= \App\Support\Security::escape($s['name']) ?>
                                    </td>
                                    <td style="padding: 0.85rem 1rem; font-size: 0.8rem;">
                                        <?= date('d/m/Y', strtotime($s['start_date'])) ?> s.d. <?= date('d/m/Y', strtotime($s['end_date'])) ?>
                                    </td>
                                    <td style="padding: 0.85rem 1rem; text-align: center;">
                                        <span class="badge" style="background: #e2e8f0; color: #1e293b;">
                                            <?= (int)$s['schedule_count'] ?> entri
                                        </span>
                                    </td>
                                    <td style="padding: 0.85rem 1rem; text-align: center;">
                                        <?php if ((int)$s['is_active'] === 1): ?>
                                            <span class="badge badge-success">AKTIF UTAMA</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Tidak Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 0.85rem 1rem; text-align: right; white-space: nowrap;">
                                        <div style="display: flex; gap: 0.4rem; justify-content: flex-end;">
                                            <button type="button" class="btn btn-outline" style="padding: 0.25rem 0.55rem; font-size: 0.75rem;"
                                                    onclick="editSemester(<?= $s['id'] ?>, '<?= \App\Support\Security::escape($s['name']) ?>', '<?= $s['start_date'] ?>', '<?= $s['end_date'] ?>', <?= (int)$s['is_active'] ?>)">
                                                Edit
                                            </button>
                                            <?php if ((int)$s['is_active'] !== 1): ?>
                                                <form method="POST" action="<?= \App\Support\Security::escape($baseUrl) ?>/admin/terms/activate" style="margin: 0;" onsubmit="return confirm('Aktifkan semester ini? Semester lain otomatis nonaktif.');">
                                                    <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
                                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                                    <button type="submit" class="btn btn-primary" style="padding: 0.25rem 0.55rem; font-size: 0.75rem;">
                                                        Set Aktif
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Semester Form -->
        <div class="card" style="padding: 1.5rem;">
            <h3 id="formSemTitle" style="margin-top: 0; margin-bottom: 1rem; font-size: 1.05rem;">Tambah Semester</h3>
            <form method="POST" action="<?= \App\Support\Security::escape($baseUrl) ?>/admin/terms/save">
                <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
                <input type="hidden" name="id" id="semId" value="0">

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                        Nama Semester <span style="color: var(--danger);">*</span>
                    </label>
                    <input type="text" name="name" id="semName" class="search-input" style="padding: 0.6rem 0.75rem; width: 100%; box-sizing: border-box;" 
                           placeholder="Contoh: Gasal 2026/2027" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.35rem;">Mulai <span style="color: var(--danger);">*</span></label>
                        <input type="date" name="start_date" id="semStart" class="search-input" style="padding: 0.55rem; width: 100%; box-sizing: border-box;" required>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.35rem;">Selesai <span style="color: var(--danger);">*</span></label>
                        <input type="date" name="end_date" id="semEnd" class="search-input" style="padding: 0.55rem; width: 100%; box-sizing: border-box;" required>
                    </div>
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.85rem;">
                        <input type="checkbox" name="is_active" id="semActive" value="1">
                        <span><strong>Aktifkan semester ini sekarang</strong></span>
                    </label>
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-outline" id="btnCancelSem" style="display: none;" onclick="showAddSemester()">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitSem">Simpan Semester</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SECTION 2: PERIODE TANPA KELAS / LIBUR (FR-053 AC 1) -->
<div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <div>
            <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0;">2. Periode Tanpa Kelas / Libur / Minggu Tenang</h2>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0.25rem 0 0 0;">
                Pada rentang tanggal ini, seluruh jadwal kelas otomatis tidak berlaku dan ruangan berstatus Kosong (dapat dipinjam).
            </p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1.2fr; gap: 1.5rem; align-items: start;">
        <!-- No-Class Periods Table -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
                    <thead>
                        <tr style="background: var(--surface); border-bottom: 2px solid var(--border); color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase;">
                            <th style="padding: 0.85rem 1rem;">Keterangan Periode</th>
                            <th style="padding: 0.85rem 1rem;">Semester</th>
                            <th style="padding: 0.85rem 1rem;">Rentang Tanggal</th>
                            <th style="padding: 0.85rem 1rem; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($noClassPeriods)): ?>
                            <tr>
                                <td colspan="4" style="padding: 2.5rem 1rem; text-align: center; color: var(--text-muted);">
                                    Belum ada periode tanpa kelas. Ruangan mengikuti jadwal kelas normal.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($noClassPeriods as $p): ?>
                                <tr style="border-bottom: 1px solid var(--border);">
                                    <td style="padding: 0.85rem 1rem; font-weight: 600;">
                                        <?= \App\Support\Security::escape($p['description']) ?>
                                    </td>
                                    <td style="padding: 0.85rem 1rem; font-size: 0.8rem; color: var(--text-muted);">
                                        <?= \App\Support\Security::escape($p['semester_name']) ?>
                                    </td>
                                    <td style="padding: 0.85rem 1rem; font-size: 0.8rem; font-weight: 600; color: var(--primary);">
                                        <?= date('d/m/Y', strtotime($p['start_date'])) ?> s.d. <?= date('d/m/Y', strtotime($p['end_date'])) ?>
                                    </td>
                                    <td style="padding: 0.85rem 1rem; text-align: right; white-space: nowrap;">
                                        <div style="display: flex; gap: 0.4rem; justify-content: flex-end;">
                                            <button type="button" class="btn btn-outline" style="padding: 0.25rem 0.55rem; font-size: 0.75rem;"
                                                    onclick="editPeriod(<?= $p['id'] ?>, <?= $p['semester_id'] ?>, '<?= \App\Support\Security::escape($p['description']) ?>', '<?= $p['start_date'] ?>', '<?= $p['end_date'] ?>')">
                                                Edit
                                            </button>
                                            <form method="POST" action="<?= \App\Support\Security::escape($baseUrl) ?>/admin/no-class/delete" style="margin: 0;" onsubmit="return confirm('Hapus periode tanpa kelas ini?');">
                                                <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
                                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="btn btn-danger" style="padding: 0.25rem 0.55rem; font-size: 0.75rem;">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- No-Class Period Form -->
        <div class="card" style="padding: 1.5rem;">
            <h3 id="formPeriodTitle" style="margin-top: 0; margin-bottom: 1rem; font-size: 1.05rem;">Tambah Periode Tanpa Kelas</h3>
            <form method="POST" action="<?= \App\Support\Security::escape($baseUrl) ?>/admin/no-class/save">
                <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
                <input type="hidden" name="id" id="periodId" value="0">

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                        Pilih Semester <span style="color: var(--danger);">*</span>
                    </label>
                    <select name="semester_id" id="periodSemesterId" class="filter-select" style="width: 100%;" required>
                        <?php foreach ($semesters as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= ((int)$s['is_active'] === 1) ? 'selected' : '' ?>>
                                <?= \App\Support\Security::escape($s['name']) ?> <?= ((int)$s['is_active'] === 1) ? '(Aktif)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                        Keterangan Periode <span style="color: var(--danger);">*</span>
                    </label>
                    <input type="text" name="description" id="periodDesc" class="search-input" style="padding: 0.6rem 0.75rem; width: 100%; box-sizing: border-box;" 
                           placeholder="Contoh: Libur Akhir Semester, Minggu Tenang UAS..." required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1.25rem;">
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.35rem;">Tanggal Mulai <span style="color: var(--danger);">*</span></label>
                        <input type="date" name="start_date" id="periodStart" class="search-input" style="padding: 0.55rem; width: 100%; box-sizing: border-box;" required>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.35rem;">Tanggal Selesai <span style="color: var(--danger);">*</span></label>
                        <input type="date" name="end_date" id="periodEnd" class="search-input" style="padding: 0.55rem; width: 100%; box-sizing: border-box;" required>
                    </div>
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-outline" id="btnCancelPeriod" style="display: none;" onclick="showAddPeriod()">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitPeriod">Simpan Periode</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showAddSemester() {
    document.getElementById('formSemTitle').innerText = 'Tambah Semester';
    document.getElementById('semId').value = '0';
    document.getElementById('semName').value = '';
    document.getElementById('semStart').value = '';
    document.getElementById('semEnd').value = '';
    document.getElementById('semActive').checked = false;
    document.getElementById('btnSubmitSem').innerText = 'Simpan Semester';
    document.getElementById('btnCancelSem').style.display = 'none';
}

function editSemester(id, name, start, end, isActive) {
    document.getElementById('formSemTitle').innerText = 'Edit Semester';
    document.getElementById('semId').value = id;
    document.getElementById('semName').value = name;
    document.getElementById('semStart').value = start;
    document.getElementById('semEnd').value = end;
    document.getElementById('semActive').checked = (isActive === 1);
    document.getElementById('btnSubmitSem').innerText = 'Perbarui Semester';
    document.getElementById('btnCancelSem').style.display = 'inline-block';
}

function showAddPeriod() {
    document.getElementById('formPeriodTitle').innerText = 'Tambah Periode Tanpa Kelas';
    document.getElementById('periodId').value = '0';
    document.getElementById('periodDesc').value = '';
    document.getElementById('periodStart').value = '';
    document.getElementById('periodEnd').value = '';
    document.getElementById('btnSubmitPeriod').innerText = 'Simpan Periode';
    document.getElementById('btnCancelPeriod').style.display = 'none';
}

function editPeriod(id, semId, desc, start, end) {
    document.getElementById('formPeriodTitle').innerText = 'Edit Periode Tanpa Kelas';
    document.getElementById('periodId').value = id;
    document.getElementById('periodSemesterId').value = semId;
    document.getElementById('periodDesc').value = desc;
    document.getElementById('periodStart').value = start;
    document.getElementById('periodEnd').value = end;
    document.getElementById('btnSubmitPeriod').innerText = 'Perbarui Periode';
    document.getElementById('btnCancelPeriod').style.display = 'inline-block';
}
</script>

</div>
