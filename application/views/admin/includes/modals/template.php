<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="TemplateModal">
    <div class="modal-dialog modal-xxl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?php echo $title; ?></h4>
            </div>
            <?php
            if (!isset($template)) {
                echo form_open('admin/templates/template', array('id' => 'template-form'));
            } else {
                echo form_open('admin/templates/template/' . $id, array('id' => 'template-form'));
            }
            ?>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-12">
                        <?php
                        echo form_hidden('rel_type', $rel_type);

                        // so when modal is submitted, it returns to the proposal/contract that was being edited.
                        echo form_hidden('rel_id', $rel_id);
                        $name = isset($template) ? $template->name : '';
                        echo render_input('name', 'template_name', $name);

                        $content = isset($template) ? $template->content : '';
                        echo render_textarea('content', 'template_content', $content);
                        ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default close_btn" data-dismiss="modal"><?php echo _l('close'); ?></button>
                    <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
                </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>