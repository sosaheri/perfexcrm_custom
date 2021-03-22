<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Todo_model extends App_Model
{
    public $todo_limit;

    public function __construct()
    {
        parent::__construct();
        $this->todo_limit = hooks()->apply_filters('todos_limit', 10);
    }

    public function setTodosLimit($limit)
    {
        $this->todo_limit = $limit;
    }

    public function getTodosLimit()
    {
        return $this->todo_limit;
    }

    public function get($id = '')
    {
        $this->db->where('staffid', get_staff_user_id());

        if (is_numeric($id)) {
            $this->db->where('todoid', $id);

            return $this->db->get(db_prefix().'todos')->row();
        }

        return $this->db->get(db_prefix().'todos')->result_array();
    }

    /**
     * Get all user todos
     * @param  boolean $finished is finished todos or not
     * @param  mixed $page     pagination limit page
     * @return array
     */
    public function get_todo_items($finished, $page = '')
    {
        $this->db->select();
        $this->db->from(db_prefix().'todos');
        $this->db->where('finished', $finished);
        $this->db->where('staffid', get_staff_user_id());
        $this->db->order_by('item_order', 'asc');
        if ($page != '' && $this->input->post('todo_page')) {
            $position = ($page * $this->todo_limit);
            $this->db->limit($this->todo_limit, $position);
        } else {
            $this->db->limit($this->todo_limit);
        }
        $todos = $this->db->get()->result_array();
        // format date
        $i = 0;
        foreach ($todos as $todo) {
            $todos[$i]['dateadded']    = _dt($todo['dateadded']);
            $todos[$i]['datefinished'] = _dt($todo['datefinished']);
            $todos[$i]['description']  = check_for_links($todo['description']);
            $i++;
        }

        return $todos;
    }

    /**
     * Add new user todo
     * @param mixed $data todo $_POST data
     */
    public function add($data)
    {
        $data['dateadded']   = date('Y-m-d H:i:s');
        $data['description'] = nl2br($data['description']);
        $data['staffid']     = get_staff_user_id();
        $this->db->insert(db_prefix().'todos', $data);

        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $data['description'] = nl2br($data['description']);

        $this->db->where('todoid', $id);
        $this->db->update(db_prefix().'todos', $data);
        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Update todo's order / Ajax - Sortable
     * @param  mixed $data todo $_POST data
     */
    public function update_todo_items_order($data)
    {
        for ($i = 0; $i < count($data['data']); $i++) {
            $update = [
                'item_order' => $data['data'][$i][1],
                'finished'   => $data['data'][$i][2],
            ];
            if ($data['data'][$i][2] == 1) {
                $update['datefinished'] = date('Y-m-d H:i:s');
            }
            $this->db->where('todoid', $data['data'][$i][0]);
            $this->db->update(db_prefix().'todos', $update);
        }
    }

    /**
     * Delete todo
     * @param  mixed $id todo id
     * @return boolean
     */
    public function delete_todo_item($id)
    {
        $this->db->where('todoid', $id);
        $this->db->where('staffid', get_staff_user_id());
        $this->db->delete(db_prefix().'todos');
        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Change todo status / finished or not finished
     * @param  mixed $id     todo id
     * @param  integer $status can be passed 1 or 0
     * @return array
     */
    public function change_todo_status($id, $status)
    {
        $this->db->where('todoid', $id);
        $this->db->where('staffid', get_staff_user_id());
        $date = date('Y-m-d H:i:s');
        $this->db->update(db_prefix().'todos', [
            'finished'     => $status,
            'datefinished' => $date,
        ]);
        if ($this->db->affected_rows() > 0) {
            return [
                'success' => true,
            ];
        }

        return [
            'success' => false,
        ];
    }
}
