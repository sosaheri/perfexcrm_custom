<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php hooks()->do_action('before_leads_settings'); ?>
<?php echo form_hidden('settings[_leads_settings]','true'); ?>
<?php echo render_input('settings[leads_kanban_limit]','settings_leads_kanban_limit',get_option('leads_kanban_limit'),'number'); ?>
<hr />
<?php
foreach($leads_statuses as $subKey => $subArray){
  if($subArray['isdefault'] == '1'){
    unset($leads_statuses[$subKey]);
  }
}
echo render_select('settings[leads_default_status]',$leads_statuses,array('id','name'),'leads_default_status',get_option('leads_default_status')); ?>
<hr />
<?php echo render_select('settings[leads_default_source]',$leads_sources,array('id','name'),'leads_default_source',get_option('leads_default_source')); ?>
<hr />
<?php
$savedValidation = json_decode(get_option('lead_unique_validation'));
$validationFields = [
  [
    'value'=>'email',
    'name'=>_l('lead_add_edit_email'),
  ],
  [
    'value'=>'phonenumber',
    'name'=>_l('lead_add_edit_phonenumber'),
  ],
  [
    'value'=>'website',
    'name'=>_l('lead_website'),
  ],
  [
    'value'=>'company',
    'name'=>_l('lead_company'),
  ],
];
$validationFields = hooks()->apply_filters('lead_available_dupicate_validation_fields_option', $validationFields);
?>
<div class="form-group" id="unique_validation_wrapper">
  <label for="lead_unique_validation"><?php echo _l('lead_unique_validation_on'); ?></label>
  <select class="selectpicker" name="settings[lead_unique_validation][]" id="lead_unique_validation" data-width="100%" multiple="true" data-none-selected-text="<?php echo _l('no_validation'); ?>">
    <?php foreach($validationFields as $leadField) {
      ?>
      <option value="<?php echo $leadField['value']; ?>"<?php if(in_array($leadField['value'], $savedValidation)){echo ' selected';} ?>>
        <?php echo $leadField['name']; ?>
      </option>
      <?php
    }
    ?>
  </select>
</div>
<hr />
<?php render_yes_no_option('auto_assign_customer_admin_after_lead_convert','auto_assign_customer_admin_after_lead_convert','auto_assign_customer_admin_after_lead_convert_help'); ?>
<hr />
<?php render_yes_no_option('allow_non_admin_members_to_import_leads','allow_non_admin_members_to_import_leads'); ?>
<hr />
<div class="row">
  <div class="col-md-7">
    <label for="default_leads_kanban_sort" class="control-label"><?php echo _l('default_leads_kanban_sort'); ?></label>
    <select name="settings[default_leads_kanban_sort]" id="default_leads_kanban_sort" class="selectpicker" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
      <option value="dateadded" <?php if(get_option('default_leads_kanban_sort') == 'dateadded'){echo 'selected'; }?>><?php echo _l('leads_sort_by_datecreated'); ?></option>
      <option value="leadorder" <?php if(get_option('default_leads_kanban_sort') == 'leadorder'){echo 'selected'; }?>><?php echo _l('leads_sort_by_kanban_order'); ?></option>
      <option value="lastcontact" <?php if(get_option('default_leads_kanban_sort') == 'lastcontact'){echo 'selected'; }?>><?php echo _l('leads_sort_by_lastcontact'); ?></option>
    </select>
  </div>
  <div class="col-md-5">
   <div class="mtop30 text-right">
    <div class="radio radio-inline radio-primary">
      <input type="radio" id="k_desc" name="settings[default_leads_kanban_sort_type]" value="asc" <?php if(get_option('default_leads_kanban_sort_type') == 'asc'){echo 'checked';} ?>>
      <label for="k_desc"><?php echo _l('order_ascending'); ?></label>
    </div>
    <div class="radio radio-inline radio-primary">
      <input type="radio" id="k_asc" name="settings[default_leads_kanban_sort_type]" value="desc" <?php if(get_option('default_leads_kanban_sort_type') == 'desc'){echo 'checked';} ?>>
      <label for="k_asc"><?php echo _l('order_descending'); ?></label>
    </div>
  </div>
</div>
<div class="clearfix"></div>
</div>
<hr />
<?php echo render_yes_no_option('lead_lock_after_convert_to_customer','lead_lock_after_convert_to_customer'); ?>
<hr />
<div class="form-group">
  <label for="settings[lead_modal_class]" class="control-label">
    <?php echo _l('modal_width_class'); ?> (modal-lg, modal-xl, modal-xxl)
  </label>
  <input type="text" id="settings[lead_modal_class]" name="settings[lead_modal_class]" class="form-control" value="<?php echo get_option('lead_modal_class'); ?>">
</div>
<?php hooks()->do_action('after_leads_settings'); ?>
