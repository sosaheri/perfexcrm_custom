<?php

defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Function that format task status for the final user
 * @param  string  $id    status id
 * @param  boolean $text
 * @param  boolean $clean
 * @return string
 */
function format_task_status($status, $text = false, $clean = false)
{
    if (!is_array($status)) {
        $status = get_task_status_by_id($status);
    }

    $status_name = $status['name'];

    $status_name = hooks()->apply_filters('task_status_name', $status_name, $status);

    if ($clean == true) {
        return $status_name;
    }

    $style = '';
    $class = '';
    if ($text == false) {
        $style = 'border: 1px solid ' . $status['color'] . ';color:' . $status['color'] . ';';
        $class = 'label';
    } else {
        $style = 'color:' . $status['color'] . ';';
    }

    return '<span class="' . $class . '" style="' . $style . '">' . $status_name . '</span>';
}

/**
 * Return predefined tasks priorities
 * @return array
 */
function get_tasks_priorities()
{
    return hooks()->apply_filters('tasks_priorities', [
        [
            'id'    => 1,
            'name'  => _l('task_priority_low'),
            'color' => '#777',

        ],
        [
            'id'    => 2,
            'name'  => _l('task_priority_medium'),
            'color' => '#03a9f4',

        ],
        [
            'id'    => 3,
            'name'  => _l('task_priority_high'),
            'color' => '#ff6f00',
        ],
        [
            'id'    => 4,
            'name'  => _l('task_priority_urgent'),
            'color' => '#fc2d42',
        ],
    ]);
}

/**
 * Get project name by passed id
 * @param  mixed $id
 * @return string
 */
function get_task_subject_by_id($id)
{
    $CI = &get_instance();
    $CI->db->select('name');
    $CI->db->where('id', $id);
    $task = $CI->db->get(db_prefix() . 'tasks')->row();
    if ($task) {
        return $task->name;
    }

    return '';
}

/**
 * Get task status by passed task id
 * @param  mixed $id task id
 * @return array
 */
function get_task_status_by_id($id)
{
    $CI       = &get_instance();
    $statuses = $CI->tasks_model->get_statuses();

    $status = [
        'id'         => 0,
        'bg_color'   => '#333',
        'text_color' => '#333',
        'name'       => '[Status Not Found]',
        'order'      => 1,
    ];

    foreach ($statuses as $s) {
        if ($s['id'] == $id) {
            $status = $s;

            break;
        }
    }

    return $status;
}

/**
 * Format task priority based on passed priority id
 * @param  mixed $id
 * @return string
 */
function task_priority($id)
{
    foreach (get_tasks_priorities() as $priority) {
        if ($priority['id'] == $id) {
            return $priority['name'];
        }
    }

    // Not exists?
    return $id;
}

/**
 * Get and return task priority color
 * @param  mixed $id priority id
 * @return string
 */
function task_priority_color($id)
{
    foreach (get_tasks_priorities() as $priority) {
        if ($priority['id'] == $id) {
            return $priority['color'];
        }
    }

    // Not exists?
    return '#333';
}
/**
 * Format html task assignees
 * This function is used to save up on query
 * @param  string $ids   string coma separated assignee staff id
 * @param  string $names compa separated in the same order like assignee ids
 * @return string
 */
function format_members_by_ids_and_names($ids, $names, $hidden_export_table = true, $image_class = 'staff-profile-image-small')
{
    $outputAssignees = '';
    $exportAssignees = '';

    $assignees   = explode(',', $names);
    $assigneeIds = explode(',', $ids);
    foreach ($assignees as $key => $assigned) {
        $assignee_id = $assigneeIds[$key];
        $assignee_id = trim($assignee_id);
        if ($assigned != '') {
            $outputAssignees .= '<a href="' . admin_url('profile/' . $assignee_id) . '">' .
                staff_profile_image($assignee_id, [
                    $image_class . ' mright5',
                ], 'small', [
                    'data-toggle' => 'tooltip',
                    'data-title'  => $assigned,
                ]) . '</a>';
            $exportAssignees .= $assigned . ', ';
        }
    }

    if ($exportAssignees != '') {
        $outputAssignees .= '<span class="hide">' . mb_substr($exportAssignees, 0, -2) . '</span>';
    }

    return $outputAssignees;
}

