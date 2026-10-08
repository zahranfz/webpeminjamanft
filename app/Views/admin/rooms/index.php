<div class="container" style="padding-top: 1.5rem; padding-bottom: 4rem;">
<!-- Breadcrumb & Actions -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 class="page-title" style="margin-bottom: 0.25rem;">Kelola Data Ruangan (FR-050)</h1>
        <p class="page-subtitle" style="margin: 0;">Kelola master data ruangan, fasilitas, dan penonaktifan operasional sementara (BR-016, BR-019).</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="<?= \App\Support\Security::escape($baseUrl) ?>/admin" class="btn btn-outline" style="font-size: 0.85rem;">← Kembali ke Dashboard</a>
        <a href="<?= \App\Support\Security::escape($baseUrl) ?>/admin/rooms/create" class="btn btn-primary" style="font-size: 0.85rem;">+ Tambah Ruangan</a>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
    <form method="GET" action="<?= \App\Support\Security::escape($baseUrl) ?>/admin/rooms" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end; margin: 0;">
        <div style="flex: 1; min-width: 200px;">
            <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.35rem;">Cari Nama / Kode</label>
            <input type="text" name="search" class="search-input" style="padding-left: 1rem;" placeholder="Cari nama atau kode ruangan..." value="<?= \App\Support\Security::escape($_GET['search'] ?? '') ?>">
        </div>
        <div style="min-width: 180px;">
            <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.35rem;">Gedung</label>
            <select name="building_id" class="filter-select" style="width: 100%;">
                <option value="0">Semua Gedung</option>
                <?php foreach ($buildings as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= ((int)($_GET['building_id'] ?? 0) === (int)$b['id']) ? 'selected' : '' ?>>
                        <?= \App\Support\Security::escape($b['name']) ?> (<?= \App\Support\Security::escape($b['code']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="min-width: 150px;">
            <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.35rem;">Status</label>
            <select name="status" class="filter-select" style="width: 100%;">
                <option value="">Semua Status</option>
                <option value="active" <?= (($_GET['status'] ?? '') === 'active') ? 'selected' : '' ?>>Aktif</option>
                <option value="inactive" <?= (($_GET['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Nonaktif</option>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.65rem 1.25rem;">Filter</button>
            <a href="<?= \App\Support\Security::escape($baseUrl) ?>/admin/rooms" class="btn btn-outline" style="font-size: 0.85rem; padding: 0.65rem 1rem;">Reset</a>
        </div>
    </form>
</div>

<!-- Table Card -->
<div class="card" style="padding: 0; overflow: hidden;">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
            <thead>
                <tr style="background: var(--surface); border-bottom: 2px solid var(--border); color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase;">
                    <th style="padding: 1rem;">Kode</th>
                    <th style="padding: 1rem;">Nama Ruangan</th>
                    <th style="padding: 1rem;">Gedung & Lantai</th>
                    <th style="padding: 1rem; text-align: center;">Kapasitas</th>
                    <th style="padding: 1rem;">Tipe</th>
                    <th style="padding: 1rem; text-align: center;">Fasilitas</th>
                    <th style="padding: 1rem; text-align: center;">Status</th>
                    <th style="padding: 1rem; text-align: center;">Dapat Dipinjam</th>
                    <th style="padding: 1rem; text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rooms)): ?>
                    <tr>
                        <td colspan="9" style="padding: 3rem 1rem; text-align: center; color: var(--text-muted);">
                            Tidak ada data ruangan yang sesuai kriteria pencarian.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rooms as $r): ?>
                        <tr style="border-bottom: 1px solid var(--border); transition: background 0.15s ease;">
                            <td style="padding: 1rem; font-weight: 700; color: var(--primary);">
                                <?= \App\Support\Security::escape($r['code']) ?>
                            </td>
                            <td style="padding: 1rem; font-weight: 600;">
                                <a href="<?= \App\Support\Security::escape($baseUrl) ?>/rooms/<?= $r['id'] ?>" target="_blank" style="text-decoration: none; color: inherit;" title="Lihat detail publik">
                                    <?= \App\Support\Security::escape($r['name']) ?> ↗
                                </a>
                            </td>
                            <td style="padding: 1rem;">
                                <?= \App\Support\Security::escape($r['building_name']) ?>, Lt. <?= (int)$r['floor'] ?>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <span class="badge" style="background: #f1f5f9; color: #334155; font-weight: 600;">
                                    <?= (int)$r['capacity'] ?> org
                                </span>
                            </td>
                            <td style="padding: 1rem;">
                                <?php
                                $typeLabels = [
                                    'classroom' => 'Ruang Kelas',
                                    'laboratory' => 'Laboratorium',
                                    'hall' => 'Aula / Auditorium',
                                    'meeting_room' => 'Ruang Rapat',
                                    'other' => 'Lainnya'
                                ];
                                echo $typeLabels[$r['type']] ?? ucfirst($r['type']);
                                ?>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <a href="<?= \App\Support\Security::escape($baseUrl) ?>/admin/rooms/<?= $r['id'] ?>/facilities" class="btn btn-outline" style="padding: 0.25rem 0.6rem; font-size: 0.75rem;">
                                    ⚙️ <?= (int)$r['facility_count'] ?> item
                                </a>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <?php if ((int)$r['is_active'] === 1): ?>
                                    <span class="badge badge-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-danger" title="<?= \App\Support\Security::escape($r['inactive_reason'] ?? 'Nonaktif') ?>">
                                        Nonaktif
                                    </span>
                                    <?php if ($r['inactive_until']): ?>
                                        <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.2rem;">
                                            s.d. <?= date('d/m/Y', strtotime($r['inactive_until'])) ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <?php if ((int)$r['can_be_booked'] === 1): ?>
                                    <span style="color: var(--success); font-weight: 600;">Ya</span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted);">Tidak</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1rem; text-align: right; white-space: nowrap;">
                                <div style="display: flex; gap: 0.4rem; justify-content: flex-end; align-items: center;">
                                    <a href="<?= \App\Support\Security::escape($baseUrl) ?>/admin/rooms/<?= $r['id'] ?>/edit" class="btn btn-outline" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;">
                                        Edit
                                    </a>

                                    <?php if ((int)$r['is_active'] === 1): ?>
                                        <button type="button" class="btn btn-warning" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;" onclick="openDeactivateModal(<?= $r['id'] ?>, '<?= \App\Support\Security::escape($r['name']) ?>', '<?= \App\Support\Security::escape($r['code']) ?>')">
                                            Nonaktifkan
                                        </button>
                                    <?php else: ?>
                                        <form method="POST" action="<?= \App\Support\Security::escape($baseUrl) ?>/admin/rooms/activate" style="margin: 0;" onsubmit="return confirm('Aktifkan kembali ruangan ini?');">
                                            <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
                                            <input type="hidden" name="room_id" value="<?= $r['id'] ?>">
                                            <button type="submit" class="btn btn-success" style="padding: 0.35rem 0.65rem; font-size: 0.75rem;">
                                                Aktifkan
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <!-- Delete Button with BR-016 Warning -->
                                    <form method="POST" action="<?= \App\Support\Security::escape($baseUrl) ?>/admin/rooms/delete" style="margin: 0;" onsubmit="return confirmDelete(<?= (int)$r['schedule_count'] ?>, <?= (int)$r['booking_count'] ?>);">
                                        <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
                                        <input type="hidden" name="room_id" value="<?= $r['id'] ?>">
                                        <button type="submit" class="btn btn-danger" style="padding: 0.35rem 0.65rem; font-size: 0.75rem; <?= ((int)$r['schedule_count'] > 0 || (int)$r['booking_count'] > 0) ? 'opacity: 0.6;' : '' ?>" title="<?= ((int)$r['schedule_count'] > 0 || (int)$r['booking_count'] > 0) ? 'Ruangan punya riwayat historis (BR-016)' : 'Hapus ruangan' ?>">
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

<!-- Modal Nonaktifkan Ruangan Sementara (FR-052) -->
<div id="deactivateModal" class="modal-overlay" onclick="if(event.target === this) closeDeactivateModal();">
    <div class="modal-container">
        <!-- Modal Header -->
        <div class="modal-header">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                </div>
                <div>
                    <h3 class="modal-title" style="font-size: 1.1rem; color: #1e293b;">Nonaktifkan Ruangan Sementara</h3>
                    <div id="modalRoomSubtitle" style="font-size: 0.85rem; color: #64748b; font-weight: 500; margin-top: 0.15rem;">Ruangan</div>
                </div>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeDeactivateModal()" title="Tutup">&times;</button>
        </div>

        <form method="POST" action="<?= \App\Support\Security::escape($baseUrl) ?>/admin/rooms/deactivate" style="margin: 0; display: flex; flex-direction: column;">
            <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
            <input type="hidden" name="room_id" id="modalRoomId" value="">

            <!-- Modal Body -->
            <div class="modal-body" style="padding: 1.5rem;">
                <!-- Callout Warning -->
                <div style="background: #fff7ed; border: 1px solid #ffedd5; border-left: 4px solid #f97316; border-radius: 8px; padding: 0.85rem 1rem; margin-bottom: 1.25rem; font-size: 0.85rem; color: #9a3412; line-height: 1.45;">
                    <strong style="display: block; margin-bottom: 0.25rem; color: #c2410c;">Konsekuensi Penonaktifan (BR-019):</strong>
                    Status ruangan akan berubah menjadi <strong>Tidak tersedia</strong>, pengajuan peminjaman baru ditolak, dan seluruh peminjaman yang disetujui di masa depan otomatis ditandai <strong>perlu ditinjau</strong>.
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.4rem;">
                        Alasan Penonaktifan <span style="color: var(--danger);">*</span>
                    </label>
                    <textarea name="inactive_reason" required style="width: 100%; border: 1.5px solid var(--border); border-radius: 8px; padding: 0.75rem; font-family: inherit; font-size: 0.875rem; resize: vertical; min-height: 80px; outline: none; box-sizing: border-box; transition: border-color 0.2s;" placeholder="Contoh: Renovasi plafon, perbaikan AC sentral, pemeliharaan laboratorium..." onfocus="this.style.borderColor='var(--secondary-light)';" onblur="this.style.borderColor='var(--border)';"></textarea>
                </div>

                <div style="margin-bottom: 0.5rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.4rem;">
                        Perkiraan Aktif Kembali <span style="font-weight: 400; color: #64748b; font-size: 0.8rem;">(Opsional)</span>
                    </label>
                    <input type="date" name="inactive_until" min="<?= date('Y-m-d') ?>" style="width: 100%; border: 1.5px solid var(--border); border-radius: 8px; padding: 0.65rem 0.75rem; font-family: inherit; font-size: 0.875rem; outline: none; box-sizing: border-box; transition: border-color 0.2s;" onfocus="this.style.borderColor='var(--secondary-light)';" onblur="this.style.borderColor='var(--border)';">
                    <small style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 0.35rem;">Biarkan kosong jika jangka waktu penonaktifan belum dapat dipastikan.</small>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" style="padding: 0.6rem 1.25rem; font-size: 0.875rem;" onclick="closeDeactivateModal()">Batal</button>
                <button type="submit" class="btn btn-danger" style="padding: 0.6rem 1.35rem; font-size: 0.875rem; background: #dc2626; color: #ffffff; border: none; font-weight: 600; box-shadow: 0 2px 4px rgba(220, 38, 38, 0.2);">Konfirmasi Nonaktifkan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openDeactivateModal(id, name, code) {
    document.getElementById('modalRoomId').value = id;
    document.getElementById('modalRoomSubtitle').innerText = name + ' (' + code + ')';
    const modal = document.getElementById('deactivateModal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeDeactivateModal() {
    const modal = document.getElementById('deactivateModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

// Press ESC to close modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDeactivateModal();
    }
});

function confirmDelete(schedules, bookings) {
    if (schedules > 0 || schedules === undefined) {
        // Just standard confirm check
    }
    if (schedules > 0 || bookings > 0) {
        alert("PERINGATAN BR-016: Ruangan ini memiliki riwayat historis (" + schedules + " jadwal, " + bookings + " peminjaman).\nSistem akan menolak penghapusan permanen. Anda disarankan menonaktifkan ruangan ini.");
    }
    return confirm("Apakah Anda yakin ingin menghapus ruangan ini?");
}
</script>

</div>
