<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Get proposal short_url
 * @since  Version 2.7.3
 * @param  object $proposal
 * @return string Url
 */
function get_proposal_shortlink($proposal)
{
    $long_url = site_url("invoice/{$proposal->id}/{$proposal->hash}");
    if (!get_option('bitly_access_token')) {
        return $long_url;
    }

    // Check if proposal has short link, if yes return short link
    if (!empty($proposal->short_link)) {
        return $proposal->short_link;
    }

    // Create short link and return the newly created short link
    $short_link = app_generate_short_link([
        'long_url'  => $long_url,
        'title'     => format_proposal_number($proposal->id)
    ]);

    if ($short_link) {
        $CI = &get_instance();
        $CI->db->where('id', $proposal->id);
        $CI->db->update(db_prefix() . 'proposals', [
            'short_link' => $short_link
        ]);
        return $short_link;
    }
    return $long_url;
}

/**
 * Check if proposal hash is equal
 * @param  mixed $id   proposal id
 * @param  string $hash proposal hash
 * @return void
 */
function check_proposal_restrictions($id, $hash)
{
    $CI = &get_instance();
    $CI->load->model('proposals_model');
    if (!$hash || !$id) {
        show_404();
    }
    $proposal = $CI->proposals_model->get($id);
    if (!$proposal || ($proposal->hash != $hash)) {
        show_404();
    }
}

/**
 * Check if proposal email template for expiry reminders is enabled
 * @return boolean
 */
function is_proposals_email_expiry_reminder_enabled()
{
    return total_rows(db_prefix() . 'emailtemplates', ['slug' => 'proposal-expiry-reminder', 'active' => 1]) > 0;
}

/**
 * Check if there are sources for sending proposal expiry reminders
 * Will be either email or SMS
 * @return boolean
 */
function is_proposals_expiry_reminders_enabled()
{
    return is_proposals_email_expiry_reminder_enabled() || is_sms_trigger_active(SMS_TRIGGER_PROPOSAL_EXP_REMINDER);
}

/**
 * Return proposal status color class based on twitter bootstrap
 * @param  mixed  $id
 * @param  boolean $replace_default_by_muted
 * @return string
 */
function proposal_status_color_class($id, $replace_default_by_muted = false)
{
    if ($id == 1) {
        $class = 'default';
    } elseif ($id == 2) {
        $class = 'danger';
    } elseif ($id == 3) {
        $class = 'success';
    } elseif ($id == 4 || $id == 5) {
        // status sent and revised
        $class = 'info';
    } elseif ($id == 6) {
        $class = 'default';
    }
    if ($class == 'default') {
        if ($replace_default_by_muted == true) {
            $class = 'muted';
        }
    }

    return $class;
}
/**
 * Format proposal status with label or not
 * @param  mixed  $status  proposal status id
 * @param  string  $classes additional label classes
 * @param  boolean $label   to include the label or return just translated text
 * @return string
 */
function format_proposal_status($status, $classes = '', $label = true)
{
    $id = $status;
    if ($status == 1) {
        $status      = _l('proposal_status_open');
        $label_class = 'default';
    } elseif ($status == 2) {
        $status      = _l('proposal_status_declined');
        $label_class = 'danger';
    } elseif ($status == 3) {
        $status      = _l('proposal_status_accepted');
        $label_class = 'success';
    } elseif ($status == 4) {
        $status      = _l('proposal_status_sent');
        $label_class = 'info';
    } elseif ($status == 5) {
        $status      = _l('proposal_status_revised');
        $label_class = 'info';
    } elseif ($status == 6) {
        $status      = _l('proposal_status_draft');
        $label_class = 'default';
    }

    if ($label == true) {
        return '<span class="label label-' . $label_class . ' ' . $classes . ' s-status proposal-status-' . $id . '">' . $status . '</span>';
    }

    return $status;
}

/**
 * Function that format proposal number based on the prefix option and the proposal id
 * @param  mixed $id proposal id
 * @return string
 */
function format_proposal_number($id)
{
    return get_option('proposal_number_prefix') . str_pad($id, get_option('number_padding_prefixes'), '0', STR_PAD_LEFT);
}


/**
 * Function that return proposal item taxes based on passed item id
 * @param  mixed $itemid
 * @return array
 */
function get_proposal_item_taxes($itemid)
{
    $CI = &get_instance();
    $CI->db->where('itemid', $itemid);
    $CI->db->where('rel_type', 'proposal');
    $taxes = $CI->db->get(db_prefix() . 'item_tax')->result_array();
    $i     = 0;
    foreach ($taxes as $tax) {
        $taxes[$i]['taxname'] = $tax['taxname'] . '|' . $tax['taxrate'];
        $i++;
    }

    return $taxes;
}


/**
 * Calculate proposal percent by status
 * @param  mixed $status          proposal status
 * @param  mixed $total_estimates in case the total is calculated in other place
 * @return array
 */