/**
 * Format task relation name
 * @param  string $rel_name current rel name
 * @param  mixed $rel_id   relation id
 * @param  string $rel_type relation type
 * @return string
 */
function task_rel_name($rel_name, $rel_id, $rel_type)
{
    if ($rel_type == 'invoice') {
        $rel_name = format_invoice_number($rel_id);
    } elseif ($rel_type == 'estimate') {
        $rel_name = format_estimate_number($rel_id);
    } elseif ($rel_type == 'proposal') {
        $rel_name = format_proposal_number($rel_id);
    }

    return $rel_name;
}

/**
 * Task relation link
 * @param  mixed $rel_id   relation id
 * @param  string $rel_type relation type
 * @return string
 */
function task_rel_link($rel_id, $rel_type)
{
    $link = '#';
    if ($rel_type == 'customer') {
        $link = admin_url('clients/client/' . $rel_id);
    } elseif ($rel_type == 'invoice') {
        $link = admin_url('invoices/list_invoices/' . $rel_id);
    } elseif ($rel_type == 'project') {
        $link = admin_url('projects/view/' . $rel_id);
    } elseif ($rel_type == 'estimate') {
        $link = admin_url('estimates/list_estimates/' . $rel_id);
    } elseif ($rel_type == 'contract') {
        $link = admin_url('contracts/contract/' . $rel_id);
    } elseif ($rel_type == 'ticket') {
        $link = admin_url('tickets/ticket/' . $rel_id);
    } elseif ($rel_type == 'expense') {
        $link = admin_url('expenses/list_expenses/' . $rel_id);
    } elseif ($rel_type == 'lead') {
        $link = admin_url('leads/index/' . $rel_id);
    } elseif ($rel_type == 'proposal') {
        $link = admin_url('proposals/list_proposals/' . $rel_id);
    }

    return $link;
}
/**
 * Prepares task array gantt data to be used in the gantt chart
 * @param  array $task task array
 * @return array
 */
function get_task_array_gantt_data($task, $dep_id = null, $defaultEnd = null)
{
    $data = [];

    $data['id']   = $task['id'];
    $data['desc'] = $task['name'];

    $data['start'] = strftime('%Y-%m-%d', strtotime($task['startdate']));

    if ($task['duedate']) {
        $data['end'] = strftime('%Y-%m-%d', strtotime($task['duedate']));
    } else {
        $data['end'] = $defaultEnd;
    }

    $data['desc']  = $task['name'] . ' - ' . _l('task_total_logged_time') . ' ' . seconds_to_time_format($task['total_logged_time']);
    $data['label'] = $task['name'];
    if ($task['duedate'] && date('Y-m-d') > $task['duedate'] && $task['status'] != Tasks_model::STATUS_COMPLETE) {
        $data['custom_class'] = 'ganttRed';
    } elseif ($task['status'] == Tasks_model::STATUS_COMPLETE) {
        $data['custom_class'] = 'ganttGreen';
    }

    $data['name']     = $task['name'];
    $data['task_id']  = $task['id'];
    $data['progress'] = 0;

    //for task in single project gantt
    if ($dep_id) {
        $data['dependencies'] = $dep_id;
    }

    if (!staff_can('edit', 'tasks') || is_client_logged_in()) {
        if (isset($data['custom_class'])) {
            $data['custom_class'] .= ' noDrag';
        } else {
            $data['custom_class'] = 'noDrag';
        }
    }

    return $data;
}
/**
 * Common function used to select task relation name
 * @return string
 */
