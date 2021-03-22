<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="row">
	<div class="col-md-12 section-client-dashboard">

			<?php get_template_part('external_urls');?>


			<h3 id="customers" class="no-mtop"><?php echo _l('projects_summary'); ?></h3>
	
		<?php if(has_contact_permission('projects')) { ?>
			<div class="panel_s">
				<div class="panel-body">
					<div class="row">
						<?php get_template_part('projects/project_summary'); ?>
					</div>
				</div>
			</div>
		<?php } ?>



		
			

			<h3 id="customers" class="no-mtop"><?php echo _l('my_royalties'); ?></h3>



		<?php hooks()->do_action('client_area_after_project_overview'); ?>
		<div class="panel_s">
			<div class="panel-body">
				
			</div>
		</div>
	</div>

