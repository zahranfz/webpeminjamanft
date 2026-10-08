<div class="container" style="padding-top: 1.5rem; padding-bottom: 4rem;">
<!-- Breadcrumb & Header -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 class="page-title" style="margin-bottom: 0.25rem;">Kelola Data Gedung (FR-050)</h1>
        <p class="page-subtitle" style="margin: 0;">Master data gedung Fakultas Teknik Universitas Jenderal Soedirman.</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="<?= \App\Support\Security::escape($baseUrl) ?>/admin" class="btn btn-outline" style="font-size: 0.85rem;">← Kembali ke Dashboard</a>
        <button class="btn btn-primary" onclick="showAddBuilding()" style="font-size: 0.85rem;">+ Tambah Gedung</button>
    </div>
</div>

<!-- Layout: Grid with Table & Form -->
<div style="display: grid; grid-template-columns: 2fr 1.2fr; gap: 1.5rem; align-items: start;">
    <!-- Buildings Table -->
    <div class="card" style="padding: 0; overflow: hidden;">
        <div style="padding: 1rem 1.25rem; background: var(--surface); border-bottom: 1px solid var(--border); font-weight: 600;">
            Daftar Gedung Fakultas Teknik
        </div>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
                <thead>
                    <tr style="background: var(--surface); border-bottom: 2px solid var(--border); color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase;">
                        <th style="padding: 0.85rem 1rem;">Kode</th>
                        <th style="padding: 0.85rem 1rem;">Nama Gedung</th>
                        <th style="padding: 0.85rem 1rem; text-align: center;">Jumlah Ruangan</th>
                        <th style="padding: 0.85rem 1rem; text-align: center;">Status</th>
                        <th style="padding: 0.85rem 1rem; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($buildings)): ?>
                        <tr>
                            <td colspan="5" style="padding: 2.5rem 1rem; text-align: center; color: var(--text-muted);">
                                Belum ada gedung terdaftar. Silakan tambahkan pada formulir.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($buildings as $b): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 0.85rem 1rem; font-weight: 700; color: var(--primary);">
                                    <?= \App\Support\Security::escape($b['code']) ?>
                                </td>
                                <td style="padding: 0.85rem 1rem; font-weight: 600;">
                                    <?= \App\Support\Security::escape($b['name']) ?>
                                </td>
                                <td style="padding: 0.85rem 1rem; text-align: center;">
                                    <a href="<?= \App\Support\Security::escape($baseUrl) ?>/admin/rooms?building_id=<?= $b['id'] ?>" style="text-decoration: none; font-weight: 600;">
                                        <?= (int)$b['total_rooms'] ?> ruangan (<?= (int)$b['active_rooms'] ?> aktif)
                                    </a>
                                </td>
                                <td style="padding: 0.85rem 1rem; text-align: center;">
                                    <?php if ((int)$b['is_active'] === 1): ?>
                                        <span class="badge badge-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 0.85rem 1rem; text-align: right; white-space: nowrap;">
                                    <div style="display: flex; gap: 0.4rem; justify-content: flex-end;">
                                        <button type="button" class="btn btn-outline" style="padding: 0.25rem 0.55rem; font-size: 0.75rem;"
                                                onclick="editBuilding(<?= $b['id'] ?>, '<?= \App\Support\Security::escape($b['code']) ?>', '<?= \App\Support\Security::escape($b['name']) ?>', <?= (int)$b['is_active'] ?>)">
                                            Edit
                                        </button>
                                        <form method="POST" action="<?= \App\Support\Security::escape($baseUrl) ?>/admin/buildings/delete" style="margin: 0;" onsubmit="return confirm('Hapus gedung ini? (Akan ditolak jika masih memiliki data ruangan sesuai BR-016)');">
                                            <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
                                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
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

    <!-- Building Form -->
    <div class="card" style="padding: 1.5rem;">
        <h3 id="formBuildingTitle" style="margin-top: 0; margin-bottom: 1rem; font-size: 1.1rem;">Tambah Gedung</h3>
        <form method="POST" action="<?= \App\Support\Security::escape($baseUrl) ?>/admin/buildings/save">
            <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
            <input type="hidden" name="id" id="bldgId" value="0">

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                    Kode Gedung <span style="color: var(--danger);">*</span>
                </label>
                <input type="text" name="code" id="bldgCode" class="search-input" style="padding: 0.6rem 0.75rem; width: 100%; box-sizing: border-box;" 
                       placeholder="Contoh: A, B, C, LAB, DKN" required>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                    Nama Gedung <span style="color: var(--danger);">*</span>
                </label>
                <input type="text" name="name" id="bldgName" class="search-input" style="padding: 0.6rem 0.75rem; width: 100%; box-sizing: border-box;" 
                       placeholder="Contoh: Gedung A, Gedung Dekanat..." required>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem;">
                    <input type="checkbox" name="is_active" id="bldgActive" value="1" checked>
                    <span><strong>Gedung Aktif Operasional</strong></span>
                </label>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button type="button" class="btn btn-outline" id="btnCancelBldg" style="display: none;" onclick="showAddBuilding()">Batal Edit</button>
                <button type="submit" class="btn btn-primary" id="btnSubmitBldg">Simpan Gedung</button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddBuilding() {
    document.getElementById('formBuildingTitle').innerText = 'Tambah Gedung';
    document.getElementById('bldgId').value = '0';
    document.getElementById('bldgCode').value = '';
    document.getElementById('bldgName').value = '';
    document.getElementById('bldgActive').checked = true;
    document.getElementById('btnSubmitBldg').innerText = 'Simpan Gedung';
    document.getElementById('btnCancelBldg').style.display = 'none';
}

function editBuilding(id, code, name, isActive) {
    document.getElementById('formBuildingTitle').innerText = 'Edit Gedung';
    document.getElementById('bldgId').value = id;
    document.getElementById('bldgCode').value = code;
    document.getElementById('bldgName').value = name;
    document.getElementById('bldgActive').checked = (isActive === 1);
    document.getElementById('btnSubmitBldg').innerText = 'Perbarui Gedung';
    document.getElementById('btnCancelBldg').style.display = 'inline-block';
}
</script>

</div>
