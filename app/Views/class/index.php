<div style="margin-bottom: 2rem;">
    <h2 style="color: var(--secondary);">Jadwal Kelas Saya Hari Ini</h2>
    <p style="color: var(--text-muted);"><?= date('l, d F Y') ?></p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">
    <?php if (empty($classes)): ?>
        <div style="grid-column: 1 / -1; background: var(--surface); padding: 3rem; border-radius: var(--radius); text-align: center; color: var(--text-muted); box-shadow: var(--shadow);">
            Anda tidak memiliki jadwal kelas hari ini.
        </div>
    <?php else: ?>
        <?php foreach ($classes as $c): ?>
            <?php 
                $session = $sessions[$c['id']] ?? null;
                $sessionStatus = $session ? $session['status'] : null;
                $hasCheckedIn = ($sessionStatus === 'checked_in' || $sessionStatus === 'ongoing');
                $isCancelled = ($sessionStatus === 'cancelled_by_lecturer' || $sessionStatus === 'cancelled_by_manager');
                
                $openCheckinTimestamp = strtotime(date('Y-m-d') . ' ' . $c['start_time']) - 900; // 15 mins before
                $endTimestamp = strtotime(date('Y-m-d') . ' ' . $c['end_time']);
                $nowTimestamp = time();
                
                $canCheckin = ($nowTimestamp >= $openCheckinTimestamp && $nowTimestamp <= $endTimestamp && !$hasCheckedIn && !$isCancelled);
                $isEarly = ($nowTimestamp < $openCheckinTimestamp);
                
                $borderColor = 'var(--primary)';
                if ($hasCheckedIn) $borderColor = 'var(--success)';
                if ($isCancelled) $borderColor = 'var(--danger)';
            ?>
            <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius); box-shadow: var(--shadow); border-left: 4px solid <?= $borderColor ?>; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                        <div>
                            <h3 style="margin-bottom: 0.25rem; font-size: 1.125rem; color: var(--text);"><?= \App\Support\Security::escape($c['course_name']) ?></h3>
                            <div style="color: var(--text-muted); font-size: 0.875rem;">
                                <?= \App\Support\Security::escape($c['course_code']) ?> - Kelas <?= \App\Support\Security::escape($c['class_name']) ?>
                            </div>
                        </div>
                        <div style="background: var(--bg-color); padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; color: var(--secondary);">
                            <?= \App\Support\Security::escape($c['room_name']) ?> (<?= \App\Support\Security::escape($c['room_code']) ?>)
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem; font-size: 0.875rem; color: var(--text-muted);">
                        <strong>Waktu:</strong> <?= date('H:i', strtotime($c['start_time'])) ?> - <?= date('H:i', strtotime($c['end_time'])) ?> WIB
                    </div>
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <?php if ($hasCheckedIn): ?>
                        <div style="background: var(--success-light); color: var(--success); padding: 0.75rem; border-radius: 8px; text-align: center; font-weight: 500; font-size: 0.875rem;">
                            ✓ Sudah Check-in (<?= !empty($session['actual_start_time']) ? date('H:i', strtotime($session['actual_start_time'])) : 'Terkonfirmasi' ?>)
                        </div>
                    <?php elseif ($sessionStatus === 'moved'): ?>
                        <div style="background: #e0f2fe; color: #0369a1; padding: 0.75rem; border-radius: 8px; text-align: center; font-weight: 500; font-size: 0.875rem;">
                            ⇄ Sesi Dipindahkan ke Ruang <?= \App\Support\Security::escape($session['target_room_name'] ?? 'Lain') ?> (<?= \App\Support\Security::escape($session['target_room_code'] ?? '') ?>)
                            <?php if (!empty($session['note'])): ?>
                                <div style="font-size: 0.75rem; margin-top: 0.25rem;">"<?= \App\Support\Security::escape($session['note']) ?>"</div>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($isCancelled): ?>
                        <div style="background: var(--danger-light); color: var(--danger); padding: 0.75rem; border-radius: 8px; text-align: center; font-weight: 500; font-size: 0.875rem;">
                            ✕ Sesi Dibatalkan (<?= $sessionStatus === 'cancelled_by_lecturer' ? 'Oleh Anda' : 'Oleh Pengelola' ?>)
                            <?php if (!empty($session['note'])): ?>
                                <div style="font-size: 0.75rem; margin-top: 0.25rem;">"<?= \App\Support\Security::escape($session['note']) ?>"</div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php if ($canCheckin): ?>
                            <form action="<?= \App\Support\Security::escape($baseUrl) ?>/class/checkin" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
                                <input type="hidden" name="schedule_id" value="<?= $c['id'] ?>">
                                <button type="submit" class="btn btn-primary" style="width: 100%;">Konfirmasi Hadir (Check-in)</button>
                            </form>
                        <?php elseif ($isEarly): ?>
                            <button class="btn btn-outline" style="width: 100%; opacity: 0.6; cursor: not-allowed;" disabled>
                                Check-in dibuka pukul <?= date('H:i', $openCheckinTimestamp) ?>
                            </button>
                        <?php else: ?>
                            <button class="btn btn-outline" style="width: 100%; opacity: 0.6; cursor: not-allowed;" disabled>
                                Waktu sesi telah lewat
                            </button>
                        <?php endif; ?>

                        <!-- Tombol Pindah Ruangan Kelas (FR-068) -->
                        <details style="font-size: 0.875rem;">
                            <summary style="color: var(--primary); cursor: pointer; text-align: center; padding: 0.25rem;">⇄ Pindahkan ke ruangan lain (FR-068)...</summary>
                            <form action="<?= \App\Support\Security::escape($baseUrl) ?>/class/move" method="POST" style="margin-top: 0.75rem; display: flex; flex-direction: column; gap: 0.5rem;" onsubmit="return confirm('Pindahkan sesi kelas ini ke ruangan lain? Ruangan asal akan menjadi kosong.');">
                                <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
                                <input type="hidden" name="schedule_id" value="<?= $c['id'] ?>">
                                <input type="hidden" name="session_date" value="<?= date('Y-m-d') ?>">
                                
                                <select name="target_room_id" required class="form-control" style="font-size: 0.8rem; padding: 0.4rem;">
                                    <option value="">-- Pilih Ruangan Tujuan --</option>
                                    <?php foreach ($allRooms as $ar): ?>
                                        <?php if ($ar['id'] != $c['room_id']): ?>
                                            <option value="<?= $ar['id'] ?>"><?= \App\Support\Security::escape($ar['name']) ?> (<?= \App\Support\Security::escape($ar['code']) ?> - Kap: <?= $ar['capacity'] ?>)</option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" name="reason" placeholder="Alasan pindah ruangan (misal: perlu lab)" class="form-control" style="font-size: 0.8rem; padding: 0.4rem;">
                                <button type="submit" class="btn btn-outline" style="width: 100%; font-size: 0.8rem; padding: 0.4rem; border-color: var(--primary); color: var(--primary);">Konfirmasi Pindah Ruangan</button>
                            </form>
                        </details>

                        <!-- Tombol Pembatalan Kelas (FR-061) -->
                        <details style="font-size: 0.875rem;">
                            <summary style="color: var(--danger); cursor: pointer; text-align: center; padding: 0.25rem;">Batalkan kelas hari ini...</summary>
                            <form action="<?= \App\Support\Security::escape($baseUrl) ?>/class/cancel" method="POST" style="margin-top: 0.75rem; display: flex; flex-direction: column; gap: 0.5rem;" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan sesi kelas ini? Ruangan akan langsung dibebaskan untuk peminjaman.');">
                                <input type="hidden" name="csrf_token" value="<?= \App\Support\Security::generateCsrfToken() ?>">
                                <input type="hidden" name="schedule_id" value="<?= $c['id'] ?>">
                                <input type="hidden" name="session_date" value="<?= date('Y-m-d') ?>">
                                <input type="text" name="reason" placeholder="Alasan pembatalan (opsional)" class="form-control" style="font-size: 0.8rem; padding: 0.4rem;">
                                <button type="submit" class="btn btn-danger" style="width: 100%; font-size: 0.8rem; padding: 0.4rem;">Konfirmasi Batalkan Kelas</button>
                            </form>
                        </details>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
