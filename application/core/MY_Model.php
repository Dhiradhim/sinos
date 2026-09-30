<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * MY_Model
 * Model dasar dengan konfigurasi tabel & primary key sederhana.
 */
#[\AllowDynamicProperties]
class MY_Model extends CI_Model
{
    protected $table;
    protected $primary_key = 'id';

    public function __construct()
    {
        parent::__construct();
    }

    public function get($id)
    {
        return $this->db->get_where($this->table, array($this->primary_key => $id))->row();
    }

    public function get_all($order_by = NULL, $direction = 'ASC')
    {
        if ($order_by) {
            $this->db->order_by($order_by, $direction);
        }
        return $this->db->get($this->table)->result();
    }

    public function insert($data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $this->db->where($this->primary_key, $id);
        return $this->db->update($this->table, $data);
    }

    public function delete($id)
    {
        return $this->db->delete($this->table, array($this->primary_key => $id));
    }

    public function count_all()
    {
        return $this->db->count_all($this->table);
    }
}
