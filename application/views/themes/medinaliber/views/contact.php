<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel_s section-heading section-contact">
   <div class="panel-body">
      <h4 class="no-margin section-text pull-left"><?php echo _l('clients_my_contact'); ?></h4>
      <a href="<?php echo site_url('contacts'); ?>" class="btn btn-info pull-right mtop5">
        <?php echo _l('clients_my_contacts'); ?>
      </a>
   </div>
</div>
<div class="clear-fix"></div>
<div class="panel_s">
   <div class="panel-body">
      <?php
         echo form_open_multipart(
             site_url('contacts/contact/' . (isset($my_contact) ? $my_contact->id : '')),
             ['id' => 'contact-form']
         );
         ?>
      <div class="row">
         <div class="col-md-12">
            <?php if (isset($my_contact)) { ?>
            <img src="<?php echo contact_profile_image_url($my_contact->id, 'thumb'); ?>" id="contact-img" class="client-profile-image-thumb">
            <?php if (!empty($my_contact->profile_image)) { ?>
            <a href="#" onclick="delete_contact_profile_image(<?php echo $my_contact->id; ?>); return false;" class="text-danger pull-right" id="contact-remove-img"><i class="fa fa-remove"></i></a>
            <?php } ?>
            <hr />
            <?php } ?>
            <div id="contact-profile-image"
               class="form-group<?php echo isset($my_contact) && !empty($my_contact->profile_image) ? ' hide' : ''; ?>">
               <label for="profile_image" class="profile-image"><?php echo _l('client_profile_image'); ?></label>
               <input type="file" name="profile_image" class="form-control" id="profile_image">
            </div>
            <div class="row">
               <div class="col-md-6">
                  <?php $value = (isset($my_contact) ? $my_contact->firstname : ''); ?>
                  <?php echo render_input('firstname', 'client_firstname', $value); ?>
                  <?php echo form_error('firstname'); ?>
               </div>
               <div class="col-md-6">
                  <?php $value = (isset($my_contact) ? $my_contact->lastname : ''); ?>
                  <?php echo render_input('lastname', 'client_lastname', $value); ?>
                  <?php echo form_error('lastname'); ?>
               </div>
               <div class="col-md-6">
                  <?php $value = (isset($my_contact) ? $my_contact->email : ''); ?>
                  <?php echo render_input('email', 'client_email', $value, 'email'); ?>
                  <?php echo form_error('email'); ?>
               </div>
               <div class="col-md-6">
                  <?php $value = (isset($my_contact) ? $my_contact->phonenumber : ''); ?>
                  <?php echo render_input('phonenumber', 'client_phonenumber', $value, 'text', array('autocomplete' => 'off')); ?>
                  <?php echo form_error('phonenumber'); ?>
               </div>
               <div class="col-md-6">
                  <?php $value = (isset($my_contact) ? $my_contact->title : ''); ?>
                  <?php echo render_input('title', 'contact_position', $value); ?>
               </div>
               <div class="col-md-6">
                  <div class="form-group contact-direction-option">
                     <label for="direction"><?php echo _l('document_direction'); ?></label>
                     <select class="selectpicker"
                        data-none-selected-text="<?php echo _l('system_default_string'); ?>"
                        data-width="100%"
                        name="direction"
                        id="direction">
                        <option value="" <?php if (isset($my_contact) && empty($my_contact->direction)) {
                           echo 'selected';
                           } ?>></option>
                        <option value="ltr" <?php if (isset($my_contact) && $my_contact->direction == 'ltr') {
                           echo 'selected';
                           } ?>>LTR</option>
                        <option value="rtl" <?php if (isset($my_contact) && $my_contact->direction == 'rtl') {
                           echo 'selected';
                           } ?>>RTL</option>
                     </select>
                  </div>
               </div>
            </div>
            <?php $rel_id = (isset($my_contact) ? $my_contact->id : false); ?>
            <?php echo render_custom_fields('contacts', $rel_id, array('show_on_client_portal' => 1)); ?>
            <div class="client_password_set_wrapper form-group">
               <label for="password" class="control-label">
               <?php echo _l('client_password'); ?>
               </label>
               <div class="input-group">
                  <input type="password" class="form-control password" name="password" autocomplete="false">
                  <span class="input-group-addon">
                  <a href="#password" class="show_password" onclick="showPassword('password'); return false;"><i class="fa fa-eye"></i></a>
                  </span>
                  <span class="input-group-addon">
                  <a href="#" class="generate_password" onclick="generatePassword(this);return false;"><i class="fa fa-refresh"></i></a>
                  </span>
                  <?php echo form_error('password'); ?>
               </div>
            </div>
            <?php if (isset($my_contact)) { ?>
            <p class="text-muted">
               <?php echo _l('client_password_change_populate_note'); ?>
            </p>
            <?php if ($my_contact->last_password_change != NULL) {
               echo _l('client_password_last_changed');
               echo '<span class="text-has-action" data-toggle="tooltip" data-title="' . _dt($my_contact->last_password_change) . '"> ' . time_ago($my_contact->last_password_change) . '</span>';
               }
               } ?>
         </div>
         <hr />
         <div class="col-md-12">
            <?php if (!isset($my_contact) && is_email_template_active('new-client-created')) { ?>
            <div class="checkbox checkbox-primary">
               <input type="checkbox" name="donotsendwelcomeemail" id="donotsendwelcomeemail">
               <label for="donotsendwelcomeemail">
               <?php echo _l('client_do_not_send_welcome_email'); ?>
               </label>
            </div>
            <?php } ?>
            <?php if (is_email_template_active('contact-set-password')) { ?>
            <div class="checkbox checkbox-primary">
               <input type="checkbox" name="send_set_password_email" id="send_set_password_email">
               <label for="send_set_password_email">
               <?php echo _l('client_send_set_password_email'); ?>
               </label>
            </div>
            <?php } ?>
         </div>
         <hr />
         <div class="col-md-12">
            <p class="bold mtop15"><?php echo _l('email_notifications'); ?>
               <?php if (is_sms_trigger_active()) {
                  echo '/SMS';
                  } ?>
            </p>
            <div id="contact_email_notifications">
               <div class="row">
                  <div class="col-md-3 mtop10 border-right">
                     <span><?php echo _l('invoice'); ?></span>
                  </div>
                  <div class="col-md-9 mtop10">
                     <div class="onoffswitch">
                        <input type="checkbox" id="invoice_emails" data-perm-id="1" class="onoffswitch-checkbox" <?php echo set_checkbox('invoice_emails', $this->input->post() ? $this->input->post('invoice_emails') : (isset($my_contact) && $my_contact->invoice_emails == '1' ? 'invoice_emails' : ''), (isset($my_contact) && $my_contact->invoice_emails == '1')); ?> value="invoice_emails" name="invoice_emails">
                        <label class="onoffswitch-label" for="invoice_emails"></label>
                     </div>
                  </div>
               </div>
               <div class="row">
                  <div class="col-md-3 mtop10 border-right">
                     <span><?php echo _l('estimate'); ?></span>
                  </div>
                  <div class="col-md-9 mtop10">
                     <div class="onoffswitch">
                        <input type="checkbox" id="estimate_emails" data-perm-id="2" class="onoffswitch-checkbox" <?php echo set_checkbox('estimate_emails', $this->input->post() ? $this->input->post('estimate_emails') : (isset($my_contact) && $my_contact->estimate_emails == '1' ? 'estimate_emails' : ''), (isset($my_contact) && $my_contact->estimate_emails == '1')); ?> value="estimate_emails" name="estimate_emails">
                        <label class="onoffswitch-label" for="estimate_emails"></label>
                     </div>
                  </div>
               </div>
               <div class="row">
                  <div class="col-md-3 mtop10 border-right">
                     <span><?php echo _l('credit_note'); ?></span>
                  </div>
                  <div class="col-md-9 mtop10">
                     <div class="onoffswitch">
                        <input type="checkbox" id="credit_note_emails" data-perm-id="1" class="onoffswitch-checkbox" <?php echo set_checkbox('credit_note_emails', $this->input->post() ? $this->input->post('credit_note_emails') : (isset($my_contact) && $my_contact->credit_note_emails == '1' ? 'credit_note_emails' : ''), (isset($my_contact) && $my_contact->credit_note_emails == '1')); ?> value="credit_note_emails" name="credit_note_emails">
                        <label class="onoffswitch-label" for="credit_note_emails"></label>
                     </div>
                  </div>
               </div>
               <div class="row">
                  <div class="col-md-3 mtop10 border-right">
                     <span><?php echo _l('project'); ?></span>
                  </div>
                  <div class="col-md-9 mtop10">
                     <div class="onoffswitch">
                        <input type="checkbox" id="project_emails" data-perm-id="6" class="onoffswitch-checkbox" <?php echo set_checkbox('project_emails', $this->input->post() ? $this->input->post('project_emails') : (isset($my_contact) && $my_contact->project_emails == '1' ? 'project_emails' : ''), (isset($my_contact) && $my_contact->project_emails == '1')); ?> value="project_emails" name="project_emails">
                        <label class="onoffswitch-label" for="project_emails"></label>
                     </div>
                  </div>
               </div>
               <div class="row">
                  <div class="col-md-3 mtop10 border-right">
                     <span><?php echo _l('tickets'); ?></span>
                  </div>
                  <div class="col-md-9 mtop10">
                     <div class="onoffswitch">
                        <input type="checkbox" id="ticket_emails" data-perm-id="5" class="onoffswitch-checkbox" <?php echo set_checkbox('ticket_emails', $this->input->post() ? $this->input->post('ticket_emails') : (isset($my_contact) && $my_contact->ticket_emails == '1' ? 'ticket_emails' : 'ticket_emails'), (isset($my_contact) && $my_contact->ticket_emails == '1')); ?> value="ticket_emails" name="ticket_emails">
                        <label class="onoffswitch-label" for="ticket_emails"></label>
                     </div>
                  </div>
                  <div class="col-md-3 mtop10 border-right">
                     <span> <?php echo _l('task'); ?></span>
                  </div>
                  <div class="col-md-9 mtop10">
                     <div class="onoffswitch">
                        <input type="checkbox" id="task_emails" data-perm-id="6" class="onoffswitch-checkbox" <?php echo set_checkbox('task_emails', $this->input->post() ? $this->input->post('task_emails') : (isset($my_contact) && $my_contact->task_emails == '1' ? 'task_emails' : ''), (isset($my_contact) && $my_contact->task_emails == '1')); ?> value="task_emails" name="task_emails">
                        <label class="onoffswitch-label" for="task_emails"></label>
                     </div>
                  </div>
               </div>
               <div class="row">
                  <div class="col-md-3 mtop10 border-right">
                     <span><?php echo _l('contract'); ?></span>
                  </div>
                  <div class="col-md-9 mtop10">
                     <div class="onoffswitch">
                        <input type="checkbox" id="contract_emails" data-perm-id="3" class="onoffswitch-checkbox" <?php echo set_checkbox('contract_emails', $this->input->post() ? $this->input->post('contract_emails') : (isset($my_contact) && $my_contact->contract_emails == '1' ? 'contract_emails' : ''), (isset($my_contact) && $my_contact->contract_emails == '1')); ?> value="contract_emails" name="contract_emails">
                        <label class="onoffswitch-label" for="contract_emails"></label>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <hr />
      <div class="text-right">
          <button type="submit"
          class="btn btn-info"
          autocomplete="off" >
          <?php echo _l('submit'); ?>
      </button>
   </div>
   <?php echo form_close(); ?>
   </div>
</div>
</div>
<script>
   $('#send_set_password_email').click(function() {
      $('.client_password_set_wrapper').toggle();
      $('.password').prop('disabled', $(this).prop('checked') === true);
   });

   function delete_contact_profile_image(contact_id) {
        $.post(site_url+'contacts/delete_profile_image/'+contact_id).done(function(){
            $('body').find('#contact-profile-image').removeClass('hide');
            $('body').find('#contact-remove-img').addClass('hide');
            $('body').find('#contact-img').attr('src', site_url+'assets/images/user-placeholder.jpg');
        });
    }
</script>
