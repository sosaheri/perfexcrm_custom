<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="col-md-12 no-padding animated fadeIn">
    <div class="panel_s">
        <?php echo form_open($formUrl, array('id'=>'schedule_send_form')); ?>
        <div class="panel-body">
            <h4 class="no-margin"><?php echo _l('schedule_email_for', format_invoice_number($invoice->id)); ?></h4>
            <hr class="hr-panel-heading" />
            <div class="row">
                <div class="col-md-12">
                    <div class="checkbox checkbox-primary">
                        <input type="checkbox"
                        name="attach_pdf"
                        id="attach_pdf"
                        <?php echo (isset($schedule) && $schedule->attach_pdf || !isset($schedule) ? ' checked' : ''); ?>>
                        <label for="attach_pdf"><?php echo _l('invoice_send_to_client_attach_pdf'); ?></label>
                    </div>
                    <hr />
                    <div class="form-group">
                        <?php
                        if($template_disabled){
                            echo '<div class="alert alert-danger">';
                            echo 'The email template <b><a href="'.admin_url('emails/email_template/'.$template_id).'" target="_blank">'.$template_system_name.'</a></b> is disabled. Click <a href="'.admin_url('emails/email_template/'.$template_id).'" target="_blank">here</a> to enable the email template in order to be sent successfully.';
                            echo '</div>';
                        }
                        $selected = [];
                        $contacts = $this->clients_model->get_contacts($invoice->clientid,array('active'=>1, 'invoice_emails'=>1));

                        if(!isset($schedule)){
                            foreach($contacts as $contact){
                                array_push($selected,$contact['id']);
                            }
                        } else {
                            $selected = explode(',', $schedule->contacts);
                        }

                        if(count($selected) == 0){
                            echo '<p class="text-danger">' . _l('sending_email_contact_permissions_warning',_l('customer_permission_invoice')) . '</p><hr />';
                        }

                        echo render_select('sent_to[]', $contacts, array('id','email','firstname,lastname'), 'invoice_estimate_sent_to_email', $selected, array('multiple'=>true), [], '', '', false);
                        ?>
                    </div>
                    <?php echo render_input('cc','CC', isset($schedule) ? $schedule->cc : ''); ?>
                    <?php echo render_datetime_input(
                        'scheduled_at',
                        'schedule_date',
                        _d($date)
                    ); ?>
                </div>
            </div>
            <div class="pull-right mtop15">
                <a href="#" class="btn btn-danger" onclick="init_invoice(<?php echo $invoice->id; ?>); return false;"><?php echo _l('cancel'); ?></a>
                <button type="submit" autocomplete="off" data-loading-text="<?php echo _l('wait_text'); ?>" data-form="#schedule_send_form" class="btn btn-success"><?php echo _l('schedule'); ?></button>
            </div>
        </div>
        <?php echo form_close(); ?>
    </div>
</div>
<script>
   $(function(){
       init_selectpicker();
       init_datepicker();
       appValidateForm($('#schedule_send_form'),{'sent_to[]': 'required', scheduled_at: 'required'});
   });
</script>
