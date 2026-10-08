<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">

<div style="margin-bottom: 2rem;">
    <h2 style="color: var(--secondary); margin-bottom: 0.25rem;">Cari Ruangan Kosong (FR-022)</h2>
    <p style="color: var(--text-muted); margin: 0;">Temukan ruangan yang bebas dari perkuliahan dan peminjaman pada tanggal dan rentang jam yang Anda inginkan.</p>
</div>

<!-- FORM PENCARIAN -->
<div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius); box-shadow: var(--shadow); margin-bottom: 2rem;">
    <form action="<?= \App\Support\Security::escape($baseUrl) ?>/free" method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) auto; gap: 1rem; align-items: flex-end;">
        <div>
            <label style="display: block; margin-bottom: 0.35rem; font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">Tanggal</label>
            <input type="date" name="date" value="<?= \App\Support\Security::escape($searchDate) ?>" required class="form-control" style="width: 100%; padding: 0.55rem; font-size: 0.9rem;">
        </div>

        <div>
            <label style="display: block; margin-bottom: 0.35rem; font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">Jam Mulai (WIB)</label>
            <input type="time" name="start_time" value="<?= \App\Support\Security::escape($startTime) ?>" required class="form-control" style="width: 100%; padding: 0.55rem; font-size: 0.9rem;">
        </div>

        <div>
            <label style="display: block; margin-bottom: 0.35rem; font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">Jam Selesai (WIB)</label>
            <input type="time" name="end_time" value="<?= \App\Support\Security::escape($endTime) ?>" required class="form-control" style="width: 100%; padding: 0.55rem; font-size: 0.9rem;">
        </div>

        <div>
            <label style="display: block; margin-bottom: 0.35rem; font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">Kapasitas Minimal</label>
            <input type="number" name="capacity" value="<?= $minCapacity ?: '' ?>" placeholder="Semua" class="form-control" style="width: 100%; padding: 0.55rem; font-size: 0.9rem;">
        </div>

        <div>
            <label style="display: block; margin-bottom: 0.35rem; font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">Gedung</label>
            <select name="building_id" class="form-control" style="width: 100%; padding: 0.55rem; font-size: 0.9rem;">
                <option value="0">Semua Gedung</option>
                <?php foreach ($buildings as $bldg): ?>
                    <option value="<?= $bldg['id'] ?>" <?= ((int)$buildingId === (int)$bldg['id']) ? 'selected' : '' ?>>
                        <?= \App\Support\Security::escape($bldg['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <button type="submit" class="btn btn-primary" style="padding: 0.6rem 1.25rem;">
                Cari Ruangan
            </button>
        </div>
    </form>
</div>

<!-- HASIL PENCARIAN -->
<?php if ($searched): ?>
    <div>
        <h3 style="margin-bottom: 1rem; color: var(--secondary); font-size: 1.15rem; display: flex; justify-content: space-between; align-items: center;">
            <span>Hasil Pencarian Ruangan Kosong</span>
            <span style="font-size: 0.875rem; color: var(--text-muted); font-weight: normal;">
                <?= date('d M Y', strtotime($searchDate)) ?> (<?= $startTime ?> - <?= $endTime ?> WIB)
            </span>
        </h3>

        <?php if (empty($rooms)): ?>
            <div style="background: var(--surface); border-radius: var(--radius); padding: 3.5rem 1rem; text-align: center; color: var(--text-muted); box-shadow: var(--shadow);">
                <p style="font-size: 1rem; margin-bottom: 0.5rem;">Tidak ada ruangan yang kosong pada rentang waktu tersebut.</p>
                <p style="font-size: 0.85rem; margin: 0;">Semua ruangan sedang digunakan untuk perkuliahan aktif atau peminjaman yang disetujui.</p>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem;">
                <?php foreach ($rooms as $r): ?>
                    <div style="background: var(--surface); border-radius: var(--radius); padding: 1.5rem; box-shadow: var(--shadow); border-left: 4px solid var(--success); display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                                <div>
                                    <h4 style="margin: 0 0 0.2rem 0; font-size: 1.1rem; color: var(--text);"><?= \App\Support\Security::escape($r['name']) ?> (<?= \App\Support\Security::escape($r['code']) ?>)</h4>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">Gedung <?= \App\Support\Security::escape($r['building_name']) ?>, Lantai <?= $r['floor'] ?></div>
                                </div>
                                <span class="badge badge-success" style="font-size: 0.75rem;">Kosong Penuh</span>
                            </div>

                            <div style="margin-bottom: 1rem; font-size: 0.85rem; color: var(--text-muted);">
                                <strong>Kapasitas:</strong> <?= $r['capacity'] ?> Orang • Tipe: <?= ucfirst($r['type']) ?>
                            </div>
                        </div>

                        <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                            <a href="<?= $baseUrl ?>/rooms/<?= $r['id'] ?>" class="btn btn-outline" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">Lihat Jadwal</a>
                            <?php if ($r['can_be_booked']): ?>
                                <a href="<?= $baseUrl ?>/booking/create?room_id=<?= $r['id'] ?>&booking_date=<?= $searchDate ?>&start_time=<?= $startTime ?>&end_time=<?= $endTime ?>" class="btn btn-primary" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
                                    Ajukan Pinjam
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

</div>
