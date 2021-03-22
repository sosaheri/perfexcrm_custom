<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel_s section-heading section-contracts">
  <div class="panel-body">
    <h4 class="no-margin section-text"><?php echo _l('clients_contracts'); ?></h4>
  </div>
</div>
<div class="panel_s">
  <div class="panel-body">
    <div class="col-md-12">
      <h3 class="text-success contracts-summary-heading no-mtop mbot15"><?php echo _l('contract_summary_by_type'); ?></h3>
      <div class="relative" style="max-height:300px;">
        <canvas class="chart" height="300" id="contracts-by-type-chart"></canvas>
      </div>
    </div>
    <div class="clearfix"></div>
    <?php get_template_part('contracts_table'); ?>
 </div>
</div>
<script>
  var contracts_by_type = '<?php echo $contracts_by_type_chart; ?>';
</script>
