<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Client_groups_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Add new customer group
     * @param array $data $_POST data
     */
    public function add($data)
    {
        $this->db->insert(db_prefix().'customers_groups', $data);

        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            log_activity('New Customer Group Created [ID:' . $insert_id . ', Name:' . $data['name'] . ']');

            return $insert_id;
        }

        return false;
    }

    /**
    * Get customer groups where customer belongs
    * @param  mixed $id customer id
    * @return array
    */
    public function get_customer_groups($id)
    {
        $this->db->where('customer_id', $id);

        return $this->db->get(db_prefix().'customer_groups')->result_array();
    }

    /**
     * Get all customer groups
     * @param  string $id
     * @return mixed
     */
    public function get_groups($id = '')
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);

            return $this->db->get(db_prefix().'customers_groups')->row();
        }
        $this->db->order_by('name', 'asc');

        return $this->db->get(db_prefix().'customers_groups')->result_array();
    }

    /**
     * Edit customer group
     * @param  array $data $_POST data
     * @return boolean
     */
    public function edit($data)
    {
        $this->db->where('id', $data['id']);
        $this->db->update(db_prefix().'customers_groups', [
            'name' => $data['name'],
        ]);
        if ($this->db->affected_rows() > 0) {
            log_activity('Customer Group Updated [ID:' . $data['id'] . ']');

            return true;
        }

        return false;
    }

    /**
     * Delete customer group
     * @param  mixed $id group id
     * @return boolean
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix().'customers_groups');
        if ($this->db->affected_rows() > 0) {
            $this->db->where('groupid', $id);
            $this->db->delete(db_prefix().'customer_groups');

            hooks()->do_action('customer_group_deleted', $id);

            log_activity('Customer Group Deleted [ID:' . $id . ']');

            return true;
        }

        return false;
    }

    /**
    * Update/sync customer groups where belongs
    * @param  mixed $id        customer id
    * @param  mixed $groups_in
    * @return boolean
    */
    public function sync_customer_groups($id, $groups_in)
    {
        if ($groups_in == false) {
            unset($groups_in);
        }
        $affectedRows    = 0;
        $customer_groups = $this->get_customer_groups($id);
        if (sizeof($customer_groups) > 0) {
            foreach ($customer_groups as $customer_group) {
                if (isset($groups_in)) {
                    if (!in_array($customer_group['groupid'], $groups_in)) {
                        $this->db->where('customer_id', $id);
                        $this->db->where('id', $customer_group['id']);
                        $this->db->delete(db_prefix().'customer_groups');
                        if ($this->db->affected_rows() > 0) {
                            $affectedRows++;
                        }
                    }
                } else {
                    $this->db->where('customer_id', $id);
                    $this->db->delete(db_prefix().'customer_groups');
                    if ($this->db->affected_rows() > 0) {
                        $affectedRows++;
                    }
                }
            }
            if (isset($groups_in)) {
                foreach ($groups_in as $group) {
                    $this->db->where('customer_id', $id);
                    $this->db->where('groupid', $group);
                    $_exists = $this->db->get(db_prefix().'customer_groups')->row();
                    if (!$_exists) {
                        if (empty($group)) {
                            continue;
                        }
                        $this->db->insert(db_prefix().'customer_groups', [
                            'customer_id' => $id,
                            'groupid'     => $group,
                        ]);
                        if ($this->db->affected_rows() > 0) {
                            $affectedRows++;
                        }
                    }
                }
            }
        } else {
            if (isset($groups_in)) {
                foreach ($groups_in as $group) {
                    if (empty($group)) {
                        continue;
                    }
                    $this->db->insert(db_prefix().'customer_groups', [
                        'customer_id' => $id,
                        'groupid'     => $group,
                    ]);
                    if ($this->db->affected_rows() > 0) {
                        $affectedRows++;
                    }
                }
            }
        }

        if ($affectedRows > 0) {
            return true;
        }

        return false;
    }
}
