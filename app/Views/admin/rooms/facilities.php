<div class="container" style="padding-top: 1.5rem; padding-bottom: 4rem;">
<!-- Breadcrumb & Header -->
<div style="margin-bottom: 1.5rem;">
    <a href="<?= \App\Support\Security::escape($baseUrl) ?>/admin/rooms" style="text-decoration: none; color: var(--primary); font-size: 0.85rem; font-weight: 600;">
        ← Kembali ke Kelola Ruangan
    </a>
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 0.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 class="page-title" style="margin-bottom: 0.25rem;">
                Fasilitas Ruangan: <?= \App\Support\Security::escape($room['name']) ?> (<?= \App\Support\Security::escape($room['code']) ?>)
            </h1>
            <p class="page-subtitle" style="margin: 0;">
                Gedung <?= \App\Support\Security::escape($room['building_name']) ?>, Lantai <?= (int)$room['floor'] ?> | Kapasitas: <?= (int)$room['capacity'] ?> orang
            </p>
        </div>
        <button class="btn btn-primary" onclick="showAddForm()" style="font-size: 0.85rem;">+ Tambah Fasilitas</button>
    </div>
</div>

<!-- Grid: List and Add/Edit Form -->
<div style="display: grid; grid-template-columns: 2fr 1.2fr; gap: 1.5rem; align-items: start;">
    <!-- Facilities Table -->
    <div class="card" style="padding: 0; overflow: hidden;">
        <div style="padding: 1rem 1.25rem; background: var(--surface); border-bottom: 1px solid var(--border); font-weight: 600;">
            Daftar Inventaris Fasilitas Ruangan (FR-051)
        </div>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
                <thead>
                    <tr style="background: var(--surface); border-bottom: 2px solid var(--border); color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase;">
                        <th style="padding: 0.85rem 1rem;">Jenis Fasilitas</th>
                        <th style="padding: 0.85rem 1rem; text-align: center;">Jumlah</th>
                        <th style="padding: 0.85rem 1rem; text-align: center;">Kondisi</th>
                        <th style="padding: 0.85rem 1rem; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($facilities)): ?>
                        <tr>
                            <td colspan="4" style="padding: 2.5rem 1rem; text-align: center; color: var(--text-muted);">
                                Belum ada fasilitas terdaftar untuk ruangan ini. Silakan tambahkan pada formulir di sebelah kanan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($facilities as $f): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 0.85rem 1rem; font-weight: 600;">
                                    <?= \App\Support\Security::escape($f['type']) ?>
                                </td>
                                <td style="padding: 0.85rem 1rem; text-align: center;">
                                    <span class="badge" style="background: #e2e8f0; color: #1e293b; font-weight: 600;">
                                        <?= (int)$f['quantity'] ?> unit
                                    </span>
                                </td>
                                <td style="padding: 0.85rem 1rem; text-align: center;">
                                    <?php
                                    $condLabels = [
                                        'good' => ['Baik', 'badge-success'],
                                        'damaged' => ['Rusak', 'badge-danger'],
                                        'lost' => ['Hilang', 'badge-secondary'],
                                        'under_repair' => ['Dalam Perbaikan', 'badge-warning']
                                    ];
                                    $cond = $condLabels[$f['condition']] ?? [$f['condition'], 'badge-info'];
                                    ?>
                                    <span class="badge <?= $cond[1] ?>"><?= $cond[0] ?></span>
                                </td>
                                <td style="padding: 0.85rem 1rem; text-align: right; white-space: nowrap;">
                                    <div style="display: flex; gap: 0.4rem; justify-content: flex-end;">
                                        <button type="button" class="btn btn-outline" style="padding: 0.25rem 0.55rem; font-size: 0.75rem;" 
                                                onclick="editFacility(<?= $f['id'] ?>, '<?= \App\Support\Security::escape($f['type']) ?>', <?= (int)$f['quantity'] ?>, '<?= $f['condition'] ?>')">
                                            Edit
                                        </button>
                                        <form method="POST" action="<?= \App\Support\Security::escape($baseUrl) ?>/admin/facilities/<?= $f['id'] ?>/delete" style="margin: 0;" onsubmit="return confirm('Hapus fasilitas ini dari ruangan?');">
                                            <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
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

    <!-- Add / Edit Form Card -->
    <div class="card" style="padding: 1.5rem;">
        <h3 id="formTitle" style="margin-top: 0; margin-bottom: 1rem; font-size: 1.1rem;">Tambah Fasilitas</h3>
        <form method="POST" action="<?= \App\Support\Security::escape($baseUrl) ?>/admin/rooms/<?= $room['id'] ?>/facilities/save">
            <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
            <input type="hidden" name="id" id="facilityId" value="0">

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                    Jenis Fasilitas <span style="color: var(--danger);">*</span>
                </label>
                <input type="text" name="type" id="facilityType" class="search-input" style="padding: 0.6rem 0.75rem; width: 100%; box-sizing: border-box;" 
                       placeholder="Contoh: Proyektor LCD, AC, Stopkontak, Whiteboard, Mic..." required>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                    Jumlah Unit <span style="color: var(--danger);">*</span>
                </label>
                <input type="number" name="quantity" id="facilityQty" class="search-input" style="padding: 0.6rem 0.75rem; width: 100%; box-sizing: border-box;" 
                       min="1" max="100" value="1" required>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                    Kondisi <span style="color: var(--danger);">*</span>
                </label>
                <select name="condition" id="facilityCondition" class="filter-select" style="width: 100%;" required>
                    <option value="good">Baik (good)</option>
                    <option value="damaged">Rusak (damaged)</option>
                    <option value="under_repair">Dalam Perbaikan (under_repair)</option>
                    <option value="lost">Hilang (lost)</option>
                </select>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button type="button" class="btn btn-outline" id="btnCancelEdit" style="display: none;" onclick="showAddForm()">Batal Edit</button>
                <button type="submit" class="btn btn-primary" id="btnSubmitForm">Simpan Fasilitas</button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddForm() {
    document.getElementById('formTitle').innerText = 'Tambah Fasilitas';
    document.getElementById('facilityId').value = '0';
    document.getElementById('facilityType').value = '';
    document.getElementById('facilityQty').value = '1';
    document.getElementById('facilityCondition').value = 'good';
    document.getElementById('btnSubmitForm').innerText = 'Simpan Fasilitas';
    document.getElementById('btnCancelEdit').style.display = 'none';
}

function editFacility(id, type, qty, cond) {
    document.getElementById('formTitle').innerText = 'Edit Fasilitas';
    document.getElementById('facilityId').value = id;
    document.getElementById('facilityType').value = type;
    document.getElementById('facilityQty').value = qty;
    document.getElementById('facilityCondition').value = cond;
    document.getElementById('btnSubmitForm').innerText = 'Perbarui Fasilitas';
    document.getElementById('btnCancelEdit').style.display = 'inline-block';
}
</script>

</div>
