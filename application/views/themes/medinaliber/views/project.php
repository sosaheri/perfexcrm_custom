<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

			<?php get_template_part('external_urls');?>

<?php echo form_hidden('project_id',$project->id); ?>
<div class="panel_s section-heading section-project">
    <div class="panel-body">
       <h3 class="bold mtop10 project-name pull-left"><?php echo $project->name; ?>
            <span style="color:<?php echo $project_status['color']; ?>; font-size:16px;"><?php echo $project_status['name']; ?></span>
       </h3>
        <?php if($project->settings->view_tasks == 1 && $project->settings->create_tasks == 1){ ?>
        <a href="<?php echo site_url('clients/project/'.$project->id.'?group=new_task'); ?>" class="btn btn-info pull-right mtop5"><?php echo _l('new_task'); ?></a>
        <?php } ?>
   </div>
</div>
<div class="panel_s">
    <div class="panel-body">
        <?php get_template_part('projects/project_tabs'); ?>
        <div class="clearfix mtop15"></div>
        <?php get_template_part('projects/'.$group); ?>
    </div>
</div>
