<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
             <div class="panel_s">
                 <div class="panel-body">
                    <div class="_buttons">
                        <a href="<?php echo admin_url('custom_fields/field'); ?>" class="btn btn-info pull-left display-block"><?php echo _l('new_custom_field'); ?></a>
                    </div>
                    <div class="clearfix"></div>
                    <hr class="hr-panel-heading" />
                    <div class="clearfix"></div>
                    <?php render_datatable(
                        array(
                            _l('id'),
                            _l('custom_field_dt_field_name'),
                            _l('custom_field_dt_field_to'),
                            _l('custom_field_dt_field_type'),
                            _l('kb_article_slug'),
                            _l('custom_field_add_edit_active'),
                            ),'custom-fields'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php init_tail(); ?>
    <script>
        $(function(){
            initDataTable('.table-custom-fields', window.location.href);
        });
    </script>
</body>
</html>
