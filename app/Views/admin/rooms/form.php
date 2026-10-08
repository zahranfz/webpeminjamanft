<div class="container" style="padding-top: 1.5rem; padding-bottom: 4rem;">
<!-- Breadcrumb -->
<div style="margin-bottom: 1.5rem;">
    <a href="<?= \App\Support\Security::escape($baseUrl) ?>/admin/rooms" style="text-decoration: none; color: var(--primary); font-size: 0.85rem; font-weight: 600;">
        ← Kembali ke Daftar Ruangan
    </a>
    <h1 class="page-title" style="margin-top: 0.5rem; margin-bottom: 0.25rem;">
        <?= $room ? 'Edit Ruangan: ' . \App\Support\Security::escape($room['name']) : 'Tambah Ruangan Baru' ?>
    </h1>
    <p class="page-subtitle" style="margin: 0;">Isi spesifikasi ruangan sesuai data gedung Fakultas Teknik (FR-050).</p>
</div>

<div class="card" style="max-width: 680px; padding: 2rem;">
    <form method="POST" action="<?= \App\Support\Security::escape($baseUrl) ?>/admin/rooms/save">
        <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
        <?php if ($room): ?>
            <input type="hidden" name="id" value="<?= (int)$room['id'] ?>">
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                    Kode Ruangan (Unik) <span style="color: var(--danger);">*</span>
                </label>
                <input type="text" name="code" class="search-input" style="padding: 0.65rem 0.75rem; width: 100%; box-sizing: border-box;" 
                       placeholder="Contoh: A-101, LAB-KOMP-1" 
                       value="<?= \App\Support\Security::escape($room['code'] ?? '') ?>" required>
                <small style="font-size: 0.75rem; color: var(--text-muted);">Harus unik, digunakan sebagai identifikasi jadwal & peminjaman.</small>
            </div>
            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                    Nama Ruangan <span style="color: var(--danger);">*</span>
                </label>
                <input type="text" name="name" class="search-input" style="padding: 0.65rem 0.75rem; width: 100%; box-sizing: border-box;" 
                       placeholder="Contoh: Ruang Kuliah A101" 
                       value="<?= \App\Support\Security::escape($room['name'] ?? '') ?>" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                    Gedung <span style="color: var(--danger);">*</span>
                </label>
                <select name="building_id" class="filter-select" style="width: 100%;" required>
                    <option value="">Pilih Gedung...</option>
                    <?php foreach ($buildings as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= ((int)($room['building_id'] ?? 0) === (int)$b['id']) ? 'selected' : '' ?>>
                            <?= \App\Support\Security::escape($b['name']) ?> (<?= \App\Support\Security::escape($b['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                    Lantai <span style="color: var(--danger);">*</span>
                </label>
                <input type="number" name="floor" class="search-input" style="padding: 0.65rem 0.75rem; width: 100%; box-sizing: border-box;" 
                       min="1" max="10" 
                       value="<?= (int)($room['floor'] ?? 1) ?>" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                    Kapasitas (Orang) <span style="color: var(--danger);">*</span>
                </label>
                <input type="number" name="capacity" class="search-input" style="padding: 0.65rem 0.75rem; width: 100%; box-sizing: border-box;" 
                       min="1" max="1000" 
                       placeholder="Contoh: 40" 
                       value="<?= (int)($room['capacity'] ?? 40) ?>" required>
            </div>
            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                    Tipe Ruangan <span style="color: var(--danger);">*</span>
                </label>
                <select name="type" class="filter-select" style="width: 100%;" required>
                    <?php
                    $types = [
                        'classroom' => 'Ruang Kelas Teori',
                        'laboratory' => 'Laboratorium',
                        'hall' => 'Aula / Auditorium',
                        'meeting_room' => 'Ruang Rapat',
                        'other' => 'Lainnya'
                    ];
                    $currentType = $room['type'] ?? 'classroom';
                    foreach ($types as $key => $lbl):
                    ?>
                        <option value="<?= $key ?>" <?= ($currentType === $key) ? 'selected' : '' ?>>
                            <?= $lbl ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div style="border-top: 1px solid var(--border); padding-top: 1.25rem; margin-bottom: 1.5rem;">
            <div style="display: flex; gap: 2rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem;">
                    <input type="checkbox" name="can_be_booked" value="1" <?= (!isset($room) || (int)$room['can_be_booked'] === 1) ? 'checked' : '' ?>>
                    <span><strong>Dapat Dipinjam</strong> (Buka untuk permohonan peminjaman)</span>
                </label>

                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem;">
                    <input type="checkbox" name="is_active" value="1" <?= (!isset($room) || (int)$room['is_active'] === 1) ? 'checked' : '' ?>>
                    <span><strong>Aktif Operasional</strong></span>
                </label>
            </div>
        </div>

        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <a href="<?= \App\Support\Security::escape($baseUrl) ?>/admin/rooms" class="btn btn-outline">Batal</a>
            <button type="submit" class="btn btn-primary">
                <?= $room ? 'Simpan Perubahan' : 'Tambah Ruangan' ?>
            </button>
        </div>
    </form>
</div>

</div>
