<div class="container" style="padding-top: 1.5rem; padding-bottom: 4rem;">
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="color: var(--secondary); margin-bottom: 0.25rem;">Impor Jadwal Kuliah (Bapendik)</h2>
        <p style="color: var(--text-muted); margin: 0;">Kelola unggahan berkas CSV jadwal semester, pratinjau validasi, dan riwayat impor.</p>
    </div>
    <a href="<?= \App\Support\Security::escape($baseUrl) ?>/admin/import/template" class="btn btn-outline" style="font-size: 0.875rem;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 0.25rem;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
        Unduh Template CSV
    </a>
</div>

<!-- SECTION 1: FORM UNGGAH & PRATINJAU -->
<div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius); box-shadow: var(--shadow); margin-bottom: 2rem;">
    <h3 style="margin-bottom: 1rem; color: var(--secondary); font-size: 1.15rem;">Unggah Berkas Jadwal (CSV / Excel .xlsx) (FR-040, FR-041, FR-046)</h3>
    
    <div style="background: var(--bg-color); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.85rem; border-left: 3px solid var(--primary);">
        <strong>Petunjuk Kolom Wajib (PRD 8.5):</strong><br>
        <code>course_code,course_name,class_name,lecturer_name,lecturer_ref,weekday,start_time,end_time,room_code,attendees</code><br>
        <span style="color: var(--text-muted);">Mendukung berkas <strong>.csv</strong> atau <strong>.xlsx</strong> (membaca sheet pertama). Format waktu: HH:MM (contoh: 08:00). Format hari: Nama Indonesia (Senin-Minggu) atau angka 1-7. Maksimal ukuran 5 MB.</span>
    </div>

    <form action="<?= \App\Support\Security::escape($baseUrl) ?>/admin/import/preview" method="POST" enctype="multipart/form-data" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 1rem; align-items: flex-end;">
        <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
        
        <div>
            <label style="display: block; margin-bottom: 0.4rem; color: var(--text-muted); font-size: 0.85rem; font-weight: 500;">Pilih Semester</label>
            <select name="semester_id" required class="form-control" style="width: 100%; padding: 0.6rem; font-size: 0.9rem;">
                <?php foreach ($semesters as $sem): ?>
                    <option value="<?= $sem['id'] ?>" <?= $sem['is_active'] ? 'selected' : '' ?>>
                        <?= \App\Support\Security::escape($sem['name']) ?> <?= $sem['is_active'] ? '(Aktif)' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label style="display: block; margin-bottom: 0.4rem; color: var(--text-muted); font-size: 0.85rem; font-weight: 500;">Berkas CSV / Excel (.xlsx)</label>
            <input type="file" name="csv_file" accept=".csv, .xlsx" required class="form-control" style="width: 100%; padding: 0.45rem; font-size: 0.85rem;">
        </div>

        <div>
            <button type="submit" class="btn btn-primary" style="padding: 0.65rem 1.25rem; font-size: 0.9rem;">
                Validasi & Pratinjau
            </button>
        </div>
    </form>
</div>

