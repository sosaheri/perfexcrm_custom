<?php

use app\services\imap\Imap;
use app\services\imap\ConnectionErrorException;
use Ddeboer\Imap\Exception\MailboxDoesNotExistException;

defined('BASEPATH') or exit('No direct script access allowed');

class Departments extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('departments_model');

        if (!is_admin()) {
            access_denied('Departments');
        }
    }

    /* List all departments */
    public function index()
    {
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data('departments');
        }
        $data['email_exist_as_staff'] = $this->email_exist_as_staff();
        $data['title']                = _l('departments');
        $this->load->view('admin/departments/manage', $data);
    }

    /* Edit or add new department */
    public function department($id = '')
    {
        if ($this->input->post()) {
            $message          = '';
            $data             = $this->input->post();
            $data             = $this->input->post();
            $data['password'] = $this->input->post('password', false);

            if (isset($data['fakeusernameremembered']) || isset($data['fakepasswordremembered'])) {
                unset($data['fakeusernameremembered']);
                unset($data['fakepasswordremembered']);
            }

            if (!$this->input->post('id')) {
                $id = $this->departments_model->add($data);
                if ($id) {
                    $success = true;
                    $message = _l('added_successfully', _l('department'));
                }
                echo json_encode([
                    'success'              => $success,
                    'message'              => $message,
                    'email_exist_as_staff' => $this->email_exist_as_staff(),
                ]);
            } else {
                $id = $data['id'];
                unset($data['id']);
                $success = $this->departments_model->update($data, $id);
                if ($success) {
                    $message = _l('updated_successfully', _l('department'));
                }
                echo json_encode([
                    'success'              => $success,
                    'message'              => $message,
                    'email_exist_as_staff' => $this->email_exist_as_staff(),
                ]);
            }
            die;
        }
    }

    /* Delete department from database */
    public function delete($id)
    {
        if (!$id) {
            redirect(admin_url('departments'));
        }
        $response = $this->departments_model->delete($id);
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('department_lowercase')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('department')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('department_lowercase')));
        }
        redirect(admin_url('departments'));
    }

    public function email_exists()
    {
        // First we need to check if the email is the same
        $departmentid = $this->input->post('departmentid');
        if ($departmentid) {
            $this->db->where('departmentid', $departmentid);
            $_current_email = $this->db->get(db_prefix() . 'departments')->row();
            if ($_current_email->email == $this->input->post('email')) {
                echo json_encode(true);
                die();
            }
        }
        $exists = total_rows(db_prefix() . 'departments', [
            'email' => $this->input->post('email'),
        ]);
        if ($exists > 0) {
            echo 'false';
        } else {
            echo 'true';
        }
    }

    public function folders()
    {
        app_check_imap_open_function();

        $imap = new Imap(
           $this->input->post('username') ? $this->input->post('username') : $this->input->post('email'),
           $this->input->post('password', false),
           $this->input->post('host'),
           $this->input->post('encryption')
        );

        try {
            echo json_encode($imap->getSelectableFolders());
        } catch (ConnectionErrorException $e) {
            echo json_encode([
                'alert_type' => 'warning',
                'message'    => $e->getMessage(),
            ]);
        }
    }

    public function test_imap_connection()
    {
        app_check_imap_open_function();

        $imap = new Imap(
           $this->input->post('username') ? $this->input->post('username') : $this->input->post('email'),
           $this->input->post('password', false),
           $this->input->post('host'),
           $this->input->post('encryption')
        );

        try {
            $connection = $imap->testConnection();

            try {
                $folder = $this->input->post('folder');

                $connection->getMailbox(empty($folder) ? 'INBOX' : $folder);
            } catch (MailboxDoesNotExistException $e) {
                echo json_encode([
                    'alert_type' => 'warning',
                    'message'    => $e->getMessage(),
                ]);
                die;
            }
            echo json_encode([
                'alert_type' => 'success',
                'message'    => _l('lead_email_connection_ok'),
            ]);
        } catch (ConnectionErrorException $e) {
            echo json_encode([
                'alert_type' => 'warning',
                'message'    => $e->getMessage(),
            ]);
        }
    }

    private function email_exist_as_staff()
    {
        return total_rows(db_prefix() . 'departments', 'email IN (SELECT email FROM ' . db_prefix() . 'staff)') > 0;
    }
}
