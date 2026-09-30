<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * M_jabatan - model tabel `jabatan`
 */
class M_jabatan extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'jabatan';
        $this->primary_key = 'id';
    }

    public function get_jabatan($id)
    {
        $row = $this->get($id);
        return $row ? $row->jabatan : NULL;
    }
}