function tasks_rel_name_select_query()
{
    return '(CASE rel_type
        WHEN "contract" THEN (SELECT subject FROM ' . db_prefix() . 'contracts WHERE ' . db_prefix() . 'contracts.id = ' . db_prefix() . 'tasks.rel_id)
        WHEN "estimate" THEN (SELECT id FROM ' . db_prefix() . 'estimates WHERE ' . db_prefix() . 'estimates.id = ' . db_prefix() . 'tasks.rel_id)
        WHEN "proposal" THEN (SELECT id FROM ' . db_prefix() . 'proposals WHERE ' . db_prefix() . 'proposals.id = ' . db_prefix() . 'tasks.rel_id)
        WHEN "invoice" THEN (SELECT id FROM ' . db_prefix() . 'invoices WHERE ' . db_prefix() . 'invoices.id = ' . db_prefix() . 'tasks.rel_id)
        WHEN "ticket" THEN (SELECT CONCAT(CONCAT("#",' . db_prefix() . 'tickets.ticketid), " - ", ' . db_prefix() . 'tickets.subject) FROM ' . db_prefix() . 'tickets WHERE ' . db_prefix() . 'tickets.ticketid=' . db_prefix() . 'tasks.rel_id)
        WHEN "lead" THEN (SELECT CASE ' . db_prefix() . 'leads.email WHEN "" THEN ' . db_prefix() . 'leads.name ELSE CONCAT(' . db_prefix() . 'leads.name, " - ", ' . db_prefix() . 'leads.email) END FROM ' . db_prefix() . 'leads WHERE ' . db_prefix() . 'leads.id=' . db_prefix() . 'tasks.rel_id)
        WHEN "customer" THEN (SELECT CASE company WHEN "" THEN (SELECT CONCAT(firstname, " ", lastname) FROM ' . db_prefix() . 'contacts WHERE userid = ' . db_prefix() . 'clients.userid and is_primary = 1) ELSE company END FROM ' . db_prefix() . 'clients WHERE ' . db_prefix() . 'clients.userid=' . db_prefix() . 'tasks.rel_id)
        WHEN "project" THEN (SELECT CONCAT(CONCAT(CONCAT("#",' . db_prefix() . 'projects.id)," - ",' . db_prefix() . 'projects.name), " - ", (SELECT CASE company WHEN "" THEN (SELECT CONCAT(firstname, " ", lastname) FROM ' . db_prefix() . 'contacts WHERE userid = ' . db_prefix() . 'clients.userid and is_primary = 1) ELSE company END FROM ' . db_prefix() . 'clients WHERE userid=' . db_prefix() . 'projects.clientid)) FROM ' . db_prefix() . 'projects WHERE ' . db_prefix() . 'projects.id=' . db_prefix() . 'tasks.rel_id)
        WHEN "expense" THEN (SELECT CASE expense_name WHEN "" THEN ' . db_prefix() . 'expenses_categories.name ELSE
         CONCAT(' . db_prefix() . 'expenses_categories.name, \' (\',' . db_prefix() . 'expenses.expense_name,\')\') END FROM ' . db_prefix() . 'expenses JOIN ' . db_prefix() . 'expenses_categories ON ' . db_prefix() . 'expenses_categories.id = ' . db_prefix() . 'expenses.category WHERE ' . db_prefix() . 'expenses.id=' . db_prefix() . 'tasks.rel_id)
        ELSE NULL
        END)';
}


/**
 * Tasks html table used all over the application for relation tasks
 * This table is not used for the main tasks table
 * @param  array  $table_attributes
 * @return string
 */
function init_relation_tasks_table($table_attributes = [])
{
    $table_data = [
        _l('the_number_sign'),
        [
            'name'     => _l('tasks_dt_name'),
            'th_attrs' => [
                'style' => 'width:200px',
            ],
        ],
        _l('task_status'),
        [
            'name'     => _l('tasks_dt_datestart'),
            'th_attrs' => [
                'style' => 'width:75px',
            ],
        ],
        [
            'name'     => _l('task_duedate'),
            'th_attrs' => [
                'style' => 'width:75px',
                'class' => 'duedate',
            ],
        ],
        [
            'name'     => _l('task_assigned'),
            'th_attrs' => [
                'style' => 'width:75px',
            ],
        ],
        _l('tags'),
        _l('tasks_list_priority'),
    ];

    array_unshift($table_data, [
        'name'     => '<span class="hide"> - </span><div class="checkbox mass_select_all_wrap"><input type="checkbox" id="mass_select_all" data-to-table="rel-tasks"><label></label></div>',
        'th_attrs' => ['class' => ($table_attributes['data-new-rel-type'] !== 'project' ? 'not_visible' : '')],
    ]);

    $custom_fields = get_custom_fields('tasks', [
        'show_on_table' => 1,
    ]);

    foreach ($custom_fields as $field) {
        array_push($table_data, $field['name']);
    }

    $table_data = hooks()->apply_filters('tasks_related_table_columns', $table_data);

    $name = 'rel-tasks';
    if ($table_attributes['data-new-rel-type'] == 'lead') {
        $name = 'rel-tasks-leads';
    }

    $table      = '';
    $CI         = &get_instance();
    $table_name = '.table-' . $name;
    $CI->load->view('admin/tasks/tasks_filter_by', [
        'view_table_name' => $table_name,
    ]);
    if (has_permission('tasks', '', 'create')) {
        $disabled   = '';
        $table_name = addslashes($table_name);
        if ($table_attributes['data-new-rel-type'] == 'customer' && is_numeric($table_attributes['data-new-rel-id'])) {
            if (total_rows(db_prefix() . 'clients', [
                'active' => 0,
                'userid' => $table_attributes['data-new-rel-id'],
            ]) > 0) {
                $disabled = ' disabled';
            }
        }
        // projects have button on top
        if ($table_attributes['data-new-rel-type'] != 'project') {
            echo "<a href='#' class='btn btn-info pull-left mbot25 mright5 new-task-relation" . $disabled . "' onclick=\"new_task_from_relation('$table_name'); return false;\" data-rel-id='" . $table_attributes['data-new-rel-id'] . "' data-rel-type='" . $table_attributes['data-new-rel-type'] . "'>" . _l('new_task') . '</a>';
        }
    }

    if ($table_attributes['data-new-rel-type'] == 'project') {
        echo "<a href='" . admin_url('tasks/detailed_overview?project_id=' . $table_attributes['data-new-rel-id']) . "' class='btn btn-success pull-right mbot25'>" . _l('detailed_overview') . '</a>';
        echo "<a href='" . admin_url('tasks/list_tasks?project_id=' . $table_attributes['data-new-rel-id'] . '&kanban=true') . "' class='btn btn-default pull-right mbot25 mright5 hidden-xs'>" . _l('view_kanban') . '</a>';
        echo '<div class="clearfix"></div>';
        echo $CI->load->view('admin/tasks/_bulk_actions', ['table' => '.table-rel-tasks'], true);
        echo $CI->load->view('admin/tasks/_summary', ['rel_id' => $table_attributes['data-new-rel-id'], 'rel_type' => 'project', 'table' => $table_name], true);
        echo '<a href="#" data-toggle="modal" data-target="#tasks_bulk_actions" class="hide bulk-actions-btn table-btn" data-table=".table-rel-tasks">' . _l('bulk_actions') . '</a>';
    } elseif ($table_attributes['data-new-rel-type'] == 'customer') {
        echo '<div class="clearfix"></div>';
        echo '<div id="tasks_related_filter">';
        echo '<p class="bold">' . _l('task_related_to') . ': </p>';

        echo '<div class="checkbox checkbox-inline mbot25">
        <input type="checkbox" checked value="customer" disabled id="ts_rel_to_customer" name="tasks_related_to[]">
        <label for="ts_rel_to_customer">' . _l('client') . '</label>
        </div>

        <div class="checkbox checkbox-inline mbot25">
        <input type="checkbox" value="project" id="ts_rel_to_project" name="tasks_related_to[]">
        <label for="ts_rel_to_project">' . _l('projects') . '</label>
        </div>

        <div class="checkbox checkbox-inline mbot25">
        <input type="checkbox" value="invoice" id="ts_rel_to_invoice" name="tasks_related_to[]">
        <label for="ts_rel_to_invoice">' . _l('invoices') . '</label>
        </div>

        <div class="checkbox checkbox-inline mbot25">
        <input type="checkbox" value="estimate" id="ts_rel_to_estimate" name="tasks_related_to[]">
        <label for="ts_rel_to_estimate">' . _l('estimates') . '</label>
        </div>

        <div class="checkbox checkbox-inline mbot25">
        <input type="checkbox" value="contract" id="ts_rel_to_contract" name="tasks_related_to[]">
        <label for="ts_rel_to_contract">' . _l('contracts') . '</label>
        </div>

        <div class="checkbox checkbox-inline mbot25">
        <input type="checkbox" value="ticket" id="ts_rel_to_ticket" name="tasks_related_to[]">
        <label for="ts_rel_to_ticket">' . _l('tickets') . '</label>
        </div>

        <div class="checkbox checkbox-inline mbot25">
        <input type="checkbox" value="expense" id="ts_rel_to_expense" name="tasks_related_to[]">
        <label for="ts_rel_to_expense">' . _l('expenses') . '</label>
        </div>

        <div class="checkbox checkbox-inline mbot25">
        <input type="checkbox" value="proposal" id="ts_rel_to_proposal" name="tasks_related_to[]">
        <label for="ts_rel_to_proposal">' . _l('proposals') . '</label>
        </div>';

        echo '</div>';
    }
    echo "<div class='clearfix'></div>";

    // If new column is added on tasks relations table this will not work fine
    // In this case we need to add new identifier eq task-relation
    $table_attributes['data-last-order-identifier'] = 'tasks';
    $table_attributes['data-default-order']         = get_table_last_order('tasks');

    $table .= render_datatable($table_data, $name, [], $table_attributes);

    return $table;
}

/**
 * Return tasks summary formated data
 * @param  string $where additional where to perform
 * @return array
 */
function tasks_summary_data($rel_id = null, $rel_type = null)
{
    $CI            = &get_instance();
    $tasks_summary = [];
    $statuses      = $CI->tasks_model->get_statuses();
    foreach ($statuses as $status) {
        $tasks_where = 'status = ' . $CI->db->escape_str($status['id']);
        if (!has_permission('tasks', '', 'view')) {
            $tasks_where .= ' ' . get_tasks_where_string();
        }
        $tasks_my_where = 'id IN(SELECT taskid FROM ' . db_prefix() . 'task_assigned WHERE staffid=' . get_staff_user_id() . ') AND status=' . $CI->db->escape_str($status['id']);
        if ($rel_id && $rel_type) {
            $tasks_where .= ' AND rel_id=' . $CI->db->escape_str($rel_id) . ' AND rel_type="' . $CI->db->escape_str($rel_type) . '"';
            $tasks_my_where .= ' AND rel_id=' . $CI->db->escape_str($rel_id) . ' AND rel_type="' . $CI->db->escape_str($rel_type) . '"';
        } else {
            $sqlProjectTasksWhere = ' AND CASE
            WHEN rel_type="project" AND rel_id IN (SELECT project_id FROM ' . db_prefix() . 'project_settings WHERE project_id=rel_id AND name="hide_tasks_on_main_tasks_table" AND value=1)
            THEN rel_type != "project"
            ELSE 1=1
            END';
            $tasks_where .= $sqlProjectTasksWhere;
            $tasks_my_where .= $sqlProjectTasksWhere;
        }

        $summary                   = [];
        $summary['total_tasks']    = total_rows(db_prefix() . 'tasks', $tasks_where);
        $summary['total_my_tasks'] = total_rows(db_prefix() . 'tasks', $tasks_my_where);
        $summary['color']          = $status['color'];
        $summary['name']           = $status['name'];
        $summary['status_id']      = $status['id'];
        $tasks_summary[]           = $summary;
    }

    return $tasks_summary;
}


function get_sql_calc_task_logged_time($task_id)
{
    /**
     * Do not remove where task_id=
     * Used in tasks detailed_overview to overwrite the taskid
     */
    return 'SELECT SUM(CASE
            WHEN end_time is NULL THEN ' . time() . '-start_time
            ELSE end_time-start_time
            END) as total_logged_time FROM ' . db_prefix() . 'taskstimers WHERE task_id =' . get_instance()->db->escape_str($task_id);
}

