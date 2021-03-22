<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
	<div class="content">
		<div class="row">
			<div class="col-md-12">
				<div class="panel_s">
					<div class="panel-body" style="overflow-x: auto;">
						<div class="dt-loader hide"></div>
						<?php $this->load->view('admin/utilities/calendar_filters'); ?>
						<div id="calendar"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php $this->load->view('admin/utilities/calendar_template'); ?>
<?php hooks()->do_action('after_calendar_loaded');?>
<script>
	app.calendarIDs = '<?php echo json_encode($google_ids_calendars); ?>';
</script>
<?php init_tail(); ?>
<script>
	$(function(){
		if(get_url_param('eventid')) {
			view_event(get_url_param('eventid'));
		}
	});
</script>
</body>
</html>
