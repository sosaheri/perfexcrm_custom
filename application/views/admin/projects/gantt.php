<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
	<div class="content">
		<div class="row">
			<div class="panel_s">
				<div class="panel-body">
					<div class="row">
						<div class="col-md-12">
							<h4><?php echo _l('filter_by'); ?></h4>
						</div>
						<?php echo form_open(admin_url('projects/gantt'), ['method' => 'get', 'id' => 'ganttFiltersForm']); ?>
						<div class="col-md-4">
							<select class="selectpicker" data-none-selected-text="<?php echo _l('all'); ?>" name="status[]" data-width="100%" multiple="true">
								<?php foreach ($statuses as $status) {
									$statusSelected = in_array($status['id'], $selected_statuses); ?>
									<option value="<?php echo $status['id']; ?>" <?php if ($statusSelected) {
																						echo ' selected';
																					} ?>>
										<?php echo $status['name']; ?>
									</option>
								<?php } ?>
							</select>
						</div>
						<?php
						/**
						 * Only show this filter if user has permission for projects view otherwise
						 * wont need this becuase by default this filter will be applied
						 */
						if (has_permission('projects', '', 'view')) {
						?>
							<div class="col-md-3">
								<select class="selectpicker" data-live-search="true" data-title="<?php echo _l('project_member'); ?>" name="member" data-width="100%">
									<option value=""></option>
									<?php foreach ($project_members as $member) { ?>
										<option value="<?php echo $member['staff_id']; ?>" <?php if ($selectedMember == $member['staff_id']) {
																								echo ' selected';
																							} ?>>
											<?php echo $member['firstname'] . ' ' . $member['lastname']; ?>
										</option>
									<?php } ?>
									</option>
								</select>
							</div>
						<?php } ?>
						<div class="col-md-1">
							<button type="submit" class="btn btn-default"><?php echo _l('apply'); ?></button>
						</div>
						<?php echo form_close(); ?>
						<div class="clearfix mbot10"></div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<?php if (count($gantt_data) > 0) { ?>
								<div class="form-group">
									<select class="selectpicker" name="gantt_view">
										<option value="Day"><?php echo _l('gantt_view_day'); ?></option>
										<option value="Week"><?php echo _l('gantt_view_week'); ?></option>
										<option value="Month" selected><?php echo _l('gantt_view_month'); ?></option>
										<option value="Year"><?php echo _l('gantt_view_year'); ?></option>
									</select>
								</div>
								<div id="gantt"></div>
							<?php } else { ?>
								<p><?php echo _l('no_tasks_found'); ?></p>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php init_tail(); ?>
	<script>
		var gantt_data = <?php echo json_encode($gantt_data); ?>;

		if (gantt_data.length > 0) {
			var gantt = new Gantt("#gantt", gantt_data, {
				view_modes: ['Day', 'Week', 'Month', 'Year'],
				view_mode: 'Month',
				date_format: 'YYYY-MM-DD',
				popup_trigger: 'click mouseover',
				on_date_change: function(data, start, end) {
					if (typeof(data.task_id) != 'undefined') {
						$.post(admin_url + 'tasks/gantt_date_update/' + data.task_id, {
							startdate: moment(start).format('YYYY-MM-DD'),
							duedate: moment(end).format('YYYY-MM-DD'),
						});
					}
				},
				on_click: function(data) {
					if (typeof(data.project_id) != 'undefined') {
						var projectViewUrl = '<?php echo admin_url('projects/view'); ?>';
						window.location.href = projectViewUrl + '/' + data.project_id;
					} else if (typeof(data.task_id) != 'undefined') {
						init_task_modal(data.task_id);
					}
				}
			});

		    $('body').on('mouseleave', '.grid-row', function() {
		        gantt.hide_popup();
		    })

			$('select[name$="gantt_view"').change(function(el) {
				let view = $(el.target).val();
				gantt.change_view_mode(view);
			})
		}
	</script>
	</body>
	</html>
