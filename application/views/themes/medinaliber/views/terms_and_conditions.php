<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php get_template_part('external_urls');?>

<div class="panel_s">
    <div class="panel-body">
        <h4 class="terms-and-conditions-heading"><?php echo _l('terms_and_conditions'); ?></h4>
        <hr />
        <div class="tc-content terms-and-conditions-content">
            <?php echo $terms; ?>
        </div>
    </div>
</div>
