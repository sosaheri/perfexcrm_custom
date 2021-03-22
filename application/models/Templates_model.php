<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Templates_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Add template
     * @param  array $data template data
     * @return int|boolean
     */

    public function create($data)
    {
        $data = hooks()->apply_filters('before_template_added', $data);

        $this->db->insert(db_prefix() . 'templates', $data);
        $template_id = $this->db->insert_id();

        if ($template_id) {
            log_activity('New Template Added [ID: ' . $template_id . ', ' . $data['name'] . ']');

            hooks()->do_action('new_template_added', $template_id);

            return $template_id;
        }

        return false;
    }
    
    
    /**
     * Get template
     * @param  int $id template id
     * @param  array $where 
     * @return object|array
     */
    public function get($id = '', $where = [])
    {
        $this->db->where($where);

        if (is_numeric($id)) {
            $this->db->where('id', $id);
            $template = $this->db->get(db_prefix() . 'templates')->row();
            return $template;
        }

        $templates = $this->db->get(db_prefix() . 'templates')->result_array();
        return $templates;
    }

    /**
     * Edit template
     * @param  int $id template id
     * @param  array $data template data
     * @return boolean
     */
    public function update($id, $data)
    {
        $data = hooks()->apply_filters('before_template_deleted', $data, $id);
        $name = $this->get($id)->name;

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'templates', $data);

        if ($this->db->affected_rows() > 0) {
            log_activity('Template updated [Name: ' . $name . ']');
            hooks()->do_action('after_template_updated', $id);
            return true;
        }
        return false;
    }

    /**
     * Delete template
     * @param  array $id template id
     * @return boolean
     */    
    public function delete($id)
    {
        hooks()->do_action('before_template_deleted', $id);
        $name = $this->get($id)->name;

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'templates');
        log_activity('Template Deleted [Name: ' . $name . ']');

        hooks()->do_action('after_template_deleted', $id);
        return true;
    }
}
