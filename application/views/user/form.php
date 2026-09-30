<?php
$edit = ! is_null($row);
$action = $edit ? base_url('user/update/' . $row->id) : base_url('user/simpan');
$v = function ($field, $default = '') use ($row) {
    return ($row && isset($row->$field)) ? html_escape($row->$field) : $default;
};
?>
<div class="mx-auto w-full max-w-2xl">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= $edit ? 'Edit User' : 'Tambah User'; ?></h2>
            <p class="card-description"><?= $edit ? 'Perbarui data pengguna.' : 'Lengkapi data pengguna baru.'; ?></p>
        </div>
        <div class="card-content">
            <form method="post" action="<?= $action; ?>" class="space-y-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="label" for="nama">Nama Lengkap</label>
                        <input class="input" type="text" id="nama" name="nama" value="<?= $v('nama'); ?>" required>
                    </div>
                    <div>
                        <label class="label" for="nip">NIP / Username</label>
                        <input class="input" type="text" id="nip" name="nip" value="<?= $v('nip'); ?>" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="label" for="jabatan">Jabatan</label>
                        <select class="select" id="jabatan" name="jabatan" required>
                            <option value="" hidden>Pilih Jabatan</option>
                            <?php foreach ($jabatan as $j): ?>
                                <option value="<?= (int) $j->id; ?>" <?= ($row && (int) $row->id_jabatan === (int) $j->id) ? 'selected' : ''; ?>>
                                    <?= html_escape($j->jabatan); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label" for="aktif">Status</label>
                        <select class="select" id="aktif" name="aktif" required>
                            <option value="0" <?= ($row && (int) $row->aktif === 0) ? 'selected' : ''; ?>>Aktif</option>
                            <option value="1" <?= ($row && (int) $row->aktif === 1) ? 'selected' : ''; ?>>Nonaktif</option>
                        </select>
                    </div>
                </div>

                <label class="flex items-start gap-3 rounded-lg border border-border p-3">
                    <input type="checkbox" name="operator" value="1" class="mt-0.5 h-4 w-4 rounded border-input text-primary focus:ring-ring"
                        <?= ($row && (int) $row->operator === 1) ? 'checked' : ''; ?>>
                    <span class="text-sm">
                        <span class="font-medium text-foreground">Operator Surat</span>
                        <span class="block text-xs text-muted-foreground">Hanya operator yang berhak mengirim disposisi dan mengarsipkan surat masuk.</span>
                    </span>
                </label>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="label" for="pass1">Password <?= $edit ? '(kosongkan bila tidak diubah)' : ''; ?></label>
                        <input class="input" type="password" id="pass1" name="pass1" autocomplete="new-password" <?= $edit ? '' : 'required'; ?>>
                    </div>
                    <div>
                        <label class="label" for="pass2">Ulangi Password</label>
                        <input class="input" type="password" id="pass2" name="pass2" autocomplete="new-password" <?= $edit ? '' : 'required'; ?>>
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-border pt-5">
                    <a href="<?= base_url('user'); ?>" class="btn-outline">
                        <?= svg_icon('arrow-left'); ?>
                        <span>Kembali</span>
                    </a>
                    <button type="submit" class="btn-primary">
                        <?= svg_icon('check'); ?>
                        <span><?= $edit ? 'Simpan Perubahan' : 'Simpan'; ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>