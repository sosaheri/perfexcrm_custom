<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="row">
    <div class="col-md-12">
        <?php if (count($gantt_data) > 0) { ?>
            <div class="form-group pull-right">
                <select class="selectpicker" name="gantt_view">
                    <option value="Day"><?php echo _l('gantt_view_day'); ?></option>
                    <option value="Week"><?php echo _l('gantt_view_week'); ?></option>
                    <option value="Month" selected><?php echo _l('gantt_view_month'); ?></option>
                    <option value="Year"><?php echo _l('gantt_view_year'); ?></option>
                </select>
            </div>
        <?php } else { ?>
            <p><?php echo _l('no_tasks_found'); ?></p>
        <?php } ?>
    </div>
</div>
<svg id="gantt"></svg>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        var gantt_data = <?php echo json_encode($gantt_data); ?>;

        if (gantt_data.length > 0) {
            var gantt = new Gantt("#gantt", gantt_data, {
                view_modes: ['Day', 'Week', 'Month', 'Year'],
                view_mode: 'Month',
                date_format: 'YYYY-MM-DD',
                popup_trigger: 'click mouseover',
                on_click: function(data) {
                    if (typeof(data.task_id) != 'undefined') {
                        let projectViewUrl = '<?php echo admin_url('projects/view'); ?>';
                        let params = [];
                        params['group'] = 'project_tasks';
                        params['taskid'] = data.task_id;
                        window.location.href = buildUrl(site_url + 'clients/project/' + project_id, params);
                    }
                }
            });

            $("#gantt g.handle-group").hide();

            $('body').on('mouseleave', '.grid-row', function() {
                gantt.hide_popup();
            })

            $('select[name$="gantt_view"').change(function(el) {
                let view = $(el.target).val();
                gantt.change_view_mode(view);
            });
        }

    })
</script>
