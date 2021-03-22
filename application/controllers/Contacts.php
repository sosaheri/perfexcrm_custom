<?php

defined('BASEPATH') or exit('No direct script access allowed');

use app\services\ValidatesContact;

class Contacts extends ClientsController
{
    /**
     * @since  2.3.3
     */
    use ValidatesContact;

    public $contactId;

    protected $contact;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('clients_model');

        $this->contactId = get_contact_user_id();
        $this->contact   = $this->clients_model->get_contact($this->contactId);

        if (!can_loggged_in_user_manage_contacts()) {
            show_404();
        }

        hooks()->do_action('after_clients_area_init', $this);
    }

    /**
     * View all customer contacts
     *
     * @return mixed
     */
    public function index()
    {
        if (is_gdpr() && get_option('gdpr_enable_consent_for_contacts') == '1') {
            $this->load->model('gdpr_model');
            $data['consent_purposes'] = $this->gdpr_model->get_consent_purposes();
        }
        $data['contacts']  = $this->clients_model->get_contacts(get_client_user_id(), ['active' => 1, 'id !=' => $this->contactId]);
        $data['title']     = _l('customer_contacts');
        $data['bodyclass'] = 'contacts';
        $this->data($data);
        $this->view('contacts');
        $this->layout();
    }

    /**
     * Manage contact
     *
     * @param  int $id
     *
     * @return mixed
     */
    public function contact($id = null)
    {
        if ($this->input->post()) {
            $this->form_validation->set_rules('firstname', _l('client_firstname'), 'required');
            $this->form_validation->set_rules('lastname', _l('client_lastname'), 'required');

            if (is_numeric($id)) {
                $this->form_validation->set_message('contact_email_profile_unique', _l('contact_form_validation_is_unique'));
                $this->form_validation->set_rules('email', _l('clients_email'), 'trim|required|valid_email|callback_contact_email_profile_unique[' . $id . ']');
            } else {
                $this->form_validation->set_message('is_unique', _l('contact_form_validation_is_unique'));
                $this->form_validation->set_rules('email', _l('client_email'), 'trim|required|is_unique[' . db_prefix() . 'contacts.email]|valid_email');
            }

            $custom_fields = get_custom_fields('contacts', [
                'show_on_client_portal'  => 1,
                'required'               => 1,
                'disalow_client_to_edit' => 0,
            ]);

            foreach ($custom_fields as $field) {
                $field_name = 'custom_fields[' . $field['fieldto'] . '][' . $field['id'] . ']';

                if ($field['type'] == 'checkbox' || $field['type'] == 'multiselect') {
                    $field_name .= '[]';
                }

                $this->form_validation->set_rules($field_name, $field['name'], 'required');
            }

            if ($this->form_validation->run() != false) {
                $data = $this->input->post();

                $contact_data = [
                    'is_primary'         => 0,
                    'firstname'          => $this->input->post('firstname'),
                    'lastname'           => $this->input->post('lastname'),
                    'title'              => $this->input->post('title'),
                    'email'              => $this->input->post('email'),
                    'phonenumber'        => $this->input->post('phonenumber'),
                    'direction'          => $this->input->post('direction'),
                    'invoice_emails'     => isset($data['invoice_emails']) ? 1 : 0,
                    'credit_note_emails' => isset($data['credit_note_emails']) ? 1 : 0,
                    'estimate_emails'    => isset($data['estimate_emails']) ? 1 : 0,
                    'ticket_emails'      => isset($data['ticket_emails']) ? 1 : 0,
                    'contract_emails'    => isset($data['contract_emails']) ? 1 : 0,
                    'project_emails'     => isset($data['project_emails']) ? 1 : 0,
                    'task_emails'        => isset($data['task_emails']) ? 1 : 0,
                    'custom_fields'      => isset($data['custom_fields']) && is_array($data['custom_fields']) ? $data['custom_fields'] : [],
                ];

                if (isset($data['password'])) {
                    $contact_data['password'] = $this->input->post('password', false);
                }

                if (isset($data['send_set_password_email']) && $data['send_set_password_email'] == 'on') {
                    $contact_data['send_set_password_email'] = true;
                }

                if (isset($data['donotsendwelcomeemail']) && $data['donotsendwelcomeemail'] == 'on') {
                    $contact_data['donotsendwelcomeemail'] = true;
                }

                if (is_numeric($id)) {
                    handle_contact_profile_image_upload($id);
                    $success = $this->clients_model->update_contact($contact_data, $id, true);

                    if ($success == true) {
                        set_alert('success', _l('clients_contact_updated'));
                    }
                } else {
                    $contactId = $this->clients_model->add_contact_via_customers_area($contact_data, get_client_user_id());

                    if ($contactId !== false) {
                        handle_contact_profile_image_upload($contactId);
                        set_alert('success', _l('clients_contact_added'));
                    }
                }

                redirect(site_url('contacts'));
            }
        };

        if (is_numeric($id)) {
            $data['my_contact'] = $this->clients_model->get_contact($id);
        }

        $data['customer_permissions'] = get_contact_permissions();
        $data['title']                = _l('customer_contact');
        $data['bodyclass']            = 'contact';

        $this->data($data);
        $this->view('contact');
        $this->layout();
    }

    /**
     * Delete contact
     *
     * @param  int $customer_id
     * @param  int $id
     *
     * @return mixed
     */
    public function delete($customer_id, $id)
    {
        $contact = $this->clients_model->get_contact($id);

        if ($customer_id != get_client_user_id() || $contact->is_primary == 1) {
            show_404();
            die();
        }

        $this->clients_model->delete_contact($id);

        redirect(site_url('contacts'));
    }

    /**
     * Delete the given contact profile image
     *
     * @param  int $id
     *
     * @return void
     */
    public function delete_profile_image($id)
    {
        $contact = $this->clients_model->get_contact($id);

        if ($contact->userid != get_client_user_id()) {
            ajax_access_denied();
        }

        $this->clients_model->delete_contact_profile_image($id);
    }

    /**
     * Validate email
     *
     * @param  string $email
     * @param  id $id
     *
     * @return boolean
     */
    public function contact_email_profile_unique($email, $id)
    {
        return total_rows(
            db_prefix() . 'contacts',
            'id !=' . $id . ' AND email="' . get_instance()->db->escape_str($email) . '"'
        ) > 0 ? false : true;
    }
}