<!-- SECTION 2: TAMPILAN PRATINJAU JIKA ADA (FR-041, FR-042) -->
<?php if ($previewData): ?>
    <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius); box-shadow: var(--shadow); margin-bottom: 2rem; border-top: 4px solid var(--primary);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <div>
                <h3 style="color: var(--secondary); margin-bottom: 0.25rem;">Hasil Pratinjau Validasi: <?= \App\Support\Security::escape($previewData['filename']) ?></h3>
                <div style="display: flex; gap: 1rem; font-size: 0.875rem;">
                    <span style="color: var(--success); font-weight: 600;">✓ <?= $previewData['valid_count'] ?> Baris Valid</span>
                    <span style="color: <?= $previewData['invalid_count'] > 0 ? 'var(--danger)' : 'var(--text-muted)' ?>; font-weight: 600;">
                        ⚠ <?= $previewData['invalid_count'] ?> Baris Bermasalah
                    </span>
                    <span style="color: var(--text-muted);">Total: <?= $previewData['total_rows'] ?> Baris</span>
                </div>
            </div>
            
            <form action="<?= \App\Support\Security::escape($baseUrl) ?>/admin/import/cancel" method="POST">
                <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
                <button type="submit" class="btn btn-outline" style="font-size: 0.85rem; color: var(--danger); border-color: var(--danger);">
                    Batal Pratinjau
                </button>
            </form>
        </div>

        <!-- Daftar Baris Bermasalah (jika ada) -->
        <?php if (!empty($previewData['invalid_rows'])): ?>
            <div style="background: rgba(239, 68, 68, 0.05); border: 1px solid var(--danger); border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;">
                <h4 style="color: var(--danger); margin-bottom: 0.5rem; font-size: 0.95rem;">Daftar Baris Bermasalah (Tidak Dapat Diimpor):</h4>
                <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.85rem; color: var(--danger);">
                    <?php foreach ($previewData['invalid_rows'] as $inv): ?>
                        <li style="margin-bottom: 0.25rem;">
                            <strong>Baris #<?= $inv['row_number'] ?></strong> (<?= \App\Support\Security::escape($inv['course_code']) ?> - <?= \App\Support\Security::escape($inv['course_name']) ?>):
                            <?= implode(', ', $inv['errors']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Form Terapkan Impor (FR-042) -->
        <form action="<?= \App\Support\Security::escape($baseUrl) ?>/admin/import/commit" method="POST" style="background: var(--bg-color); padding: 1.25rem; border-radius: 8px;">
            <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
            
            <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem;">
                <div style="display: flex; gap: 1.5rem; align-items: center;">
                    <div>
                        <label style="font-size: 0.85rem; font-weight: 600; margin-bottom: 0.25rem; display: block;">Mode Penerapan:</label>
                        <label style="margin-right: 1rem; font-size: 0.85rem;">
                            <input type="radio" name="mode" value="replace" checked> Ganti seluruh jadwal semester (Rekomendasi)
                        </label>
                        <label style="font-size: 0.85rem;">
                            <input type="radio" name="mode" value="append"> Tambah/Perbarui saja
                        </label>
                    </div>

                    <?php if ($previewData['invalid_count'] > 0): ?>
                        <div style="border-left: 2px solid var(--border); padding-left: 1rem;">
                            <label style="font-size: 0.85rem; color: var(--text); font-weight: 600; cursor: pointer;">
                                <input type="checkbox" name="allow_valid_only" value="1">
                                Abaikan baris bermasalah & terapkan baris valid saja (FR-042)
                            </label>
                        </div>
                    <?php endif; ?>
                </div>

                <div>
                    <button type="submit" class="btn btn-primary" style="padding: 0.6rem 1.5rem;" onclick="return confirm('Apakah Anda yakin ingin menerapkan jadwal ini ke database?');">
                        Terapkan Impor Sekarang
                    </button>
                </div>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- SECTION 3: RIWAYAT IMPOR JADWAL & ROLLBACK (FR-043, FR-044) -->
<div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius); box-shadow: var(--shadow);">
    <h3 style="margin-bottom: 1rem; color: var(--secondary); font-size: 1.15rem;">Riwayat Impor Jadwal Kuliah (FR-044)</h3>
    
    <?php if (empty($history)): ?>
        <div style="text-align: center; color: var(--text-muted); padding: 2.5rem 0;">
            Belum ada riwayat impor jadwal yang tercatat.
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
                <thead>
                    <tr style="background: var(--bg-color); border-bottom: 2px solid var(--border);">
                        <th style="padding: 0.75rem 1rem;">Waktu</th>
                        <th style="padding: 0.75rem 1rem;">Semester</th>
                        <th style="padding: 0.75rem 1rem;">Nama Berkas</th>
                        <th style="padding: 0.75rem 1rem;">Pengunggah</th>
                        <th style="padding: 0.75rem 1rem;">Baris Valid</th>
                        <th style="padding: 0.75rem 1rem;">Mode</th>
                        <th style="padding: 0.75rem 1rem;">Status</th>
                        <th style="padding: 0.75rem 1rem; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $h): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 0.75rem 1rem; color: var(--text-muted);"><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></td>
                            <td style="padding: 0.75rem 1rem; font-weight: 500;"><?= \App\Support\Security::escape($h['semester_name']) ?></td>
                            <td style="padding: 0.75rem 1rem;"><code><?= \App\Support\Security::escape($h['filename']) ?></code></td>
                            <td style="padding: 0.75rem 1rem;"><?= \App\Support\Security::escape($h['uploader_name']) ?></td>
                            <td style="padding: 0.75rem 1rem; font-weight: 600;"><?= $h['valid_rows'] ?></td>
                            <td style="padding: 0.75rem 1rem;"><?= ucfirst($h['mode']) ?></td>
                            <td style="padding: 0.75rem 1rem;">
                                <?php if ($h['status'] === 'committed'): ?>
                                    <span class="badge badge-success">Aktif</span>
                                <?php elseif ($h['status'] === 'rolled_back'): ?>
                                    <span class="badge badge-danger">Rolled Back</span>
                                <?php else: ?>
                                    <span class="badge badge-warning"><?= ucfirst($h['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.75rem 1rem; text-align: right;">
                                <?php if ($h['status'] === 'committed'): ?>
                                    <form action="<?= \App\Support\Security::escape($baseUrl) ?>/admin/import/rollback" method="POST" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin melakukan ROLLBACK pada batch impor ini? Jadwal akan dinonaktifkan.');">
                                        <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
                                        <input type="hidden" name="import_id" value="<?= $h['id'] ?>">
                                        <button type="submit" class="btn btn-outline" style="font-size: 0.75rem; padding: 0.25rem 0.6rem; color: var(--danger); border-color: var(--danger);">
                                            Rollback
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 0.75rem;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

</div>