function get_sql_select_task_assignees_ids()
{
    return '(SELECT GROUP_CONCAT(staffid SEPARATOR ",") FROM ' . db_prefix() . 'task_assigned WHERE taskid=' . db_prefix() . 'tasks.id ORDER BY ' . db_prefix() . 'task_assigned.staffid)';
}

function get_sql_select_task_asignees_full_names()
{
    return '(SELECT GROUP_CONCAT(CONCAT(firstname, \' \', lastname) SEPARATOR ",") FROM ' . db_prefix() . 'task_assigned JOIN ' . db_prefix() . 'staff ON ' . db_prefix() . 'staff.staffid = ' . db_prefix() . 'task_assigned.staffid WHERE taskid=' . db_prefix() . 'tasks.id ORDER BY ' . db_prefix() . 'task_assigned.staffid)';
}

function get_sql_select_task_total_checklist_items()
{
    return '(SELECT COUNT(id) FROM ' . db_prefix() . 'task_checklist_items WHERE taskid=' . db_prefix() . 'tasks.id) as total_checklist_items';
}

function get_sql_select_task_total_finished_checklist_items()
{
    return '(SELECT COUNT(id) FROM ' . db_prefix() . 'task_checklist_items WHERE taskid=' . db_prefix() . 'tasks.id AND finished=1) as total_finished_checklist_items';
}

