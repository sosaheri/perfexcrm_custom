<?php

use app\services\utilities\Arr;

defined('BASEPATH') or exit('No direct script access allowed');

class Misc_model extends App_Model
{
    public $notifications_limit;

    public function __construct()
    {
        parent::__construct();
        $this->notifications_limit = 15;
    }

    public function get_notifications_limit()
    {
        return hooks()->apply_filters('notifications_limit', $this->notifications_limit);
    }

    public function get_taxes_dropdown_template($name, $taxname, $type = '', $item_id = '', $is_edit = false, $manual = false)
    {
        // if passed manually - like in proposal convert items or project
        if ($manual == true) {
            // + is no longer used and is here for backward compatibilities
            if (is_array($taxname) || strpos($taxname, '+') !== false) {
                if (!is_array($taxname)) {
                    $__tax = explode('+', $taxname);
                } else {
                    $__tax = $taxname;
                }
                // Multiple taxes found // possible option from default settings when invoicing project
                $taxname = [];
                foreach ($__tax as $t) {
                    $tax_array = explode('|', $t);
                    if (isset($tax_array[0]) && isset($tax_array[1])) {
                        array_push($taxname, $tax_array[0] . '|' . $tax_array[1]);
                    }
                }
            } else {
                $tax_array = explode('|', $taxname);
                // isset tax rate
                if (isset($tax_array[0]) && isset($tax_array[1])) {
                    $tax = get_tax_by_name($tax_array[0]);
                    if ($tax) {
                        $taxname = $tax->name . '|' . $tax->taxrate;
                    }
                }
            }
        }
        // First get all system taxes
        $this->load->model('taxes_model');
        $taxes = $this->taxes_model->get();
        $i     = 0;
        foreach ($taxes as $tax) {
            unset($taxes[$i]['id']);
            $taxes[$i]['name'] = $tax['name'] . '|' . $tax['taxrate'];
            $i++;
        }
        if ($is_edit == true) {

            // Lets check the items taxes in case of changes.
            // Separate functions exists to get item taxes for Invoice, Estimate, Proposal, Credit Note
            $func_taxes = 'get_' . $type . '_item_taxes';
            if (function_exists($func_taxes)) {
                $item_taxes = call_user_func($func_taxes, $item_id);
            }

            foreach ($item_taxes as $item_tax) {
                $new_tax            = [];
                $new_tax['name']    = $item_tax['taxname'];
                $new_tax['taxrate'] = $item_tax['taxrate'];
                $taxes[]            = $new_tax;
            }
        }

        // In case tax is changed and the old tax is still linked to estimate/proposal when converting
        // This will allow the tax that don't exists to be shown on the dropdowns too.
        if (is_array($taxname)) {
            foreach ($taxname as $tax) {
                // Check if tax empty
                if ((!is_array($tax) && $tax == '') || is_array($tax) && $tax['taxname'] == '') {
                    continue;
                };
                // Check if really the taxname NAME|RATE don't exists in all taxes
                if (!value_exists_in_array_by_key($taxes, 'name', $tax)) {
                    if (!is_array($tax)) {
                        $tmp_taxname = $tax;
                        $tax_array   = explode('|', $tax);
                    } else {
                        $tax_array   = explode('|', $tax['taxname']);
                        $tmp_taxname = $tax['taxname'];
                        if ($tmp_taxname == '') {
                            continue;
                        }
                    }
                    $taxes[] = ['name' => $tmp_taxname, 'taxrate' => $tax_array[1]];
                }
            }
        }

        // Clear the duplicates
        $taxes = Arr::uniqueByKey($taxes, 'name');

        $select = '<select class="selectpicker display-block tax" data-width="100%" name="' . $name . '" multiple data-none-selected-text="' . _l('no_tax') . '">';

        foreach ($taxes as $tax) {
            $selected = '';
            if (is_array($taxname)) {
                foreach ($taxname as $_tax) {
                    if (is_array($_tax)) {
                        if ($_tax['taxname'] == $tax['name']) {
                            $selected = 'selected';
                        }
                    } else {
                        if ($_tax == $tax['name']) {
                            $selected = 'selected';
                        }
                    }
                }
            } else {
                if ($taxname == $tax['name']) {
                    $selected = 'selected';
                }
            }

            $select .= '<option value="' . $tax['name'] . '" ' . $selected . ' data-taxrate="' . $tax['taxrate'] . '" data-taxname="' . $tax['name'] . '" data-subtext="' . $tax['name'] . '">' . $tax['taxrate'] . '%</option>';
        }
        $select .= '</select>';

        return $select;
    }

    public function add_attachment_to_database($rel_id, $rel_type, $attachment, $external = false)
    {
        $data['dateadded'] = date('Y-m-d H:i:s');
        $data['rel_id']    = $rel_id;
        if (!isset($attachment[0]['staffid'])) {
            $data['staffid'] = get_staff_user_id();
        } else {
            $data['staffid'] = $attachment[0]['staffid'];
        }

        if (isset($attachment[0]['task_comment_id'])) {
            $data['task_comment_id'] = $attachment[0]['task_comment_id'];
        }

        $data['rel_type'] = $rel_type;

        if (isset($attachment[0]['contact_id'])) {
            $data['contact_id']          = $attachment[0]['contact_id'];
            $data['visible_to_customer'] = 1;
            if (isset($data['staffid'])) {
                unset($data['staffid']);
            }
        }

        $data['attachment_key'] = app_generate_hash();

        if ($external == false) {
            $data['file_name'] = $attachment[0]['file_name'];
            $data['filetype']  = $attachment[0]['filetype'];
        } else {
            $path_parts            = pathinfo($attachment[0]['name']);
            $data['file_name']     = $attachment[0]['name'];
            $data['external_link'] = $attachment[0]['link'];
            $data['filetype']      = !isset($attachment[0]['mime']) ? get_mime_by_extension('.' . $path_parts['extension']) : $attachment[0]['mime'];
            $data['external']      = $external;
            if (isset($attachment[0]['thumbnailLink'])) {
                $data['thumbnail_link'] = $attachment[0]['thumbnailLink'];
            }
        }

        $this->db->insert(db_prefix() . 'files', $data);
        $insert_id = $this->db->insert_id();

        if ($data['rel_type'] == 'customer' && isset($data['contact_id'])) {
            if (get_option('only_own_files_contacts') == 1) {
                $this->db->insert(db_prefix() . 'shared_customer_files', [
                    'file_id'    => $insert_id,
                    'contact_id' => $data['contact_id'],
                ]);
            } else {
                $this->db->select('id');
                $this->db->where('userid', $data['rel_id']);
                $contacts = $this->db->get(db_prefix() . 'contacts')->result_array();
                foreach ($contacts as $contact) {
                    $this->db->insert(db_prefix() . 'shared_customer_files', [
                        'file_id'    => $insert_id,
                        'contact_id' => $contact['id'],
                    ]);
                }
            }
        }

        return $insert_id;
    }

