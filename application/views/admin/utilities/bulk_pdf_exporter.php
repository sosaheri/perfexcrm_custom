<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo $title; ?></h4>
                        <hr class="hr-panel-heading" />
                        <?php echo form_open($this->uri->uri_string()); ?>
                        <div class="form-group select-placeholder">
                            <label for="export_type"><?php echo _l('bulk_pdf_export_select_type'); ?></label>
                            <select name="export_type" id="export_type" class="selectpicker" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                <option value=""></option>
                                <?php foreach($bulk_pdf_export_available_features as $feature){ ?>
                                    <option value="<?php echo $feature['feature']; ?>"><?php echo $feature['name']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <?php echo render_date_input('date-from','zip_from_date'); ?>
                        <?php echo render_date_input('date-to','zip_to_date'); ?>
                        <?php echo render_input('tag','bulk_export_include_tag','','text',array('data-toggle'=>'tooltip','title'=>'bulk_export_include_tag_help')); ?>
                        <div class="form-group hide shifter estimates_shifter">
                            <label for="estimate_zip_status"><?php echo _l('bulk_export_status'); ?></label>
                            <div class="radio radio-primary">
                                <input type="radio" value="all" checked name="estimates_export_status">
                                <label for="all"><?php echo _l('bulk_export_status_all'); ?></label>
                            </div>
                            <?php foreach($estimate_statuses as $status){ ?>
                                <div class="radio radio-primary">
                                    <input type="radio" id="<?php echo format_estimate_status($status,'',false); ?>" value="<?php echo $status; ?>" name="estimates_export_status">
                                    <label for="<?php echo format_estimate_status($status,'',false); ?>"><?php echo format_estimate_status($status,'',false); ?></label>
                                </div>
                            <?php } ?>
                        </div>

                        <div class="form-group hide shifter credit_notes_shifter">
                            <label for="credit_notes_export_status"><?php echo _l('bulk_export_status'); ?></label>
                            <div class="radio radio-primary">
                                <input type="radio" id="all" value="all" checked name="credit_notes_export_status">
                                <label for="all"><?php echo _l('bulk_export_status_all'); ?></label>
                            </div>
                            <?php foreach($credit_notes_statuses as $status){ ?>
                                <div class="radio radio-primary">
                                    <input type="radio" id="credit_note_<?php echo $status['id']; ?>" value="<?php echo $status['id']; ?>" name="credit_notes_export_status">
                                    <label for="credit_note_<?php echo $status['id']; ?>"><?php echo $status['name']; ?></label>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="form-group hide shifter invoices_shifter">
                            <label for="invoices_export_status"><?php echo _l('bulk_export_status'); ?></label>
                            <div class="radio radio-primary">
                                <input type="radio" id="all" value="all" checked name="invoices_export_status">
                                <label for="all"><?php echo _l('bulk_export_status_all'); ?></label>
                            </div>
                            <?php foreach($invoice_statuses as $status){ ?>
                                <div class="radio radio-primary">
                                    <input type="radio" id="invoice_<?php echo format_invoice_status($status,'',false); ?>" value="<?php echo $status; ?>" name="invoices_export_status">
                                    <label for="invoice_<?php echo format_invoice_status($status,'',false); ?>"><?php echo format_invoice_status($status,'',false); ?></label>
                                </div>
                            <?php } ?>
                            <hr />
                            <div class="radio radio-primary">
                                <input type="radio" id="invoice_not_send" value="not_send" name="invoices_export_status">
                                <label for="invoice_not_send"><?php echo _l('not_sent_indicator'); ?></label>
                            </div>
                        </div>
                        <div class="form-group hide shifter proposals_shifter">
                            <label for="proposals_export_status"><?php echo _l('bulk_export_status'); ?></label>
                            <div class="radio radio-primary">
                                <input type="radio" value="all" checked name="proposals_export_status">
                                <label for="all"><?php echo _l('bulk_export_status_all'); ?></label>
                            </div>
                            <?php foreach($proposal_statuses as $status){
                                if($status == 0){continue;}
                                ?>
                                <div class="radio radio-primary">
                                    <input type="radio" value="<?php echo $status; ?>" name="proposals_export_status" id="proposal_<?php echo format_proposal_status($status,'',false); ?>">
                                    <label for="proposal_<?php echo format_proposal_status($status,'',false); ?>"><?php echo format_proposal_status($status,'',false); ?></label>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="form-group hide shifter payments_shifter">
                            <?php
                            array_unshift($payment_modes,array('id'=>'','name'=>_l('bulk_export_status_all')));
                            echo render_select('paymentmode',$payment_modes,array('id','name'),'bulk_export_zip_payment_modes');
                            ?>
                        </div>
                        <?php hooks()->do_action('after_bulk_pdf_export_options'); ?>
                        <button class="btn btn-info" type="submit"><?php echo _l('bulk_pdf_export_button'); ?></button>
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function(){
        appValidateForm($('form'),{export_type:'required'});
        $('#export_type').on('change',function(){
            var val = $(this).val();
            $('.shifter').addClass('hide');
            $('.'+val+'_shifter').removeClass('hide');
        });
    });
</script>
</body>
</html>
