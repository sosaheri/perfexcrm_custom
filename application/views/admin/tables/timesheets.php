<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'CONCAT(firstname, \' \', lastname) as staff',
    'task_id',
    '(SELECT GROUP_CONCAT(name SEPARATOR ",") FROM ' . db_prefix() . 'taggables JOIN ' . db_prefix() . 'tags ON ' . db_prefix() . 'taggables.tag_id = ' . db_prefix() . 'tags.id WHERE rel_id = ' . db_prefix() . 'taskstimers.id and rel_type="timesheet" ORDER by tag_order ASC) as tags',
    'start_time',
    'end_time',
    'note',
    'end_time - start_time',
    'end_time - start_time',
    ];
$sIndexColumn = 'id';
$sTable       = db_prefix() . 'taskstimers';

$aColumns = hooks()->apply_filters('projects_timesheets_table_sql_columns', $aColumns);

$join = [
    'JOIN ' . db_prefix() . 'tasks ON ' . db_prefix() . 'tasks.id = ' . db_prefix() . 'taskstimers.task_id',
    'JOIN ' . db_prefix() . 'staff ON ' . db_prefix() . 'staff.staffid = ' . db_prefix() . 'taskstimers.staff_id',
    ];

$join = hooks()->apply_filters('projects_timesheets_table_sql_join', $join);

$where = ['AND task_id IN (SELECT id FROM ' . db_prefix() . 'tasks WHERE rel_id="' . $this->ci->db->escape_str($project_id) . '" AND rel_type="project")'];

if (!has_permission('projects', '', 'create')) {
    array_push($where, 'AND ' . db_prefix() . 'taskstimers.staff_id=' . get_staff_user_id());
}

$staff_ids = $this->ci->projects_model->get_distinct_tasks_timesheets_staff($project_id);

$_staff_ids = [];

foreach ($staff_ids as $s) {
    if ($this->ci->input->post('staff_id_' . $s['staff_id'])) {
        array_push($_staff_ids, $s['staff_id']);
    }
}

if (count($_staff_ids) > 0) {
    array_push($where, 'AND ' . db_prefix() . 'taskstimers.staff_id IN (' . implode(', ', $_staff_ids) . ')');
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    db_prefix() . 'taskstimers.id',
    db_prefix() . 'tasks.name',
    'billed',
    'billable',
    db_prefix() . 'taskstimers.staff_id',
    'status',
    ]);
