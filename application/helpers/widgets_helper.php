<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Admin dashboard widgets
 * We are registering all widgets here
 * Also action hook is included to add new widgets if needed in my_functions_helper.php
 * @return array
 */
function get_dashboard_widgets()
{
    $widgets = [
        [
            'path'      => 'admin/dashboard/widgets/top_stats',
            'container' => 'top-12',
        ],
        [
            'path'      => 'admin/dashboard/widgets/finance_overview',
            'container' => 'left-8',
        ],
        [
            'path'      => 'admin/dashboard/widgets/user_data',
            'container' => 'left-8',
        ],
        [
            'path'      => 'admin/dashboard/widgets/upcoming_events',
            'container' => 'left-8',
        ],
        [
            'path'      => 'admin/dashboard/widgets/calendar',
            'container' => 'left-8',
        ],
        [
            'path'      => 'admin/dashboard/widgets/weekly_payments_chart',
            'container' => 'left-8',
        ],
        [
            'path'      => 'admin/dashboard/widgets/todos',
            'container' => 'right-4',
        ],
        [
            'path'      => 'admin/dashboard/widgets/leads_chart',
            'container' => 'right-4',
        ],
        [
            'path'      => 'admin/dashboard/widgets/projects_chart',
            'container' => 'right-4',
        ],
        [
            'path'      => 'admin/dashboard/widgets/tickets_chart',
            'container' => 'right-4',
        ],
        [
            'path'      => 'admin/dashboard/widgets/projects_activity',
            'container' => 'right-4',
        ],
        [
            'path'      => 'admin/dashboard/widgets/contracts_expiring',
            'container' => 'left-8',
        ],
    ];

    return hooks()->apply_filters('get_dashboard_widgets', $widgets);
}

/**
 * Render widgets based on container
 * The function will check if staff have re-organized the dashboard and apply any order which is needed.
 * @param  string $container
 * @return mixed
 */
function render_dashboard_widgets($container)
{
    $widgetsHtml = [];

    static $widgets     = null;
    static $widgetsData = null;

    include_once(APPPATH . 'third_party/simple_html_dom.php');

    $CI = &get_instance();

    if (!$widgets) {
        $widgetsData       = [];
        $widgetsContainers = [];
        $widgets           = get_dashboard_widgets();

        foreach ($widgets as $key => $widget) {
            $html = str_get_html($CI->load->view($widget['path'], [], true));
            if ($html) {
                $widgetContainer = $html->firstChild();
                if ($widgetContainer) {
                    $htmlID = $widgetContainer->getAttribute('id');

                    $widgetsData[$htmlID] = [
                    'widgetIndex'     => $key,
                    'widgetPath'      => $widget['path'],
                    'widgetContainer' => $widget['container'],
                    'html'            => $widgetContainer,
                ];

                    $widget['widgetID']         = $htmlID;
                    $widget['html']             = $widgetContainer;
                    $widgets[$key]['settingID'] = strafter($htmlID, 'widget-');
                    $widgets[$key]['html']      = $widgetContainer;
                } else {
                    // Not compatible widget
                    unset($widgets[$key]);
                }
            } else {
                // Not compatible widget
                unset($widgets[$key]);
            }
        }
    }

    $staff_dashboard = get_staff_meta(get_staff_user_id(), 'dashboard_widgets_order');
    $staff_dashboard = !$staff_dashboard ? [] : unserialize($staff_dashboard);

    if (count($staff_dashboard) == 0) {
        // Default widgets order and containers
        foreach ($widgets as $widget) {
            if ($widget['container'] == $container) {
                $widgetsHtml[$widget['settingID']] = $widget['html'];
            }
        }
    } else {
        $widgetsOutputted = [];
        if (isset($staff_dashboard[$container])) {
            foreach ($staff_dashboard[$container] as $widget) {
                if (isset($widgetsData[$widget])) {
                    array_push($widgetsOutputted, $widget);
                    $widgetsHtml[$widget] = $widgetsData[$widget]['html'];
                }
            }
        }

        foreach ($widgetsData as $wID => $widget) {
            // Widget exists but not applied in any staff container settings
            $applied = [];

            foreach ($staff_dashboard as $c => $w) {
                if (in_array($wID, $w)) {
                    array_push($applied, $wID);
                }
            }

            if ($widget['widgetContainer'] == $container && !in_array($wID, $applied)) {
                array_push($widgetsOutputted, $wID);
                $widgetsHtml[$wID] = $widget['html'];
            }
        }
    }

    $visibility = get_staff_meta(get_staff_user_id(), 'dashboard_widgets_visibility');
    $visibility = !$visibility ? [] : unserialize($visibility);
    foreach ($widgetsHtml as $widgetID => $widgetHTML) {
        foreach ($visibility as $option) {
            if ($option['id'] == strafter($widgetID, 'widget-') && $option['visible'] == 0) {
                if (strpos($widgetHTML->class, 'hide') !== true && !empty((string) $widgetHTML)) {
                    $widgetHTML->class .= ' hide';
                }
            }
        }

        echo $widgetHTML;
    }
}

/**
 * Create widget ID from the given widget file
 *
 * @param  string|null $id
 *
 * @return string
 */
function create_widget_id($id = null)
{
    $id = basename($id ? $id : debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[0]['file'], '.php');

    if (startsWith($id, 'my_')) {
        $id = strafter($id, 'my_');
    }

    return $id;
}
