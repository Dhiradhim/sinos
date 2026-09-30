<?php
$edit = ! is_null($row);
$action = $edit ? base_url('user/update/' . $row->id) : base_url('user/simpan');
$v = function ($field, $default = '') use ($row) {
    return ($row && isset($row->$field)) ? html_escape($row->$field) : $default;
};
?>
<form method="post" action="<?= $action; ?>" class="space-y-5">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="form-label" for="nama">Nama Lengkap</label>
            <input class="form-input" type="text" id="nama" name="nama" value="<?= $v('nama'); ?>" required>
        </div>
        <div>
            <label class="form-label" for="nip">NIP / Username</label>
            <input class="form-input" type="text" id="nip" name="nip" value="<?= $v('nip'); ?>" required>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="form-label" for="jabatan">Jabatan</label>
            <select class="form-select" id="jabatan" name="jabatan" required>
                <option value="" hidden>Pilih Jabatan</option>
                <?php foreach ($jabatan as $j): ?>
                    <option value="<?= (int) $j->id; ?>" <?= ($row && (int) $row->id_jabatan === (int) $j->id) ? 'selected' : ''; ?>>
                        <?= html_escape($j->jabatan); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" for="aktif">Status</label>
            <select class="form-select" id="aktif" name="aktif" required>
                <option value="0" <?= ($row && (int) $row->aktif === 0) ? 'selected' : ''; ?>>Aktif</option>
                <option value="1" <?= ($row && (int) $row->aktif === 1) ? 'selected' : ''; ?>>Nonaktif</option>
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="form-label" for="pass1">Password <?= $edit ? '(kosongkan bila tidak diubah)' : ''; ?></label>
            <input class="form-input" type="password" id="pass1" name="pass1" <?= $edit ? '' : 'required'; ?>>
        </div>
        <div>
            <label class="form-label" for="pass2">Ulangi Password</label>
            <input class="form-input" type="password" id="pass2" name="pass2" <?= $edit ? '' : 'required'; ?>>
        </div>
    </div>

    <div class="flex justify-end gap-2 pt-2">
        <a href="<?= base_url('user'); ?>" class="btn-muted">Kembali</a>
        <button type="submit" class="btn-primary"><?= $edit ? 'Simpan Perubahan' : 'Simpan'; ?></button>
    </div>
</form>