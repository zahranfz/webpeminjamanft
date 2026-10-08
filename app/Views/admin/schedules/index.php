<div class="container" style="padding-top: 1.5rem; padding-bottom: 4rem;">
<!-- Header & Actions -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 class="page-title" style="margin-bottom: 0.25rem;">Kelola Jadwal Kuliah Manual (FR-054)</h1>
        <p class="page-subtitle" style="margin: 0;">Tambah, ubah, atau nonaktifkan satu entri jadwal tanpa harus impor ulang CSV.</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="<?= \App\Support\Security::escape($baseUrl) ?>/admin" class="btn btn-outline" style="font-size: 0.85rem;">← Dashboard</a>
        <a href="<?= \App\Support\Security::escape($baseUrl) ?>/admin/import" class="btn btn-outline" style="font-size: 0.85rem;">📂 Impor CSV</a>
        <a href="<?= \App\Support\Security::escape($baseUrl) ?>/admin/schedules/create" class="btn btn-primary" style="font-size: 0.85rem;">+ Tambah Jadwal Manual</a>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
    <form method="GET" action="<?= \App\Support\Security::escape($baseUrl) ?>/admin/schedules" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end; margin: 0;">
        <div style="min-width: 180px;">
            <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.35rem;">Semester</label>
            <select name="semester_id" class="filter-select" style="width: 100%;">
                <?php foreach ($semesters as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= ((int)$semFilter === (int)$s['id']) ? 'selected' : '' ?>>
                        <?= \App\Support\Security::escape($s['name']) ?> <?= ((int)$s['is_active'] === 1) ? '(Aktif)' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="min-width: 160px;">
            <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.35rem;">Ruangan</label>
            <select name="room_id" class="filter-select" style="width: 100%;">
                <option value="0">Semua Ruangan</option>
                <?php foreach ($rooms as $r): ?>
                    <option value="<?= $r['id'] ?>" <?= ((int)($_GET['room_id'] ?? 0) === (int)$r['id']) ? 'selected' : '' ?>>
                        <?= \App\Support\Security::escape($r['code']) ?> - <?= \App\Support\Security::escape($r['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="min-width: 130px;">
            <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.35rem;">Hari</label>
            <select name="weekday" class="filter-select" style="width: 100%;">
                <option value="0">Semua Hari</option>
                <?php
                $days = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
                foreach ($days as $num => $dayName):
                ?>
                    <option value="<?= $num ?>" <?= ((int)($_GET['weekday'] ?? 0) === $num) ? 'selected' : '' ?>><?= $dayName ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="flex: 1; min-width: 200px;">
            <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.35rem;">Cari Mata Kuliah / Dosen</label>
            <input type="text" name="search" class="search-input" style="padding-left: 1rem;" placeholder="Cari nama atau kode..." value="<?= \App\Support\Security::escape($_GET['search'] ?? '') ?>">
        </div>

        <div>
            <button type="submit" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.65rem 1.25rem;">Filter</button>
            <a href="<?= \App\Support\Security::escape($baseUrl) ?>/admin/schedules" class="btn btn-outline" style="font-size: 0.85rem; padding: 0.65rem 1rem;">Reset</a>
        </div>
    </form>
</div>

<!-- Table Card -->
<div class="card" style="padding: 0; overflow: hidden;">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
            <thead>
                <tr style="background: var(--surface); border-bottom: 2px solid var(--border); color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase;">
                    <th style="padding: 1rem;">Hari & Jam</th>
                    <th style="padding: 1rem;">Ruangan</th>
                    <th style="padding: 1rem;">Mata Kuliah</th>
                    <th style="padding: 1rem; text-align: center;">Kelas</th>
                    <th style="padding: 1rem;">Dosen Pengampu</th>
                    <th style="padding: 1rem; text-align: center;">Peserta</th>
                    <th style="padding: 1rem; text-align: center;">Status</th>
                    <th style="padding: 1rem; text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($schedules)): ?>
                    <tr>
                        <td colspan="8" style="padding: 3rem 1rem; text-align: center; color: var(--text-muted);">
                            Tidak ada jadwal kuliah yang cocok dengan filter.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php
                    $weekdayMap = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
                    foreach ($schedules as $s):
                        $dayName = $weekdayMap[(int)$s['weekday']] ?? '-';
                        $timeStr = substr($s['start_time'], 0, 5) . ' - ' . substr($s['end_time'], 0, 5);
                    ?>
                        <tr style="border-bottom: 1px solid var(--border); transition: background 0.15s ease;">
                            <td style="padding: 1rem; font-weight: 700; color: var(--primary);">
                                <?= $dayName ?><br>
                                <span style="font-size: 0.8rem; font-weight: normal; color: var(--text-muted);"><?= $timeStr ?> WIB</span>
                            </td>
                            <td style="padding: 1rem;">
                                <span class="badge" style="background: #e2e8f0; color: #1e293b; font-weight: 700;">
                                    <?= \App\Support\Security::escape($s['room_code']) ?>
                                </span>
                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">
                                    <?= \App\Support\Security::escape($s['building_name']) ?>
                                </div>
                            </td>
                            <td style="padding: 1rem; font-weight: 600;">
                                <?= \App\Support\Security::escape($s['course_name']) ?>
                                <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">
                                    Kode: <?= \App\Support\Security::escape($s['course_code']) ?>
                                </div>
                            </td>
                            <td style="padding: 1rem; text-align: center; font-weight: 700;">
                                <?= \App\Support\Security::escape($s['class_name']) ?>
                            </td>
                            <td style="padding: 1rem;">
                                <?= \App\Support\Security::escape($s['lecturer_name']) ?>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <?= (int)$s['attendees'] ?>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <?php if ((int)$s['is_active'] === 1): ?>
                                    <span class="badge badge-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1rem; text-align: right; white-space: nowrap;">
                                <div style="display: flex; gap: 0.4rem; justify-content: flex-end; align-items: center;">
                                    <a href="<?= \App\Support\Security::escape($baseUrl) ?>/admin/schedules/<?= $s['id'] ?>/edit" class="btn btn-outline" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;">
                                        Edit
                                    </a>

                                    <form method="POST" action="<?= \App\Support\Security::escape($baseUrl) ?>/admin/schedules/<?= $s['id'] ?>/toggle" style="margin: 0;">
                                        <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
                                        <button type="submit" class="btn btn-outline" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;">
                                            <?= ((int)$s['is_active'] === 1) ? 'Nonaktifkan' : 'Aktifkan' ?>
                                        </button>
                                    </form>

                                    <form method="POST" action="<?= \App\Support\Security::escape($baseUrl) ?>/admin/schedules/<?= $s['id'] ?>/delete" style="margin: 0;" onsubmit="return confirm('Hapus jadwal ini?');">
                                        <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
                                        <button type="submit" class="btn btn-danger" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;">
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

</div>
