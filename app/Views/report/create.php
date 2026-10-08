<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
<div style="max-width: 680px; margin: 0 auto;">
    <div style="margin-bottom: 1.5rem;">
        <h2 style="color: var(--secondary); margin-bottom: 0.25rem;">Lapor Kendala Fasilitas Ruangan</h2>
        <p style="color: var(--text-muted); margin: 0;">Sampaikan laporan kerusakan atau kendala fasilitas ruangan kepada pengelola fakultas.</p>
    </div>

    <!-- Peringatan Laporan Serupa Aktif (FR-081: Pencegahan Laporan Ganda) -->
    <?php if (!empty($activeReports)): ?>
        <div style="background: rgba(245, 158, 11, 0.08); border: 1px solid var(--warning); border-radius: var(--radius); padding: 1.25rem; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; color: #b45309; font-weight: 600;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <span>Terdapat Laporan Aktif Serupa di Ruangan Ini:</span>
            </div>
            <p style="font-size: 0.85rem; color: var(--text); margin-bottom: 1rem;">
                Jika kendala yang Anda alami sama dengan laporan di bawah, Anda disarankan menekan tombol <strong>"Saya juga mengalami"</strong> alih-alih membuat laporan baru.
            </p>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <?php foreach ($activeReports as $ar): ?>
                    <div style="background: white; border: 1px solid var(--border); border-radius: 8px; padding: 0.75rem 1rem; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600; font-size: 0.9rem;"><?= \App\Support\Security::escape($ar['facility_name']) ?> (<?= ucfirst($ar['category']) ?>)</div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);"><?= \App\Support\Security::escape($ar['description']) ?></div>
                        </div>
                        <form action="<?= \App\Support\Security::escape($baseUrl) ?>/report/support" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
                            <input type="hidden" name="report_id" value="<?= $ar['id'] ?>">
                            <button type="submit" class="btn btn-warning" style="font-size: 0.75rem; padding: 0.35rem 0.75rem;">
                                👍 Saya Juga Mengalami (<?= $ar['supports_count'] ?>)
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div style="background: var(--surface); padding: 2rem; border-radius: var(--radius); box-shadow: var(--shadow);">
        <form action="<?= \App\Support\Security::escape($baseUrl) ?>/report/store" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
            
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.4rem; color: var(--text-muted); font-size: 0.875rem; font-weight: 500;">Pilih Ruangan *</label>
                <select name="room_id" required class="form-control" style="width: 100%; padding: 0.65rem;" onchange="window.location.href = '<?= $baseUrl ?>/report/create?room_id=' + this.value;">
                    <option value="">-- Pilih Ruangan --</option>
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?= $room['id'] ?>" <?= (isset($_GET['room_id']) && (int)$_GET['room_id'] === (int)$room['id']) ? 'selected' : '' ?>>
                            Ruang <?= \App\Support\Security::escape($room['name']) ?> (<?= \App\Support\Security::escape($room['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; margin-bottom: 0.4rem; color: var(--text-muted); font-size: 0.875rem; font-weight: 500;">Kategori Kendala *</label>
                    <select name="category" required class="form-control" style="width: 100%; padding: 0.65rem;">
                        <option value="damage">Kerusakan Fisik</option>
                        <option value="issue">Terkendala / Tidak Berfungsi</option>
                        <option value="loss">Kehilangan Fasilitas</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 0.4rem; color: var(--text-muted); font-size: 0.875rem; font-weight: 500;">Tingkat Urgensi *</label>
                    <select name="urgency" required class="form-control" style="width: 100%; padding: 0.65rem;">
                        <option value="normal">Normal (Biasa)</option>
                        <option value="urgent">Mendesak (Mengganggu Perkuliahan)</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.4rem; color: var(--text-muted); font-size: 0.875rem; font-weight: 500;">Nama Fasilitas yang Bermasalah *</label>
                <input type="text" name="facility_name" list="facilityList" required placeholder="Contoh: AC, Proyektor, Lampu, Kursi" class="form-control" style="width: 100%; padding: 0.65rem;">
                <?php if (!empty($roomFacilities)): ?>
                    <datalist id="facilityList">
                        <?php foreach ($roomFacilities as $rf): ?>
                            <option value="<?= \App\Support\Security::escape($rf['type']) ?>">
                        <?php endforeach; ?>
                        <option value="Lainnya">
                    </datalist>
                <?php endif; ?>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.4rem; color: var(--text-muted); font-size: 0.875rem; font-weight: 500;">Deskripsi Detail Kerusakan * (Min. 10 Karakter)</label>
                <textarea name="description" required minlength="10" maxlength="1000" rows="4" class="form-control" style="width: 100%; padding: 0.65rem;" placeholder="Jelaskan secara jelas kendala atau kerusakan yang terjadi..."></textarea>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.4rem; color: var(--text-muted); font-size: 0.875rem; font-weight: 500;">Foto Bukti Kerusakan (Opsional, Maks. 3 Berkas JPG/PNG, @ ≤ 2 MB)</label>
                <input type="file" name="photos[]" accept="image/jpeg,image/png,image/jpg" multiple class="form-control" style="width: 100%; padding: 0.45rem; font-size: 0.85rem;">
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <a href="<?= \App\Support\Security::escape($baseUrl) ?>/" class="btn btn-outline">Batal</a>
                <button type="submit" class="btn btn-primary">Kirim Laporan</button>
            </div>
        </form>
    </div>
</div>
</div>
