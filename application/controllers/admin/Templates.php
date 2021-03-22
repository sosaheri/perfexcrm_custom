<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Templates extends AdminController
{
    /**
     * Initialize Templates controller
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('templates_model');
    }

    /**
     * Get the template modal content
     *
     * @return string
     */
    public function modal()
    {
        $data['rel_type'] = $this->input->post('rel_type');

        // When modal is submitted, it returns to the proposal/contract that was being edited.
        $data['rel_id'] = $this->input->post('rel_id');

        if ($this->input->post('slug') == 'new') {
            $data['title'] = _l('add_template');
        } elseif ($this->input->post('slug') == 'edit') {
            $data['title'] = _l('edit_template');
            $data['id']    = $this->input->post('id');
            $this->authorize($data['id']);
            $data['template'] = $this->templates_model->get($data['id']);
        }

        $this->load->view('admin/includes/modals/template', $data);
    }

    /**
     * Get template(s) data
     *
     * @param  int|null $id
     */
    public function index($id = null)
    {
        $data['rel_type'] = $this->input->post('rel_type');
        $data['rel_id']   = $this->input->post('rel_id');

        $where             = ['type' => $data['rel_type']];
        $data['templates'] = $this->templates_model->get($id, $where);

        if (is_numeric($id)) {
            $template = $this->templates_model->get($id);

            echo json_encode([
                'data' => $template,
            ]);
            die;
        }

        $this->load->view('admin/includes/templates', $data);
    }

    /**
     * Manage template
     *
     * @param  int|null $id
     *
     */
    public function template($id = null)
    {
        $content = $this->input->post('content', false);
        $content = html_purify($content);

        $data['name']      = $this->input->post('name');
        $data['content']   = $content;
        $data['addedfrom'] = get_staff_user_id();
        $data['type']      = $this->input->post('rel_type');

        // so when modal is submitted, it returns to the proposal/contract that was being edited.
        $rel_id = $this->input->post('rel_id');

        if (is_numeric($id)) {
            $this->authorize($id);
            $success = $this->templates_model->update($id, $data);
            $message = _l('template_updated');
        } else {
            $success = $this->templates_model->create($data);
            $message = _l('template_added');
        }

        if ($success) {
            set_alert('success', $message);
        }

        redirect(
            $data['type'] == 'contracts' ?
            admin_url('contracts/contract/' . $rel_id) :
            admin_url('proposals/list_proposals/' . $rel_id)
        );
    }

    /**
     * Delete template by given id
     *
     * @param  int $id
     *
     * @return array
     */
    public function delete($id)
    {
        $this->authorize($id);

        $this->templates_model->delete($id);

        echo json_encode([
            'success' => true,
        ]);
    }

    /**
     * Authorize the template for update/delete
     *
     * @param  int $id
     *
     * @return void
     */
    protected function authorize($id)
    {
        $template = $this->templates_model->get($id);

        if ($template->addedfrom != get_staff_user_id() && !is_admin()) {
            ajax_access_denied();
        }
    }
}
