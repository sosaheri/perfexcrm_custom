<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="widget" id="widget-<?php echo create_widget_id(); ?>" data-name="<?php echo _l('projects_chart'); ?>">
  <div class="row">
    <div class="col-md-12">
     <div class="panel_s">
       <div class="panel-body padding-10">
        <div class="widget-dragger"></div>
        <p class="padding-5"><?php echo _l('home_stats_by_project_status'); ?></p>
        <hr class="hr-panel-heading-dashboard">
        <div class="relative" style="height:250px">
         <canvas class="chart" height="250" id="projects_status_stats"></canvas>
       </div>
     </div>
   </div>
 </div>
</div>
</div>
