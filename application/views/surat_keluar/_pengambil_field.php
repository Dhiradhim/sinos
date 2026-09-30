<?php

/**
 * Partial: pemilihan pengambil nomor.
 * Untuk admin tampil dropdown; untuk user biasa tampil hidden input.
 * Variabel: $user, $pengambil
 */
?>
<?php if (! empty($user['is_admin'])): ?>
    <div>
        <label class="label" for="nip">Pengambil Nomor</label>
        <select class="select" id="nip" name="nip" required>
            <option value="" hidden>Pilih Pengambil Nomor</option>
            <?php foreach ($pengambil as $p): ?>
                <option value="<?= html_escape($p->nip); ?>"><?= html_escape($p->nama); ?></option>
            <?php endforeach; ?>
        </select>
        <p class="mt-1.5 text-xs text-muted-foreground">Nomor surat akan tercatat atas nama pengambil terpilih.</p>
    </div>
<?php else: ?>
    <input type="hidden" name="nip" value="<?= html_escape($user['nip']); ?>">
<?php endif; ?>