<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Twocheckout_model extends App_Model
{
    public function add($data)
    {
        $data['created_at'] = date('c');
        $this->db->insert(db_prefix(). 'twocheckout_log', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            return true;
        }
        return false;
    }

    public function get(string $reference)
    {
        $this->db->where('reference', $reference);
        return $this->db->get(db_prefix(). 'twocheckout_log')->row();
    }

    public function delete($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix(). 'twocheckout_log');
    }
}