    public function get_file($id)
    {
        $this->db->where('id', $id);

        return $this->db->get(db_prefix() . 'files')->row();
    }

    public function get_staff_started_timers()
    {
        $this->db->select(db_prefix() . 'taskstimers.*,' . db_prefix() . 'tasks.name as task_subject');
        $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid=' . db_prefix() . 'taskstimers.staff_id');
        $this->db->join(db_prefix() . 'tasks', db_prefix() . 'tasks.id=' . db_prefix() . 'taskstimers.task_id', 'left');
        $this->db->where('staff_id', get_staff_user_id());
        $this->db->where('end_time IS NULL');

        return $this->db->get(db_prefix() . 'taskstimers')->result_array();
    }

    /**
     * Add reminder
     * @since  Version 1.0.2
     * @param mixed $data All $_POST data for the reminder
     * @param mixed $id   relid id
     * @return boolean
     */
    public function add_reminder($data, $id)
    {
        if (isset($data['notify_by_email'])) {
            $data['notify_by_email'] = 1;
        } //isset($data['notify_by_email'])
        else {
            $data['notify_by_email'] = 0;
        }
        $data['date']        = to_sql_date($data['date'], true);
        $data['description'] = nl2br($data['description']);
        $data['creator']     = get_staff_user_id();
        $this->db->insert(db_prefix() . 'reminders', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            if ($data['rel_type'] == 'lead') {
                $this->load->model('leads_model');
                $this->leads_model->log_lead_activity($data['rel_id'], 'not_activity_new_reminder_created', false, serialize([
                    get_staff_full_name($data['staff']),
                    _dt($data['date']),
                    ]));
            }
            log_activity('New Reminder Added [' . ucfirst($data['rel_type']) . 'ID: ' . $data['rel_id'] . ' Description: ' . $data['description'] . ']');

            return true;
        } //$insert_id
        return false;
    }

    public function edit_reminder($data, $id)
    {
        if (isset($data['notify_by_email'])) {
            $data['notify_by_email'] = 1;
        } else {
            $data['notify_by_email'] = 0;
        }

        $data['date']        = to_sql_date($data['date'], true);
        $data['description'] = nl2br($data['description']);

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'reminders', $data);

        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    public function get_notes($rel_id, $rel_type)
    {
        $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid=' . db_prefix() . 'notes.addedfrom');
        $this->db->where('rel_id', $rel_id);
        $this->db->where('rel_type', $rel_type);
        $this->db->order_by('dateadded', 'desc');

        $notes = $this->db->get(db_prefix() . 'notes')->result_array();

        return hooks()->apply_filters('get_notes', $notes, ['rel_id' => $rel_id, 'rel_type' => $rel_type]);
    }

    public function add_note($data, $rel_type, $rel_id)
    {
        $data['dateadded']   = date('Y-m-d H:i:s');
        $data['addedfrom']   = get_staff_user_id();
        $data['rel_type']    = $rel_type;
        $data['rel_id']      = $rel_id;
        $data['description'] = nl2br($data['description']);

        $data = hooks()->apply_filters('create_note_data', $data, $rel_type, $rel_id);

        $this->db->insert(db_prefix() . 'notes', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            hooks()->do_action('note_created', $insert_id, $data);

            return $insert_id;
        }

        return false;
    }

    public function edit_note($data, $id)
    {
        hooks()->do_action('before_update_note', [
            'data' => $data,
            'id'   => $id,
        ]);

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'notes', $data = [
            'description' => nl2br($data['description']),
        ]);

        if ($this->db->affected_rows() > 0) {
            hooks()->do_action('note_updated', $id, $data);

            return true;
        }