$output  = $result['output'];
$rResult = $result['rResult'];
foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0; $i < count($aColumns); $i++) {
        if (strpos($aColumns[$i], 'as') !== false && !isset($aRow[$aColumns[$i]])) {
            $_data = $aRow[strafter($aColumns[$i], 'as ')];
        } else {
            $_data = $aRow[$aColumns[$i]];
        }

        $user_removed_as_assignee = (total_rows(db_prefix() . 'task_assigned', ['staffid' => $aRow['staff_id'], 'taskid' => $aRow['task_id']]) == 0 ? true : false);

        // Staff full name
        if ($i == 0) {
            $_data = '<div class="mtop5">';
            $_data .= '<a href="' . admin_url('staff/profile/' . $aRow['staff_id']) . '"> ' . staff_profile_image($aRow['staff_id'], [
                'staff-profile-image-xs mright5',
                ]) . '</a>';
            if (has_permission('staff', '', 'edit')) {
                $_data .= ' <a href="' . admin_url('staff/member/' . $aRow['staff_id']) . '"> ' . $aRow['staff'] . '</a>';
            } else {
                $_data .= $aRow['staff'];
            }

            if ($user_removed_as_assignee == 1) {
                $_data .= '<span class="hidden"> - </span> <span class="mtop5 pull-right" data-toggle="tooltip" data-title="' . _l('project_activity_task_assignee_removed') . '"><i class="fa fa-exclamation-circle" aria-hidden="true"></i></span>';
            }
            $_data .= '</div>';
        } elseif ($aColumns[$i] == 'task_id') {
            $_data = '<a href="' . admin_url('tasks/view/' . $aRow['task_id']) . '" class="mtop5 inline-block" onclick="init_task_modal(' . $aRow['task_id'] . '); return false;">' . $aRow['name'] . '</a>';

            $_data .= '<div>';
            if ($aRow['billed'] == 1) {
                // hidden is for export
                $_data .= '<span class="hidden"> - </span><span class="label mtop5 label-success inline-block">' . _l('task_billed_yes') . '</span>';
            } elseif ($aRow['billable'] == 1 && $aRow['billed'] == 0) {
                $_data .= '<span class="hidden"> - </span> <span class="label mtop5 label-warning inline-block">' . _l('task_billed_no') . '</span>';
            }

            $status = get_task_status_by_id($aRow['status']);

            $_data .= '<span class="hidden"> - </span><span class="inline-block mtop5 mleft5 label" style="border:1px solid ' . $status['color'] . ';color:' . $status['color'] . '" task-status-table="' . $aRow['status'] . '">' . $status['name'] . '</span>';
            $_data .= '</div>';
        } elseif ($aColumns[$i] == 'start_time' || $aColumns[$i] == 'end_time') {
            if ($aColumns[$i] == 'end_time' && $_data == null) {
                $_data = '';
            } else {
                $_data = _dt($_data, true);
            }
        } elseif ($i == 2) {
            $_data = render_tags($_data);
        } else {
            if ($i == 6) {
                if ($_data == null) {
                    $_data = seconds_to_time_format(time() - $aRow['start_time']);
                } else {
                    $_data = seconds_to_time_format($_data);
                }
            } elseif ($i == 7) {
                if ($_data == null) {
                    $_data = sec2qty(time() - $aRow['start_time']);
                } else {
                    $_data = sec2qty($_data);
                }
            }
        }
        $row[] = $_data;
    }
    $task_is_billed = $this->ci->tasks_model->is_task_billed($aRow['task_id']);
    $options        = '';
    if (($aRow['staff_id'] == get_staff_user_id() || has_permission('projects', '', 'edit'))) {
        if (($aRow['staff_id'] == get_staff_user_id() || has_permission('projects', '', 'edit'))) {
            if ($aRow['end_time'] !== null) {
                $attrs = [
                'onclick'                 => 'edit_timesheet(this,' . $aRow['id'] . ');return false',
                'data-start_time'         => _dt($aRow['start_time'], true),
                'data-timesheet_task_id'  => $aRow['task_id'],
                'data-timesheet_staff_id' => $aRow['staff_id'],
                'data-tags'               => $aRow['tags'],
                'data-note'               => htmlspecialchars(clear_textarea_breaks($aRow['note']), ENT_COMPAT),
                ];

                $btn_icon_class = 'btn-default';
                if ($aRow['status'] == Tasks_model::STATUS_COMPLETE || $user_removed_as_assignee == true) {
                    $attrs['disabled'] = true;
                    $btn_icon_class .= ' disabled';
                }

                $attrs['data-end_time'] = _dt($aRow['end_time'], true);
                $icon_btn               = icon_btn('#', 'pencil-square-o', $btn_icon_class, $attrs);
                if ($aRow['status'] == Tasks_model::STATUS_COMPLETE) {
                    $icon_btn = '<span data-toggle="tooltip" data-title="' . _l('task_edit_delete_timesheet_notice', [($task_is_billed ? _l('task_billed') : _l('task_status_5')), _l('edit')]) . '">' . $icon_btn . '</span>';
                }
                $options .= $icon_btn;
            }
        }

        if (!$task_is_billed) {
            if ($aRow['end_time'] == null && ($aRow['staff_id'] == get_staff_user_id() || is_admin())) {
                $adminStop = $aRow['staff_id'] != get_staff_user_id() ? 1 : 0;

                $options .= ' <a href="#"
                    class="btn btn-danger btn-icon"
                    data-toggle="popover"
                    data-placement="bottom"
                    data-html="true"
                    data-trigger="manual"
                    data-title="' . _l('note') . "\"
                    data-content='" . render_textarea('timesheet_note') . '
                    <button type="button"
                    onclick="timer_action(this, ' . $aRow['task_id'] . ', ' . $aRow['id'] . ', ' . $adminStop . ');"
                    class="btn btn-info btn-xs">' . _l('save')
                    . "</button>'
                    class=\"text-danger\"
                    onclick=\"return false;\">
                    <span data-toggle=\"tooltip\" data-title='" . _l('timesheet_stop_timer') . "'>
                          <i class=\"fa fa-clock-o\"></i>
                          </span>
                    </a>'";
            }
        }

        if (has_permission('projects', '', 'delete') || has_permission('tasks', '', 'delete') || $aRow['staff_id'] == get_staff_user_id()) {
            $btn_icon_class = 'btn-danger _delete';
            $attrs          = [];

            if ($task_is_billed) {
                $btn_icon_class .= ' disabled';
                $attrs['disabled'] = true;
            }

            $icon_btn = icon_btn('tasks/delete_timesheet/' . $aRow['id'], 'remove', $btn_icon_class, $attrs);
            if ($task_is_billed) {
                $icon_btn = '<span data-toggle="tooltip" data-title="' . _l('task_edit_delete_timesheet_notice', [
                _l('task_billed'),
                _l('delete'), ]) . '">' . $icon_btn . '</span>';
            }

            $options .= $icon_btn;
        }
    }

    $row[]              = $options;
    $output['aaData'][] = $row;
}
