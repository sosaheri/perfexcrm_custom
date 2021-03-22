<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

			<?php get_template_part('external_urls');?>


			<h3 id="customers" class="no-mtop"><?php echo _l('clients_my_estimates'); ?></h3>


<div class="panel_s">
    <div class="panel-body">
        <?php get_template_part('estimates_stats'); ?>
        <hr />
        <?php get_template_part('estimates_table'); ?>
    </div>
</div>