        return false;
    }

    public function get_activity_log($limit = 30)
    {
        $this->db->limit($limit);
        $this->db->order_by('date', 'desc');

        return $this->db->get(db_prefix() . 'activity_log')->result_array();
    }

    public function delete_note($note_id)
    {
        hooks()->do_action('before_delete_note', $note_id);

        $this->db->where('id', $note_id);
        $note = $this->db->get(db_prefix() . 'notes')->row();

        if ($note->addedfrom != get_staff_user_id() && !is_admin()) {
            return false;
        }

        $this->db->where('id', $note_id);
        $this->db->delete(db_prefix() . 'notes');
        if ($this->db->affected_rows() > 0) {
            hooks()->do_action('note_deleted', $note_id, $note);

            return true;
        }

        return false;
    }

    /**
     * Get all reminders or 1 reminder if id is passed
     * @since Version 1.0.2
     * @param  mixed $id reminder id OPTIONAL
     * @return array or object
     */
    public function get_reminders($id = '')
    {
        $this->db->join(db_prefix() . 'staff', '' . db_prefix() . 'staff.staffid = ' . db_prefix() . 'reminders.staff', 'left');
        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'reminders.id', $id);

            return $this->db->get(db_prefix() . 'reminders')->row();
        } //is_numeric($id)
        $this->db->order_by('date', 'desc');

        return $this->db->get(db_prefix() . 'reminders')->result_array();
    }

    /**
     * Remove client reminder from database
     * @since Version 1.0.2
     * @param  mixed $id reminder id
     * @return boolean
     */
    public function delete_reminder($id)
    {
        $reminder = $this->get_reminders($id);
        if ($reminder->creator == get_staff_user_id() || is_admin()) {
            $this->db->where('id', $id);
            $this->db->delete(db_prefix() . 'reminders');
            if ($this->db->affected_rows() > 0) {
                log_activity('Reminder Deleted [' . ucfirst($reminder->rel_type) . 'ID: ' . $reminder->id . ' Description: ' . $reminder->description . ']');

                return true;
            } //$this->db->affected_rows() > 0
            return false;
        } //$reminder->creator == get_staff_user_id() || is_admin()
        return false;
    }

    public function get_tasks_distinct_assignees()
    {
        return $this->db->query('SELECT DISTINCT(' . db_prefix() . "task_assigned.staffid) as assigneeid, CONCAT(firstname,' ',lastname) as full_name FROM " . db_prefix() . 'task_assigned JOIN ' . db_prefix() . 'staff ON ' . db_prefix() . 'staff.staffid=' . db_prefix() . 'task_assigned.staffid')->result_array();
    }

    public function get_google_calendar_ids()
    {
        $is_admin = is_admin();
        $this->load->model('departments_model');
        $departments       = $this->departments_model->get();
        $staff_departments = $this->departments_model->get_staff_departments(false, true);
        $ids               = [];

        // Check departments google calendar ids
        foreach ($departments as $department) {
            if ($department['calendar_id'] == '') {
                continue;
            }
            if ($is_admin) {
                $ids[] = $department['calendar_id'];
            } else {
                if (in_array($department['departmentid'], $staff_departments)) {
                    $ids[] = $department['calendar_id'];
                }
            }
        }

        // Ok now check if main calendar is setup
        $main_id_calendar = get_option('google_calendar_main_calendar');
        if ($main_id_calendar != '') {
            $ids[] = $main_id_calendar;
        }

        return array_unique($ids);
    }

    /**
     * Get current user notifications
     * @param  boolean $read include and readed notifications
     * @return array
     */
    public function get_user_notifications($read = false)
    {
        $read     = $read == false ? 0 : 1;
        $total    = $this->notifications_limit;
        $staff_id = get_staff_user_id();

        $sql = 'SELECT COUNT(*) as total FROM ' . db_prefix() . 'notifications WHERE isread=' . $read . ' AND touserid=' . $staff_id;
        $sql .= ' UNION ALL ';
        $sql .= 'SELECT COUNT(*) as total FROM ' . db_prefix() . 'notifications WHERE isread_inline=' . $read . ' AND touserid=' . $staff_id;

        $res = $this->db->query($sql)->result();

        $total_unread        = $res[0]->total;
        $total_unread_inline = $res[1]->total;

        if ($total_unread > $total) {
            $total = ($total_unread - $total) + $total;
        } elseif ($total_unread_inline > $total) {
            $total = ($total_unread_inline - $total) + $total;
        }

        // In case user is not marking the notifications are read this process may be long because the script will always fetch the total from the not read notifications.
        // In this case we are limiting to 30
        $total = $total > 30 ? 30 : $total;

        $this->db->where('touserid', $staff_id);
        $this->db->limit($total);
        $this->db->order_by('date', 'desc');

        return $this->db->get(db_prefix() . 'notifications')->result_array();
    }

    /**
     * Set notification read when user open notification dropdown
     * @return boolean
     */
    public function set_notifications_read()
    {
        $this->db->where('touserid', get_staff_user_id());
        $this->db->update(db_prefix() . 'notifications', [
            'isread' => 1,
        ]);
        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    public function set_notification_read_inline($id)
    {
        $this->db->where('touserid', get_staff_user_id());
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'notifications', [
            'isread_inline' => 1,
        ]);
    }

    public function set_desktop_notification_read($id)
    {
        $this->db->where('touserid', get_staff_user_id());
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'notifications', [
            'isread'        => 1,
            'isread_inline' => 1,
        ]);
    }

    public function mark_all_notifications_as_read_inline()
    {
        $this->db->where('touserid', get_staff_user_id());
        $this->db->update(db_prefix() . 'notifications', [
            'isread_inline' => 1,
            'isread'        => 1,
        ]);
    }

    /**
     * Dismiss announcement
     * @param  array  $data  announcement data
     * @param  boolean $staff is staff or client
     * @return boolean
     */
    public function dismiss_announcement($id, $staff = true)
    {
        if ($staff == false) {
            $userid = get_contact_user_id();
        } //$staff == false
        else {
            $userid = get_staff_user_id();
        }
        $data['announcementid'] = $id;
        $data['userid']         = $userid;
        $data['staff']          = $staff;
        $this->db->insert(db_prefix() . 'dismissed_announcements', $data);

        return true;
    }

    /**
     * Perform search on top header
     * @since  Version 1.0.1
     * @param  string $q search
     * @return array    search results
     */
    public function perform_search($q)
    {
        $q = trim($q);
        $this->load->model('staff_model');
        $is_admin                       = is_admin();
        $result                         = [];
        $limit                          = get_option('limit_top_search_bar_results_to');
        $have_assigned_customers        = have_assigned_customers();
        $have_permission_customers_view = has_permission('customers', '', 'view');
        if ($have_assigned_customers || $have_permission_customers_view) {

            // Clients
            $this->db->select(implode(',', prefixed_table_fields_array(db_prefix() . 'clients')) . ',' . get_sql_select_client_company());

            $this->db->join(db_prefix() . 'countries', db_prefix() . 'countries.country_id = ' . db_prefix() . 'clients.country', 'left');
            $this->db->join(db_prefix() . 'contacts', db_prefix() . 'contacts.userid = ' . db_prefix() . 'clients.userid AND is_primary = 1', 'left');
            $this->db->from(db_prefix() . 'clients');
            if ($have_assigned_customers && !$have_permission_customers_view) {
                $this->db->where(db_prefix() . 'clients.userid IN (SELECT customer_id FROM ' . db_prefix() . 'customer_admins WHERE staff_id=' . get_staff_user_id() . ')');
            }

            $this->db->where('(company LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR vat LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR ' . db_prefix() . 'clients.phonenumber LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR ' . db_prefix() . 'contacts.phonenumber LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR city LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR zip LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR state LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR zip LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR address LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR email LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR CONCAT(firstname, \' \', lastname) LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR CONCAT(lastname, \' \', firstname) LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR ' . db_prefix() . 'countries.short_name LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR ' . db_prefix() . 'countries.long_name LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR ' . db_prefix() . 'countries.numcode LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                )');

            $this->db->limit($limit);
            $result[] = [
                'result'         => $this->db->get()->result_array(),
                'type'           => 'clients',
                'search_heading' => _l('clients'),
            ];
        }


        $staff_search = $this->_search_staff($q, $limit);
        if (count($staff_search['result']) > 0) {
            $result[] = $staff_search;
        }


        $where_contacts = '';
        if ($have_assigned_customers && !$have_permission_customers_view) {
            $where_contacts = db_prefix() . 'contacts.userid IN (SELECT customer_id FROM ' . db_prefix() . 'customer_admins WHERE staff_id=' . get_staff_user_id() . ')';
        }


        $contacts_search = $this->_search_contacts($q, $limit, $where_contacts);
        if (count($contacts_search['result']) > 0) {
            $result[] = $contacts_search;
        }

        $tickets_search = $this->_search_tickets($q, $limit);
        if (count($tickets_search['result']) > 0) {
            $result[] = $tickets_search;
        }

        $leads_search = $this->_search_leads($q, $limit);
        if (count($leads_search['result']) > 0) {
            $result[] = $leads_search;
        }

        $proposals_search = $this->_search_proposals($q, $limit);
        if (count($proposals_search['result']) > 0) {
            $result[] = $proposals_search;
        }

        $invoices_search = $this->_search_invoices($q, $limit);
        if (count($invoices_search['result']) > 0) {
            $result[] = $invoices_search;
        }

        $credit_notes_search = $this->_search_credit_notes($q, $limit);
        if (count($credit_notes_search['result']) > 0) {
            $result[] = $credit_notes_search;
        }

        $estimates_search = $this->_search_estimates($q, $limit);
        if (count($estimates_search['result']) > 0) {
            $result[] = $estimates_search;
        }

        $expenses_search = $this->_search_expenses($q, $limit);
        if (count($expenses_search['result']) > 0) {
            $result[] = $expenses_search;
        }

        $projects_search = $this->_search_projects($q, $limit);
        if (count($projects_search['result']) > 0) {
            $result[] = $projects_search;
        }

        $contracts_search = $this->_search_contracts($q, $limit);
        if (count($contracts_search['result']) > 0) {
            $result[] = $contracts_search;
        }


        if (has_permission('knowledge_base', '', 'view')) {
            // Knowledge base articles
            $this->db->select()->from(db_prefix() . 'knowledge_base')->like('subject', $q)->or_like('description', $q)->or_like('slug', $q)->limit($limit);

            $this->db->order_by('subject', 'ASC');

            $result[] = [
                'result'         => $this->db->get()->result_array(),
                'type'           => 'knowledge_base_articles',
                'search_heading' => _l('kb_string'),
            ];
        }

        // Tasks Search
        $tasks = has_permission('tasks', '', 'view');
        // Staff tasks
        $this->db->select();
        $this->db->from(db_prefix() . 'tasks');
        if (!$is_admin) {
            if (!$tasks) {
                $where = '(id IN (SELECT taskid FROM ' . db_prefix() . 'task_assigned WHERE staffid = ' . get_staff_user_id() . ') OR id IN (SELECT taskid FROM ' . db_prefix() . 'task_followers WHERE staffid = ' . get_staff_user_id() . ') OR (addedfrom=' . get_staff_user_id() . ' AND is_added_from_contact=0) ';
                if (get_option('show_all_tasks_for_project_member') == 1) {
                    $where .= ' OR (rel_type="project" AND rel_id IN (SELECT project_id FROM ' . db_prefix() . 'project_members WHERE staff_id=' . get_staff_user_id() . '))';
                }
                $where .= ' OR is_public = 1)';
                $this->db->where($where);
            } //!$tasks
        } //!$is_admin
        if (!startsWith($q, '#')) {
            $this->db->where('(name LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\' OR description LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\')');
        } else {
            $this->db->where('id IN
                (SELECT rel_id FROM ' . db_prefix() . 'taggables WHERE tag_id IN
                (SELECT id FROM ' . db_prefix() . 'tags WHERE name="' . $this->db->escape_str(strafter($q, '#')) . '")
                AND ' . db_prefix() . 'taggables.rel_type=\'task\' GROUP BY rel_id HAVING COUNT(tag_id) = 1)
                ');
        }

        $this->db->limit($limit);
        $this->db->order_by('name', 'ASC');

        $result[] = [
            'result'         => $this->db->get()->result_array(),
            'type'           => 'tasks',
            'search_heading' => _l('tasks'),
        ];


        // Payments search
        $has_permission_view_payments     = has_permission('payments', '', 'view');
        $has_permission_view_invoices_own = has_permission('invoices', '', 'view_own');

        if (has_permission('payments', '', 'view') || $has_permission_view_invoices_own || get_option('allow_staff_view_invoices_assigned') == '1') {
            if (is_numeric($q)) {
                $q = trim($q);
                $q = ltrim($q, '0');
            } elseif (startsWith($q, get_option('invoice_prefix'))) {
                $q = strafter($q, get_option('invoice_prefix'));
                $q = trim($q);
                $q = ltrim($q, '0');
            }
            $noPermissionQuery = get_invoices_where_sql_for_staff(get_staff_user_id());
            // Invoice payment records
            $this->db->select('*,' . db_prefix() . 'invoicepaymentrecords.id as paymentid');
            $this->db->from(db_prefix() . 'invoicepaymentrecords');
            $this->db->join(db_prefix() . 'payment_modes', '' . db_prefix() . 'invoicepaymentrecords.paymentmode = ' . db_prefix() . 'payment_modes.id', 'LEFT');
            $this->db->join(db_prefix() . 'invoices', '' . db_prefix() . 'invoices.id = ' . db_prefix() . 'invoicepaymentrecords.invoiceid');

            if (!$has_permission_view_payments) {
                $this->db->where('invoiceid IN (select id from ' . db_prefix() . 'invoices where ' . $noPermissionQuery . ')');
            }

            $this->db->where('(' . db_prefix() . 'invoicepaymentrecords.id LIKE "' . $this->db->escape_like_str($q) . '"
                OR paymentmode LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR ' . db_prefix() . 'payment_modes.name LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR ' . db_prefix() . 'invoicepaymentrecords.note LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR number LIKE "' . $this->db->escape_like_str($q) . ' ESCAPE \'!\'"
                )');

            $this->db->order_by(db_prefix() . 'invoicepaymentrecords.date', 'ASC');

            $result[] = [
                'result'         => $this->db->get()->result_array(),
                'type'           => 'invoice_payment_records',
                'search_heading' => _l('payments'),
            ];
        }

        // Custom fields only admins
        if ($is_admin) {
            $this->db->select()->from(db_prefix() . 'customfieldsvalues')->like('value', $q)->limit($limit);
            $result[] = [
                'result'         => $this->db->get()->result_array(),
                'type'           => 'custom_fields',
                'search_heading' => _l('custom_fields'),
            ];
        }

        // Invoice Items Search
        $has_permission_view_invoices       = has_permission('invoices', '', 'view');
        $has_permission_view_invoices_own   = has_permission('invoices', '', 'view_own');
        $allow_staff_view_invoices_assigned = get_option('allow_staff_view_invoices_assigned');

        if ($has_permission_view_invoices || $has_permission_view_invoices_own || $allow_staff_view_invoices_assigned == '1') {
            $noPermissionQuery = get_invoices_where_sql_for_staff(get_staff_user_id());
            $this->db->select()->from(db_prefix() . 'itemable');
            $this->db->where('rel_type', 'invoice');
            $this->db->where('(description LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\' OR long_description LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\')');

            if (!$has_permission_view_invoices) {
                $this->db->where('rel_id IN (select id from ' . db_prefix() . 'invoices where ' . $noPermissionQuery . ')');
            }

            $this->db->order_by('description', 'ASC');
            $result[] = [
                'result'         => $this->db->get()->result_array(),
                'type'           => 'invoice_items',
                'search_heading' => _l('invoice_items'),
            ];
        }

        // Estimate Items Search
        $has_permission_view_estimates       = has_permission('estimates', '', 'view');
        $has_permission_view_estimates_own   = has_permission('estimates', '', 'view_own');
        $allow_staff_view_estimates_assigned = get_option('allow_staff_view_estimates_assigned');
        if ($has_permission_view_estimates || $has_permission_view_estimates_own || $allow_staff_view_estimates_assigned) {
            $noPermissionQuery = get_estimates_where_sql_for_staff(get_staff_user_id());

            $this->db->select()->from(db_prefix() . 'itemable');
            $this->db->where('rel_type', 'estimate');

            if (!$has_permission_view_estimates) {
                $this->db->where('rel_id IN (select id from ' . db_prefix() . 'estimates where ' . $noPermissionQuery . ')');
            }
            $this->db->where('(description LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\' OR long_description LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\')');
            $this->db->order_by('description', 'ASC');
            $result[] = [
                'result'         => $this->db->get()->result_array(),
                'type'           => 'estimate_items',
                'search_heading' => _l('estimate_items'),
            ];
        }

        $result = hooks()->apply_filters('global_search_result_query', $result, $q, $limit);

        return $result;
    }

    public function _search_proposals($q, $limit = 0)
    {
        $result = [
            'result'         => [],
            'type'           => 'proposals',
            'search_heading' => _l('proposals'),
        ];

        $has_permission_view_proposals     = has_permission('proposals', '', 'view');
        $has_permission_view_proposals_own = has_permission('proposals', '', 'view_own');

        if ($has_permission_view_proposals || $has_permission_view_proposals_own || get_option('allow_staff_view_proposals_assigned') == '1') {
            if (is_numeric($q)) {
                $q = trim($q);
                $q = ltrim($q, '0');
            } elseif (startsWith($q, get_option('proposal_number_prefix'))) {
                $q = strafter($q, get_option('proposal_number_prefix'));
                $q = trim($q);
                $q = ltrim($q, '0');
            }

            $noPermissionQuery = get_proposals_sql_where_staff(get_staff_user_id());

            // Proposals
            $this->db->select('*,' . db_prefix() . 'proposals.id as id');
            $this->db->from(db_prefix() . 'proposals');
            $this->db->join(db_prefix() . 'currencies', db_prefix() . 'currencies.id = ' . db_prefix() . 'proposals.currency');

            if (!$has_permission_view_proposals) {
                $this->db->where($noPermissionQuery);
            }

            $this->db->where('(
                ' . db_prefix() . 'proposals.id LIKE "' . $q . '%"
                OR ' . db_prefix() . 'proposals.subject LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR ' . db_prefix() . 'proposals.content LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR ' . db_prefix() . 'proposals.proposal_to LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR ' . db_prefix() . 'proposals.zip LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR ' . db_prefix() . 'proposals.state LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR ' . db_prefix() . 'proposals.city LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR ' . db_prefix() . 'proposals.address LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR ' . db_prefix() . 'proposals.email LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR ' . db_prefix() . 'proposals.phone LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                )');

            $this->db->order_by(db_prefix() . 'proposals.id', 'desc');
            if ($limit != 0) {
                $this->db->limit($limit);
            }
            $result['result'] = $this->db->get()->result_array();
        }

        return $result;
    }

    public function _search_leads($q, $limit = 0, $where = [])
    {
        $result = [
            'result'         => [],
            'type'           => 'leads',
            'search_heading' => _l('leads'),
        ];

        $has_permission_view = has_permission('leads', '', 'view');

        if (is_staff_member()) {
            // Leads
            $this->db->select();
            $this->db->from(db_prefix() . 'leads');

            if (!$has_permission_view) {
                $this->db->where('(assigned = ' . get_staff_user_id() . ' OR addedfrom = ' . get_staff_user_id() . ' OR is_public=1)');
            }

            if (!startsWith($q, '#')) {
                $this->db->where('(name LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                    OR title LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                    OR company LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                    OR zip LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                    OR city LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                    OR state LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                    OR address LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                    OR email LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                    OR phonenumber LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                    )');
            } else {
                $this->db->where('id IN
                    (SELECT rel_id FROM ' . db_prefix() . 'taggables WHERE tag_id IN
                    (SELECT id FROM ' . db_prefix() . 'tags WHERE name="' . $this->db->escape_str(strafter($q, '#')) . '")
                    AND ' . db_prefix() . 'taggables.rel_type=\'lead\' GROUP BY rel_id HAVING COUNT(tag_id) = 1)
                    ');
            }


            $this->db->where($where);

            if ($limit != 0) {
                $this->db->limit($limit);
            }
            $this->db->order_by('name', 'ASC');
            $result['result'] = $this->db->get()->result_array();
        }

        return $result;
    }

    public function _search_tickets($q, $limit = 0)
    {
        $result = [
            'result'         => [],
            'type'           => 'tickets',
            'search_heading' => _l('support_tickets'),
        ];

        if (is_staff_member() || (!is_staff_member() && get_option('access_tickets_to_none_staff_members') == 1)) {
            $is_admin = is_admin();

            $where = '';
            if (!$is_admin && get_option('staff_access_only_assigned_departments') == 1) {
                $this->load->model('departments_model');
                $staff_deparments_ids = $this->departments_model->get_staff_departments(get_staff_user_id(), true);
                $departments_ids      = [];
                if (count($staff_deparments_ids) == 0) {
                    $departments = $this->departments_model->get();
                    foreach ($departments as $department) {
                        array_push($departments_ids, $department['departmentid']);
                    }
                } else {
                    $departments_ids = $staff_deparments_ids;
                }
                if (count($departments_ids) > 0) {
                    $where = 'department IN (SELECT departmentid FROM ' . db_prefix() . 'staff_departments WHERE departmentid IN (' . implode(',', $departments_ids) . ') AND staffid="' . get_staff_user_id() . '")';
                }
            }

            $this->db->select();
            $this->db->from(db_prefix() . 'tickets');
            $this->db->join(db_prefix() . 'departments', db_prefix() . 'departments.departmentid = ' . db_prefix() . 'tickets.department');
            $this->db->join(db_prefix() . 'clients', db_prefix() . 'clients.userid = ' . db_prefix() . 'tickets.userid', 'left');
            $this->db->join(db_prefix() . 'contacts', db_prefix() . 'contacts.id = ' . db_prefix() . 'tickets.contactid', 'left');


            if (!startsWith($q, '#')) {
                $this->db->where('(
                    ticketid LIKE "' . $q . '%"
                    OR subject LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                    OR message LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                    OR ' . db_prefix() . 'contacts.email LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                    OR CONCAT(firstname, \' \', lastname) LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                    OR CONCAT(lastname, \' \', firstname) LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                    OR company LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                    OR vat LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                    OR ' . db_prefix() . 'contacts.phonenumber LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                    OR ' . db_prefix() . 'clients.phonenumber LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                    OR city LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                    OR state LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                    OR address LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                    OR ' . db_prefix() . 'departments.name LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                    )');

                if ($where != '') {
                    $this->db->where($where);
                }
            } else {
                $this->db->where('ticketid IN
                    (SELECT rel_id FROM ' . db_prefix() . 'taggables WHERE tag_id IN
                    (SELECT id FROM ' . db_prefix() . 'tags WHERE name="' . $this->db->escape_str(strafter($q, '#')) . '")
                    AND ' . db_prefix() . 'taggables.rel_type=\'ticket\' GROUP BY rel_id HAVING COUNT(tag_id) = 1)
                    ');
            }

            if ($limit != 0) {
                $this->db->limit($limit);
            }
            $this->db->order_by('ticketid', 'DESC');
            $result['result'] = $this->db->get()->result_array();
        }

        return $result;
    }

    public function _search_contacts($q, $limit = 0, $where = '')
    {
        $result = [
            'result'         => [],
            'type'           => 'contacts',
            'search_heading' => _l('customer_contacts'),
        ];

        $have_assigned_customers        = have_assigned_customers();
        $have_permission_customers_view = has_permission('customers', '', 'view');

        if ($have_assigned_customers || $have_permission_customers_view) {
            // Contacts
            $this->db->select(implode(',', prefixed_table_fields_array(db_prefix() . 'contacts')) . ',company');
            $this->db->from(db_prefix() . 'contacts');

            $this->db->join(db_prefix() . 'clients', '' . db_prefix() . 'clients.userid=' . db_prefix() . 'contacts.userid', 'left');
            $this->db->where('(firstname LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR lastname LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR email LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR CONCAT(firstname, \' \', lastname) LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR CONCAT(lastname, \' \', firstname) LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR ' . db_prefix() . 'contacts.phonenumber LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR ' . db_prefix() . 'contacts.title LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR company LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                )');

            if ($where != '') {
                $this->db->where($where);
            }

            if ($limit != 0) {
                $this->db->limit($limit);
            }

            $this->db->order_by('firstname', 'ASC');
            $result['result'] = $this->db->get()->result_array();
        }

        return $result;
    }

    public function _search_staff($q, $limit = 0)
    {
        $result = [
            'result'         => [],
            'type'           => 'staff',
            'search_heading' => _l('staff_members'),
        ];

        if (has_permission('staff', '', 'view')) {
            // Staff
            $this->db->select();
            $this->db->from(db_prefix() . 'staff');
            $this->db->like('firstname', $q);
            $this->db->or_like('lastname', $q);
            $this->db->or_like("CONCAT(firstname, ' ', lastname)", $q, false);
            $this->db->or_like("CONCAT(lastname, ' ', firstname)", $q, false);
            $this->db->or_like('facebook', $q);
            $this->db->or_like('linkedin', $q);
            $this->db->or_like('phonenumber', $q);
            $this->db->or_like('email', $q);
            $this->db->or_like('skype', $q);

            if ($limit != 0) {
                $this->db->limit($limit);
            }
            $this->db->order_by('firstname', 'ASC');
            $result['result'] = $this->db->get()->result_array();
        }

        return $result;
    }

    public function _search_contracts($q, $limit = 0)
    {
        $result = [
            'result'         => [],
            'type'           => 'contracts',
            'search_heading' => _l('contracts'),
        ];

        $has_permission_view_contracts = has_permission('contracts', '', 'view');
        if ($has_permission_view_contracts || has_permission('contracts', '', 'view_own')) {
            // Contracts
            $this->db->select();
            $this->db->from(db_prefix() . 'contracts');
            if (!$has_permission_view_contracts) {
                $this->db->where(db_prefix() . 'contracts.addedfrom', get_staff_user_id());
            }

            $this->db->where('(description LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\' OR subject LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\')');

            if ($limit != 0) {
                $this->db->limit($limit);
            }
            $this->db->order_by('subject', 'ASC');
            $result['result'] = $this->db->get()->result_array();
        }

        return $result;
    }

    public function _search_projects($q, $limit = 0, $where = false)
    {
        $result = [
            'result'         => [],
            'type'           => 'projects',
            'search_heading' => _l('projects'),
        ];

        $projects = has_permission('projects', '', 'view');
        // Projects
        $this->db->select();
        $this->db->from(db_prefix() . 'projects');
        $this->db->join(db_prefix() . 'clients', db_prefix() . 'clients.userid = ' . db_prefix() . 'projects.clientid');
        if (!$projects) {
            $this->db->where(db_prefix() . 'projects.id IN (SELECT project_id FROM ' . db_prefix() . 'project_members WHERE staff_id=' . get_staff_user_id() . ')');
        }
        if ($where != false) {
            $this->db->where($where);
        }
        if (!startsWith($q, '#')) {
            $this->db->where('(company LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR description LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR name LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR vat LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR phonenumber LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR city LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR zip LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR state LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR zip LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR address LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                )');
        } else {
            $this->db->where('id IN
                (SELECT rel_id FROM ' . db_prefix() . 'taggables WHERE tag_id IN
                (SELECT id FROM ' . db_prefix() . 'tags WHERE name="' . $this->db->escape_str(strafter($q, '#')) . '")
                AND ' . db_prefix() . 'taggables.rel_type=\'project\' GROUP BY rel_id HAVING COUNT(tag_id) = 1)
                ');
        }

        if ($limit != 0) {
            $this->db->limit($limit);
        }

        $this->db->order_by('name', 'ASC');
        $result['result'] = $this->db->get()->result_array();

        return $result;
    }

    public function _search_invoices($q, $limit = 0)
    {
        $result = [
            'result'         => [],
            'type'           => 'invoices',
            'search_heading' => _l('invoices'),
        ];
        $has_permission_view_invoices     = has_permission('invoices', '', 'view');
        $has_permission_view_invoices_own = has_permission('invoices', '', 'view_own');

        if ($has_permission_view_invoices || $has_permission_view_invoices_own || get_option('allow_staff_view_invoices_assigned') == '1') {
            if (is_numeric($q)) {
                $q = trim($q);
                $q = ltrim($q, '0');
            } elseif (startsWith($q, get_option('invoice_prefix'))) {
                $q = strafter($q, get_option('invoice_prefix'));
                $q = trim($q);
                $q = ltrim($q, '0');
            }
            $invoice_fields    = prefixed_table_fields_array(db_prefix() . 'invoices');
            $clients_fields    = prefixed_table_fields_array(db_prefix() . 'clients');
            $noPermissionQuery = get_invoices_where_sql_for_staff(get_staff_user_id());
            // Invoices
            $this->db->select(implode(',', $invoice_fields) . ',' . implode(',', $clients_fields) . ',' . db_prefix() . 'invoices.id as invoiceid,' . get_sql_select_client_company());
            $this->db->from(db_prefix() . 'invoices');
            $this->db->join(db_prefix() . 'clients', db_prefix() . 'clients.userid = ' . db_prefix() . 'invoices.clientid', 'left');
            $this->db->join(db_prefix() . 'currencies', db_prefix() . 'currencies.id = ' . db_prefix() . 'invoices.currency');
            $this->db->join(db_prefix() . 'contacts', db_prefix() . 'contacts.userid = ' . db_prefix() . 'clients.userid AND is_primary = 1', 'left');

            if (!$has_permission_view_invoices) {
                $this->db->where($noPermissionQuery);
            }
            if (!startsWith($q, '#')) {
                $this->db->where('(
                ' . db_prefix() . 'invoices.number LIKE "' . $this->db->escape_like_str($q) . '"
                OR
                ' . db_prefix() . 'clients.company LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'invoices.clientnote LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.vat LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.phonenumber LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.city LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.state LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.zip LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.address LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'invoices.adminnote LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                CONCAT(firstname,\' \',lastname) LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                CONCAT(lastname,\' \',firstname) LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'invoices.billing_street LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'invoices.billing_city LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'invoices.billing_state LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'invoices.billing_zip LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'invoices.shipping_street LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'invoices.shipping_city LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'invoices.shipping_state LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'invoices.shipping_zip LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.billing_street LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.billing_city LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.billing_state LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.billing_zip LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.shipping_street LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.shipping_city LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.shipping_state LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.shipping_zip LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                )');
            } else {
                $this->db->where(db_prefix() . 'invoices.id IN
                (SELECT rel_id FROM ' . db_prefix() . 'taggables WHERE tag_id IN
                (SELECT id FROM ' . db_prefix() . 'tags WHERE name="' . $this->db->escape_str(strafter($q, '#')) . '")
                AND ' . db_prefix() . 'taggables.rel_type=\'invoice\' GROUP BY rel_id HAVING COUNT(tag_id) = 1)
                ');
            }


            $this->db->order_by('number,YEAR(date)', 'desc');
            if ($limit != 0) {
                $this->db->limit($limit);
            }

            $result['result'] = $this->db->get()->result_array();
        }

        return $result;
    }

    public function _search_credit_notes($q, $limit = 0)
    {
        $result = [
            'result'         => [],
            'type'           => 'credit_note',
            'search_heading' => _l('credit_notes'),
        ];

        $has_permission_view_credit_notes     = has_permission('credit_notes', '', 'view');
        $has_permission_view_credit_notes_own = has_permission('credit_notes', '', 'view_own');

        if ($has_permission_view_credit_notes || $has_permission_view_credit_notes_own) {
            if (is_numeric($q)) {
                $q = trim($q);
                $q = ltrim($q, '0');
            } elseif (startsWith($q, get_option('credit_note_prefix'))) {
                $q = strafter($q, get_option('credit_note_prefix'));
                $q = trim($q);
                $q = ltrim($q, '0');
            }
            $credit_note_fields = prefixed_table_fields_array(db_prefix() . 'creditnotes');
            $clients_fields     = prefixed_table_fields_array(db_prefix() . 'clients');
            // Invoices
            $this->db->select(implode(',', $credit_note_fields) . ',' . implode(',', $clients_fields) . ',' . db_prefix() . 'creditnotes.id as credit_note_id,' . get_sql_select_client_company());
            $this->db->from(db_prefix() . 'creditnotes');
            $this->db->join(db_prefix() . 'clients', db_prefix() . 'clients.userid = ' . db_prefix() . 'creditnotes.clientid', 'left');
            $this->db->join(db_prefix() . 'currencies', db_prefix() . 'currencies.id = ' . db_prefix() . 'creditnotes.currency');
            $this->db->join(db_prefix() . 'contacts', db_prefix() . 'contacts.userid = ' . db_prefix() . 'clients.userid AND is_primary = 1', 'left');

            if (!$has_permission_view_credit_notes) {
                $this->db->where(db_prefix() . 'creditnotes.addedfrom', get_staff_user_id());
            }

            $this->db->where('(
                ' . db_prefix() . 'creditnotes.number LIKE "' . $this->db->escape_like_str($q) . '"
                OR
                ' . db_prefix() . 'clients.company LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'creditnotes.clientnote LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.vat LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.phonenumber LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.city LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.state LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.zip LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.address LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'creditnotes.adminnote LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                CONCAT(firstname,\' \',lastname) LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                CONCAT(lastname,\' \',firstname) LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'creditnotes.billing_street LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'creditnotes.billing_city LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'creditnotes.billing_state LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'creditnotes.billing_zip LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'creditnotes.shipping_street LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'creditnotes.shipping_city LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'creditnotes.shipping_state LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'creditnotes.shipping_zip LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.billing_street LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.billing_city LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.billing_state LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.billing_zip LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.shipping_street LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.shipping_city LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.shipping_state LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.shipping_zip LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                )');


            $this->db->order_by('number', 'desc');
            if ($limit != 0) {
                $this->db->limit($limit);
            }

            $result['result'] = $this->db->get()->result_array();
        }

        return $result;
    }

    public function _search_estimates($q, $limit = 0)
    {
        $result = [
            'result'         => [],
            'type'           => 'estimates',
            'search_heading' => _l('estimates'),
        ];

        $has_permission_view_estimates     = has_permission('estimates', '', 'view');
        $has_permission_view_estimates_own = has_permission('estimates', '', 'view_own');

        if ($has_permission_view_estimates || $has_permission_view_estimates_own || get_option('allow_staff_view_estimates_assigned') == '1') {
            if (is_numeric($q)) {
                $q = trim($q);
                $q = ltrim($q, '0');
            } elseif (startsWith($q, get_option('estimate_prefix'))) {
                $q = strafter($q, get_option('estimate_prefix'));
                $q = trim($q);
                $q = ltrim($q, '0');
            }
            // Estimates
            $estimates_fields  = prefixed_table_fields_array(db_prefix() . 'estimates');
            $clients_fields    = prefixed_table_fields_array(db_prefix() . 'clients');
            $noPermissionQuery = get_estimates_where_sql_for_staff(get_staff_user_id());

            $this->db->select(implode(',', $estimates_fields) . ',' . implode(',', $clients_fields) . ',' . db_prefix() . 'estimates.id as estimateid,' . get_sql_select_client_company());
            $this->db->from(db_prefix() . 'estimates');
            $this->db->join(db_prefix() . 'clients', db_prefix() . 'clients.userid = ' . db_prefix() . 'estimates.clientid', 'left');
            $this->db->join(db_prefix() . 'currencies', db_prefix() . 'currencies.id = ' . db_prefix() . 'estimates.currency');
            $this->db->join(db_prefix() . 'contacts', db_prefix() . 'contacts.userid = ' . db_prefix() . 'clients.userid AND is_primary = 1', 'left');

            if (!$has_permission_view_estimates) {
                $this->db->where($noPermissionQuery);
            }

            $this->db->where('(
                ' . db_prefix() . 'estimates.number LIKE "' . $this->db->escape_like_str($q) . '"
                OR
                ' . db_prefix() . 'clients.company LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'estimates.clientnote LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.vat LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.phonenumber LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.city LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.state LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.zip LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                address LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'estimates.adminnote LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'estimates.billing_street LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'estimates.billing_city LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'estimates.billing_state LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'estimates.billing_zip LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'estimates.shipping_street LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'estimates.shipping_city LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'estimates.shipping_state LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'estimates.shipping_zip LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.billing_street LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.billing_city LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.billing_state LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.billing_zip LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.shipping_street LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.shipping_city LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.shipping_state LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR
                ' . db_prefix() . 'clients.shipping_zip LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                )');

            $this->db->order_by('number,YEAR(date)', 'desc');
            if ($limit != 0) {
                $this->db->limit($limit);
            }
            $result['result'] = $this->db->get()->result_array();
        }

        return $result;
    }

    public function _search_expenses($q, $limit = 0)
    {
        $result = [
            'result'         => [],
            'type'           => 'expenses',
            'search_heading' => _l('expenses'),
        ];

        $has_permission_expenses_view     = has_permission('expenses', '', 'view');
        $has_permission_expenses_view_own = has_permission('expenses', '', 'view_own');

        if ($has_permission_expenses_view || $has_permission_expenses_view_own) {
            // Expenses
            $this->db->select('*,' . db_prefix() . 'expenses.amount as amount,' . db_prefix() . 'expenses_categories.name as category_name,' . db_prefix() . 'payment_modes.name as payment_mode_name,' . db_prefix() . 'taxes.name as tax_name, ' . db_prefix() . 'expenses.id as expenseid,' . db_prefix() . 'currencies.name as currency_name');
            $this->db->from(db_prefix() . 'expenses');
            $this->db->join(db_prefix() . 'clients', db_prefix() . 'clients.userid = ' . db_prefix() . 'expenses.clientid', 'left');
            $this->db->join(db_prefix() . 'payment_modes', db_prefix() . 'payment_modes.id = ' . db_prefix() . 'expenses.paymentmode', 'left');
            $this->db->join(db_prefix() . 'taxes', db_prefix() . 'taxes.id = ' . db_prefix() . 'expenses.tax', 'left');
            $this->db->join(db_prefix() . 'expenses_categories', db_prefix() . 'expenses_categories.id = ' . db_prefix() . 'expenses.category');
            $this->db->join(db_prefix() . 'currencies', '' . db_prefix() . 'currencies.id = ' . db_prefix() . 'expenses.currency', 'left');
            if (!$has_permission_expenses_view) {
                $this->db->where(db_prefix() . 'expenses.addedfrom', get_staff_user_id());
            }

            $this->db->where('(company LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR paymentmode LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR ' . db_prefix() . 'payment_modes.name LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR vat LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR phonenumber LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR city LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR zip LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR address LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR state LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR ' . db_prefix() . 'expenses_categories.name LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR ' . db_prefix() . 'expenses.note LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                OR ' . db_prefix() . 'expenses.expense_name LIKE "%' . $this->db->escape_like_str($q) . '%" ESCAPE \'!\'
                )');

            if ($limit != 0) {
                $this->db->limit($limit);
            }
            $this->db->order_by('date', 'DESC');
            $result['result'] = $this->db->get()->result_array();
        }

        return $result;
    }
}