/**
 * This text is used in WHERE statements for tasks if the staff member don't have permission for tasks VIEW
 * This query will shown only tasks that are created from current user, public tasks or where this user is added is task follower.
 * Other statement will be included the tasks to be visible for this user only if Show All Tasks For Project Members is set to YES
 * @return string
 */
function get_tasks_where_string($table = true)
{
    $_tasks_where = '(' . db_prefix() . 'tasks.id IN (SELECT taskid FROM ' . db_prefix() . 'task_assigned WHERE staffid = ' . get_staff_user_id() . ') OR ' . db_prefix() . 'tasks.id IN (SELECT taskid FROM ' . db_prefix() . 'task_followers WHERE staffid = ' . get_staff_user_id() . ') OR (addedfrom=' . get_staff_user_id() . ' AND is_added_from_contact=0)';
    if (get_option('show_all_tasks_for_project_member') == 1) {
        $_tasks_where .= ' OR (' . db_prefix() . 'tasks.rel_type="project" AND ' . db_prefix() . 'tasks.rel_id IN (SELECT project_id FROM ' . db_prefix() . 'project_members WHERE staff_id=' . get_staff_user_id() . '))';
    }
    $_tasks_where .= ' OR is_public = 1)';
    if ($table == true) {
        $_tasks_where = 'AND ' . $_tasks_where;
    }

    return $_tasks_where;
}