function get_proposals_percent_by_status($status, $total_proposals = '')
{
    $has_permission_view                 = has_permission('proposals', '', 'view');
    $has_permission_view_own             = has_permission('proposals', '', 'view_own');
    $allow_staff_view_proposals_assigned = get_option('allow_staff_view_proposals_assigned');
    $staffId                             = get_staff_user_id();

    $whereUser = '';
    if (!$has_permission_view) {
        if ($has_permission_view_own) {
            $whereUser = '(addedfrom=' . $staffId;
            if ($allow_staff_view_proposals_assigned == 1) {
                $whereUser .= ' OR assigned=' . $staffId;
            }
            $whereUser .= ')';
        } else {
            $whereUser .= 'assigned=' . $staffId;
        }
    }

    if (!is_numeric($total_proposals)) {
        $total_proposals = total_rows(db_prefix() . 'proposals', $whereUser);
    }

    $data            = [];
    $total_by_status = 0;
    $where           = 'status=' . get_instance()->db->escape_str($status);
    if (!$has_permission_view) {
        $where .= ' AND (' . $whereUser . ')';
    }

    $total_by_status = total_rows(db_prefix() . 'proposals', $where);
    $percent         = ($total_proposals > 0 ? number_format(($total_by_status * 100) / $total_proposals, 2) : 0);

    $data['total_by_status'] = $total_by_status;
    $data['percent']         = $percent;
    $data['total']           = $total_proposals;

    return $data;
}

/**
 * Function that will search possible proposal templates in applicaion/views/admin/proposal/templates
 * Will return any found files and user will be able to add new template
 * @return array
 */
function get_proposal_templates()
{
    $proposal_templates = [];
    if (is_dir(VIEWPATH . 'admin/proposals/templates')) {
        foreach (list_files(VIEWPATH . 'admin/proposals/templates') as $template) {
            $proposal_templates[] = $template;
        }
    }

    return $proposal_templates;
}
/**
 * Check if staff member can view proposal
 * @param  mixed $id proposal id
 * @param  mixed $staff_id
 * @return boolean
 */
function user_can_view_proposal($id, $staff_id = false)
{
    $CI = &get_instance();

    $staff_id = $staff_id ? $staff_id : get_staff_user_id();

    if (has_permission('proposals', $staff_id, 'view')) {
        return true;
    }

    $CI->db->select('id, addedfrom, assigned');
    $CI->db->from(db_prefix() . 'proposals');
    $CI->db->where('id', $id);
    $proposal = $CI->db->get()->row();

    if ((has_permission('proposals', $staff_id, 'view_own') && $proposal->addedfrom == $staff_id)
        || ($proposal->assigned == $staff_id && get_option('allow_staff_view_proposals_assigned') == 1)
    ) {
        return true;
    }

    return false;
}
function parse_proposal_content_merge_fields($proposal)
{
    $id = is_array($proposal) ? $proposal['id'] : $proposal->id;
    $CI = &get_instance();

    $CI->load->library('merge_fields/proposals_merge_fields');
    $CI->load->library('merge_fields/other_merge_fields');

    $merge_fields = [];
    $merge_fields = array_merge($merge_fields, $CI->proposals_merge_fields->format($id));
    $merge_fields = array_merge($merge_fields, $CI->other_merge_fields->format());
    foreach ($merge_fields as $key => $val) {
        $content = is_array($proposal) ? $proposal['content'] : $proposal->content;

        if (stripos($content, $key) !== false) {
            if (is_array($proposal)) {
                $proposal['content'] = str_ireplace($key, $val, $content);
            } else {
                $proposal->content = str_ireplace($key, $val, $content);
            }
        } else {
            if (is_array($proposal)) {
                $proposal['content'] = str_ireplace($key, '', $content);
            } else {
                $proposal->content = str_ireplace($key, '', $content);
            }
        }
    }

    return $proposal;
}

/**
 * Check if staff member have assigned proposals / added as sale agent
 * @param  mixed $staff_id staff id to check
 * @return boolean
 */
function staff_has_assigned_proposals($staff_id = '')
{
    $CI       = &get_instance();
    $staff_id = is_numeric($staff_id) ? $staff_id : get_staff_user_id();
    $cache    = $CI->app_object_cache->get('staff-total-assigned-proposals-' . $staff_id);
    if (is_numeric($cache)) {
        $result = $cache;
    } else {
        $result = total_rows(db_prefix() . 'proposals', ['assigned' => $staff_id]);
        $CI->app_object_cache->add('staff-total-assigned-proposals-' . $staff_id, $result);
    }

    return $result > 0 ? true : false;
}

function get_proposals_sql_where_staff($staff_id)
{
    $has_permission_view_own            = has_permission('proposals', '', 'view_own');
    $allow_staff_view_invoices_assigned = get_option('allow_staff_view_proposals_assigned');
    $CI                                 = &get_instance();

    $whereUser = '';
    if ($has_permission_view_own) {
        $whereUser = '((' . db_prefix() . 'proposals.addedfrom=' . $CI->db->escape_str($staff_id) . ' AND ' . db_prefix() . 'proposals.addedfrom IN (SELECT staff_id FROM ' . db_prefix() . 'staff_permissions WHERE feature = "proposals" AND capability="view_own"))';
        if ($allow_staff_view_invoices_assigned == 1) {
            $whereUser .= ' OR assigned=' . $CI->db->escape_str($staff_id);
        }
        $whereUser .= ')';
    } else {
        $whereUser .= 'assigned=' . $CI->db->escape_str($staff_id);
    }

    return $whereUser;
}
