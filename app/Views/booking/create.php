<div class="container" style="padding-top: 1.5rem; padding-bottom: 4rem;">
    <!-- Breadcrumb -->
    <div style="margin-bottom: 1.5rem; font-size: 0.85rem; color: var(--text-muted); display: flex; align-items: center; gap: 0.5rem;">
        <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/" style="color: var(--primary-container); font-weight: 500;">Beranda</a>
        <span>&rsaquo;</span>
        <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/rooms" style="color: var(--primary-container); font-weight: 500;">Layanan Peminjaman</a>
        <span>&rsaquo;</span>
        <span style="font-weight: 600; color: var(--text-main);">Formulir Pengajuan</span>
    </div>

    <!-- Header Section (Stitch Screen 4) -->
    <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                <span class="status-chip status-special" style="font-size: 0.7rem; padding: 0.2rem 0.65rem;">
                    <span class="status-chip-dot"></span> LAYANAN PERMOHONAN RESERVASI
                </span>
            </div>
            <h1 style="font-size: 2.25rem; font-weight: 800; color: var(--primary); margin: 0; line-height: 1.2;">
                Formulir Pengajuan Peminjaman Ruangan
            </h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin-top: 0.5rem; max-width: 800px;">
                Layanan permohonan reservasi ruang kuliah, lab, ruang seminar, dan auditorium di lingkungan Fakultas Teknik Unsoed untuk kegiatan akademik dan kemahasiswaan.
            </p>
        </div>

        <div>
            <span class="status-chip status-available" style="padding: 0.5rem 1rem; font-size: 0.8rem;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                <span>SSO Terverifikasi: <?= \App\Support\Auth::check() ? ucfirst(\App\Support\Security::escape($_SESSION['user_role'] ?? 'Pengguna')) : 'Sivitas FT' ?></span>
            </span>
        </div>
    </div>

    <!-- Rules & Policy Banner (Stitch Screen 4) -->
    <div style="background: var(--warning-light); border: 1px solid var(--warning-border); border-radius: var(--radius-lg); padding: 1.25rem 1.5rem; margin-bottom: 2.5rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.85rem; color: var(--warning); font-weight: 800; font-size: 0.95rem;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            <span>PERHATIAN ATURAN PEMINJAMAN RUANGAN FT UNSOED</span>
        </div>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; font-size: 0.825rem; color: #78350f;">
            <div style="border-left: 2px solid var(--secondary-container); padding-left: 0.75rem;">
                <strong>1. Batas Durasi Maksimal</strong>
                <p style="margin-top: 0.2rem; color: #92400e; line-height: 1.4;">Maksimal pemakaian 8 jam berturut-turut dalam 1 permohonan kegiatan (BR-001).</p>
            </div>
            <div style="border-left: 2px solid var(--secondary-container); padding-left: 0.75rem;">
                <strong>2. Jam Operasional Kampus</strong>
                <p style="margin-top: 0.2rem; color: #92400e; line-height: 1.4;">Hanya diizinkan pada rentang operasional 07.00 - 21.00 WIB (Senin - Sabtu).</p>
            </div>
            <div style="border-left: 2px solid var(--secondary-container); padding-left: 0.75rem;">
                <strong>3. Berkas & Lampiran Wajib</strong>
                <p style="margin-top: 0.2rem; color: #92400e; line-height: 1.4;">Wajib unggah Surat Pengantar resmi (PDF maks 2MB) dengan tanda tangan pimpinan/pembina.</p>
            </div>
        </div>
    </div>

    <!-- Alternatives Banner if Conflict exists (FR-078) -->
    <?php if (!empty($alternatives)): ?>
        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-left: 4px solid var(--primary-container); padding: 1.25rem 1.5rem; border-radius: var(--radius-lg); margin-bottom: 2rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--primary-container)" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <strong style="color: var(--primary); font-size: 0.95rem;">Saran Ruangan Alternatif yang Kosong (FR-078)</strong>
            </div>
            <p style="color: #1e40af; font-size: 0.85rem; margin-bottom: 0.85rem;">
                Ruangan yang Anda tuju sedang terisi jadwal lain atau kapasitas tidak mencukupi. Pilih salah satu alternatif ruangan kosong berikut:
            </p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 0.75rem;">
                <?php foreach ($alternatives as $alt): ?>
                    <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: var(--radius); padding: 0.75rem 1rem; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="color: var(--primary); font-size: 0.9rem;"><?= \App\Support\Security::escape($alt['name']) ?></strong>
                            <div style="color: var(--text-muted); font-size: 0.775rem;">Gedung <?= \App\Support\Security::escape($alt['building_name']) ?> &bull; Kapasitas <?= $alt['capacity'] ?> org</div>
                        </div>
                        <button type="button" onclick="selectAlternativeRoom(<?= $alt['id'] ?>)" class="btn btn-outline btn-sm">
                            Pilih Ini
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Grid: Left Form (Sections 1-4) & Right Context Sidebar -->
    <div style="display: grid; grid-template-columns: 1.8fr 1.2fr; gap: 2.5rem; align-items: start;">
        <!-- Left: 4-Step Form -->
        <form action="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/booking/store" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">

            <!-- SECTION 1: Lokasi & Penjadwalan Waktu -->
            <div style="background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm); margin-bottom: 1.75rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem; margin-bottom: 1.25rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span class="stitch-step-num" style="width: 28px; height: 28px; font-size: 0.8rem; margin: 0;">1</span>
                        <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--primary); margin: 0;">Lokasi & Penjadwalan Waktu</h3>
                    </div>
                    <span style="font-size: 0.75rem; color: var(--danger); font-weight: 600;">Wajib Diisi *</span>
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; margin-bottom: 0.4rem; font-weight: 700; font-size: 0.875rem; color: var(--text-main);">
                        Pilih Ruangan FT *
                    </label>
                    <select name="room_id" id="room_select" required class="stitch-select" style="font-size: 0.9rem;" onchange="updateSelectedRoomPreview(this)">
                        <option value="">-- Pilih Ruangan / Laboratorium --</option>
                        <?php foreach ($rooms as $r): ?>
                            <option value="<?= $r['id'] ?>" data-cap="<?= $r['capacity'] ?>" data-bldg="<?= \App\Support\Security::escape($r['building_name'] ?? '') ?>" data-name="<?= \App\Support\Security::escape($r['name']) ?>" <?= (isset($_GET['room_id']) && $_GET['room_id'] == $r['id']) ? 'selected' : '' ?>>
                                <?= \App\Support\Security::escape($r['name']) ?> (<?= \App\Support\Security::escape($r['code']) ?>) &mdash; Kapasitas: <?= $r['capacity'] ?> Orang
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.4rem; font-weight: 700; font-size: 0.875rem; color: var(--text-main);">
                            Tanggal Kegiatan *
                        </label>
                        <input type="date" name="booking_date" id="booking_date" required min="<?= date('Y-m-d') ?>" value="<?= \App\Support\Security::escape($_GET['date'] ?? date('Y-m-d')) ?>" class="stitch-input" style="padding-left: 0.85rem;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.4rem; font-weight: 700; font-size: 0.875rem; color: var(--text-main);">
                            Estimasi Jumlah Peserta *
                        </label>
                        <input type="number" name="participants_count" required min="1" placeholder="Contoh: 35 orang" class="stitch-input" style="padding-left: 0.85rem;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.4rem; font-weight: 700; font-size: 0.875rem; color: var(--text-main);">
                            Jam Mulai *
                        </label>
                        <input type="time" name="start_time" id="start_time" required value="<?= \App\Support\Security::escape($_GET['start'] ?? '08:00') ?>" class="stitch-input" style="padding-left: 0.85rem;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.4rem; font-weight: 700; font-size: 0.875rem; color: var(--text-main);">
                            Jam Selesai *
                        </label>
                        <input type="time" name="end_time" id="end_time" required value="<?= \App\Support\Security::escape($_GET['end'] ?? '12:00') ?>" class="stitch-input" style="padding-left: 0.85rem;">
                    </div>
                </div>

                <!-- Peminjaman Berulang Mingguan (FR-079) -->
                <div style="background: var(--surface-ground); border: 1px solid var(--border); padding: 1rem; border-radius: var(--radius); margin-top: 1rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; font-size: 0.875rem; cursor: pointer; color: var(--primary);">
                        <input type="checkbox" name="is_recurring" value="1" id="is_recurring_checkbox" onchange="toggleRecurringOptions(this.checked)">
                        <span>Peminjaman Berulang Mingguan (FR-079)</span>
                    </label>
                    <div id="recurring_details" style="display: none; margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px dashed var(--border);">
                        <label style="display: block; margin-bottom: 0.35rem; color: var(--text-muted); font-size: 0.8rem;">
                            Ulangi setiap pekan pada hari dan jam yang sama untuk:
                        </label>
                        <select name="recurring_weeks" class="stitch-select" style="width: auto; padding: 0.4rem 0.75rem; font-size: 0.85rem;">
                            <option value="2">2 Pekan berturut-turut</option>
                            <option value="3">3 Pekan berturut-turut</option>
                            <option value="4" selected>4 Pekan berturut-turut (1 Bulan)</option>
                            <option value="6">6 Pekan berturut-turut</option>
                            <option value="8">8 Pekan berturut-turut (Setengah Semester)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: Deskripsi & Klasifikasi Acara -->
            <div style="background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm); margin-bottom: 1.75rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem; margin-bottom: 1.25rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span class="stitch-step-num" style="width: 28px; height: 28px; font-size: 0.8rem; margin: 0;">2</span>
                        <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--primary); margin: 0;">Deskripsi & Klasifikasi Acara</h3>
                    </div>
                    <span style="font-size: 0.75rem; color: var(--danger); font-weight: 600;">Wajib Diisi *</span>
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; margin-bottom: 0.4rem; font-weight: 700; font-size: 0.875rem; color: var(--text-main);">
                        Jenis Kegiatan *
                    </label>
                    <select name="activity_type" required class="stitch-select">
                        <option value="public_lecture">Kuliah Tamu / Kuliah Umum</option>
                        <option value="seminar">Seminar Mahasiswa / Workshop</option>
                        <option value="meeting">Rapat Organisasi / Evaluasi</option>
                        <option value="organization">Kegiatan Ormawa / Himpunan</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; margin-bottom: 0.4rem; font-weight: 700; font-size: 0.875rem; color: var(--text-main);">
                        Nama Lengkap Kegiatan *
                    </label>
                    <input type="text" name="activity_name" required placeholder="Contoh: Workshop Cloud Architecture & DevOps 2025" class="stitch-input" style="padding-left: 0.85rem;">
                </div>

                <div style="margin-bottom: 0.5rem;">
                    <label style="display: block; margin-bottom: 0.4rem; font-weight: 700; font-size: 0.875rem; color: var(--text-main);">
                        Catatan Fasilitas & Kebutuhan Khusus
                    </label>
                    <textarea name="notes" rows="3" placeholder="Contoh: Memerlukan mikrofon wireless tambahan, kabel koneksi HDMI proyektor, atau tata kursi melingkar..." class="stitch-input" style="padding-left: 0.85rem; height: auto;"></textarea>
                </div>
            </div>

            <!-- SECTION 3: Identitas Pemohon (Data SSO FT UNSOED) -->
            <div style="background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm); margin-bottom: 1.75rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem; margin-bottom: 1.25rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span class="stitch-step-num" style="width: 28px; height: 28px; font-size: 0.8rem; margin: 0;">3</span>
                        <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--primary); margin: 0;">Identitas Pemohon (Data SSO FT)</h3>
                    </div>
                    <span style="font-size: 0.75rem; color: var(--success); font-weight: 600;">Tersinkronisasi &check;</span>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.4rem; font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">Nama Pemohon</label>
                        <input type="text" readonly value="<?= \App\Support\Security::escape($_SESSION['user_name'] ?? 'Mahasiswa FT Unsoed') ?>" class="stitch-input" style="background: var(--surface-ground); font-weight: 600; padding-left: 0.85rem;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.4rem; font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">Nomor Kontak WhatsApp Aktif *</label>
                        <input type="text" name="contact_person" required placeholder="Contoh: 081234567890" value="081234567890" class="stitch-input" style="padding-left: 0.85rem;">
                    </div>
                </div>
            </div>

            <!-- SECTION 4: Lampiran Surat Izin / Proposal Resmi (PDF) -->
            <div style="background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm); margin-bottom: 1.75rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem; margin-bottom: 1.25rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span class="stitch-step-num" style="width: 28px; height: 28px; font-size: 0.8rem; margin: 0;">4</span>
                        <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--primary); margin: 0;">Lampiran Surat Izin / Proposal Resmi</h3>
                    </div>
                    <span style="font-size: 0.75rem; color: var(--primary); font-weight: 600;">Format PDF</span>
                </div>

                <!-- Drag & Drop Upload Zone (Stitch Screen 4) -->
                <div style="border: 2px dashed #cbd5e1; border-radius: var(--radius-lg); padding: 2rem 1.5rem; text-align: center; background: var(--surface-ground); transition: var(--transition);">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--primary-container)" stroke-width="1.5" style="margin-bottom: 0.75rem;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    <div style="font-weight: 700; color: var(--primary); font-size: 0.95rem; margin-bottom: 0.25rem;">
                        Tarik & Letakkan Berkas atau Klik untuk Memilih
                    </div>
                    <div style="font-size: 0.775rem; color: var(--text-muted); margin-bottom: 1rem;">
                        Wajib melampirkan Surat Pengantar yang telah disetujui (Format PDF, maksimal 2 MB).
                    </div>
                    <input type="file" name="attachment" accept=".pdf" class="stitch-input" style="padding: 0.4rem; max-width: 320px; margin: 0 auto; display: block; font-size: 0.825rem;">
                </div>
            </div>

            <!-- Disclaimer & Submit Bar -->
            <div style="background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm); margin-bottom: 2rem;">
                <label style="display: flex; align-items: flex-start; gap: 0.75rem; font-size: 0.825rem; color: var(--text-muted); cursor: pointer; line-height: 1.5; margin-bottom: 1.5rem;">
                    <input type="checkbox" required style="margin-top: 0.25rem; width: 16px; height: 16px;">
                    <span>Saya menyatakan bahwa seluruh data yang diisi di atas adalah benar. Saya bertanggung jawab penuh terhadap ketertiban, kebersihan, serta keutuhan inventaris fasilitas Fakultas Teknik Unsoed selama waktu kegiatan berlangsung.</span>
                </label>

                <div style="display: flex; justify-content: flex-end; gap: 1rem; align-items: center;">
                    <a href="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/" class="btn btn-outline">
                        Batal & Kembali
                    </a>
                    <button type="submit" class="btn btn-primary" style="padding: 0.85rem 1.75rem; font-size: 0.95rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                        <span>Kirim Pengajuan Peminjaman</span>
                    </button>
                </div>
            </div>
        </form>

        <!-- Right Context Sidebar (Stitch Screen 4) -->
        <div style="position: sticky; top: 5.5rem; display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Selected Room Card Preview -->
            <div style="background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm);">
                <div style="height: 140px; background: linear-gradient(135deg, #001e40 0%, #003366 100%); position: relative;">
                    <img src="<?= \App\Support\Security::escape($baseUrl ?? '/webpinjamft/public') ?>/img/stitch/02_detail_ruangan_lab_rpl.png" style="width: 100%; height: 100%; object-fit: cover; opacity: 0.85;">
                    <div style="position: absolute; top: 0.75rem; left: 0.75rem;">
                        <span class="status-chip status-available" style="font-size: 0.65rem; padding: 0.15rem 0.5rem;">
                            <span class="status-chip-dot"></span> Terverifikasi
                        </span>
                    </div>
                </div>

                <div style="padding: 1.25rem;">
                    <h3 id="previewRoomName" style="font-size: 1.1rem; font-weight: 800; color: var(--primary); margin: 0 0 0.25rem;">
                        Ruangan Pilihan
                    </h3>
                    <div id="previewRoomBldg" style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.85rem;">
                        Pilih ruangan pada formulir untuk melihat pratinjau spesifikasi.
                    </div>

                    <div class="stitch-specs-grid" style="margin-bottom: 0;">
                        <div class="stitch-spec-item">
                            <span>Kapasitas: <strong id="previewRoomCap">-</strong></span>
                        </div>
                        <div class="stitch-spec-item">
                            <span>Fasilitas: <strong>AC, Proyektor</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4-Step Approval Tracker Flow (Stitch Screen 4) -->
            <div style="background: #ffffff; border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.25rem; box-shadow: var(--shadow-sm);">
                <h4 style="font-size: 0.95rem; font-weight: 800; color: var(--primary); margin: 0 0 1rem;">
                    Alur Verifikasi Persetujuan
                </h4>
                
                <div style="display: flex; flex-direction: column; gap: 0.85rem; font-size: 0.8rem;">
                    <div style="display: flex; align-items: center; gap: 0.6rem; color: var(--primary); font-weight: 700;">
                        <span style="width: 22px; height: 22px; background: var(--primary-container); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.7rem;">1</span>
                        <span>Kirim Formulir & Masuk Antrean Sistem</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.6rem; color: var(--text-muted);">
                        <span style="width: 22px; height: 22px; background: var(--surface-container); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.7rem;">2</span>
                        <span>Verifikasi Surat Pengantar oleh Subbag Akademik</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.6rem; color: var(--text-muted);">
                        <span style="width: 22px; height: 22px; background: var(--surface-container); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.7rem;">3</span>
                        <span>Persetujuan Bagian Umum Sarpras FT</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.6rem; color: var(--text-muted);">
                        <span style="width: 22px; height: 22px; background: var(--surface-container); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.7rem;">4</span>
                        <span>Penerbitan QR / Notifikasi Pinjam via WhatsApp</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function selectAlternativeRoom(roomId) {
    const sel = document.getElementById('room_select');
    if (sel) {
        sel.value = roomId;
        updateSelectedRoomPreview(sel);
        sel.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

function updateSelectedRoomPreview(sel) {
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.value) {
        document.getElementById('previewRoomName').innerText = opt.getAttribute('data-name') || opt.text;
        document.getElementById('previewRoomBldg').innerText = 'Gedung ' + (opt.getAttribute('data-bldg') || 'FT') + ' Kampus Blater';
        document.getElementById('previewRoomCap').innerText = (opt.getAttribute('data-cap') || '-') + ' Orang';
    }
}

function toggleRecurringOptions(checked) {
    const el = document.getElementById('recurring_details');
    if (el) el.style.display = checked ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('room_select');
    if (sel && sel.value) updateSelectedRoomPreview(sel);
});
</script>