/**
 * @since 2.7.1
 *
 * Task timer round off options
 *
 * @return array
 */
function get_task_timer_round_off_options()
{
    $options = [
        [
            'name' => _l('task_timer_dont_round_off'),
            'id'   => 0,
        ],
        [
            'name' => _l('task_timer_round_up'),
            'id'   => 1,
        ],
        [
            'name' => _l('task_timer_round_down'),
            'id'   => 2,
        ],
        [
            'name' => _l('task_timer_round_nearest'),
            'id'   => 3,
        ],
    ];

    return hooks()->apply_filters('before_get_task_timer_round_off_options', $options);
}

/**
 * @since  2.7.1
 *
 * Get the task timer round of available times
 *
 * @return array
 */
function get_task_timer_round_off_times()
{
    return hooks()->apply_filters('before_get_task_timer_round_off_times', [5, 10, 15, 20, 25, 30, 35, 40, 45]);
}

/**
 * Round the given logged seconds of a task
 *
 * @since 2.7.1
 *
 * @param  int $seconds
 *
 * @return int
 */
function task_timer_round($seconds)
{
    $roundMinutes = get_option('round_off_task_timer_time');
    $roundSeconds = $roundMinutes * 60;
    switch (get_option('round_off_task_timer_option')) {
        case 1: // up
        return ceil($seconds / $roundSeconds) * $roundSeconds;

        break;
        case 2: // down
        return floor($seconds / $roundSeconds) * $roundSeconds;

        break;
        case 3: // nearest
        return round($seconds / $roundSeconds) * $roundSeconds;

        break;
        default:
        return $seconds;

        break;
    }
}
