<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel_s<?php if(!isset($invoice) || (isset($invoice) && count($invoices_to_merge) == 0 && (isset($invoice) && !isset($invoice_from_project) && count($expenses_to_bill) == 0 || $invoice->status == Invoices_model::STATUS_CANCELLED))){echo ' hide';} ?>" id="invoice_top_info">
   <div class="panel-body">
      <div class="row">
         <div id="merge" class="col-md-6">
            <?php
              if(isset($invoice)){
                 $this->load->view('admin/invoices/merge_invoice', array('invoices_to_merge'=>$invoices_to_merge));
              }
            ?>
         </div>
         <!--  When invoicing from project area the expenses are not visible here because you can select to bill expenses while trying to invoice project -->
         <?php if(!isset($invoice_from_project)){ ?>
           <div id="expenses_to_bill" class="col-md-6">
              <?php if(isset($invoice) && $invoice->status != Invoices_model::STATUS_CANCELLED){
                 $this->load->view('admin/invoices/bill_expenses',array('expenses_to_bill'=>$expenses_to_bill));
              } ?>
           </div>
         <?php } ?>
      </div>
   </div>
</div>
<div class="panel_s invoice accounting-template">
   <div class="additional"></div>
   <div class="panel-body">
      <?php if(isset($invoice)){ ?>
      <?php  echo format_invoice_status($invoice->status); ?>
      <hr class="hr-panel-heading" />
      <?php } ?>
      <?php hooks()->do_action('before_render_invoice_template'); ?>
      <?php if(isset($invoice)){
        echo form_hidden('merge_current_invoice',$invoice->id);
      } ?>
      <div class="row">
         <div class="col-md-6">
            <div class="f_client_id">
              <div class="form-group select-placeholder">
                <label for="clientid" class="control-label"><?php echo _l('invoice_select_customer'); ?></label>
                <select id="clientid" name="clientid" data-live-search="true" data-width="100%" class="ajax-search<?php if(isset($invoice) && empty($invoice->clientid)){echo ' customer-removed';} ?>" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
               <?php $selected = (isset($invoice) ? $invoice->clientid : '');
                 if($selected == ''){
                   $selected = (isset($customer_id) ? $customer_id: '');
                 }
                 if($selected != ''){
                    $rel_data = get_relation_data('customer',$selected);
                    $rel_val = get_relation_values($rel_data,'customer');
                    echo '<option value="'.$rel_val['id'].'" selected>'.$rel_val['name'].'</option>';
                 } ?>
                </select>
              </div>
            </div>
            <?php
            if(!isset($invoice_from_project)){ ?>
            <div class="form-group select-placeholder projects-wrapper<?php if((!isset($invoice)) || (isset($invoice) && !customer_has_projects($invoice->clientid))){ echo ' hide';} ?>">
               <label for="project_id"><?php echo _l('project'); ?></label>
              <div id="project_ajax_search_wrapper">
                   <select name="project_id" id="project_id" class="projects ajax-search" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                   <?php
                     if(isset($invoice) && $invoice->project_id != 0){
                        echo '<option value="'.$invoice->project_id.'" selected>'.get_project_name_by_id($invoice->project_id).'</option>';
                     }
                   ?>
               </select>
               </div>
            </div>
            <?php } ?>
            <div class="row">
               <div class="col-md-12">
               <hr class="hr-10" />
                  <a href="#" class="edit_shipping_billing_info" data-toggle="modal" data-target="#billing_and_shipping_details"><i class="fa fa-pencil-square-o"></i></a>
                  <?php include_once(APPPATH .'views/admin/invoices/billing_and_shipping_template.php'); ?>
               </div>
               <div class="col-md-6">
                  <p class="bold"><?php echo _l('invoice_bill_to'); ?></p>
                  <address>
                     <span class="billing_street">
                     <?php $billing_street = (isset($invoice) ? $invoice->billing_street : '--'); ?>
                     <?php $billing_street = ($billing_street == '' ? '--' :$billing_street); ?>
                     <?php echo $billing_street; ?></span><br>
                     <span class="billing_city">
                     <?php $billing_city = (isset($invoice) ? $invoice->billing_city : '--'); ?>
                     <?php $billing_city = ($billing_city == '' ? '--' :$billing_city); ?>
                     <?php echo $billing_city; ?></span>,
                     <span class="billing_state">
                     <?php $billing_state = (isset($invoice) ? $invoice->billing_state : '--'); ?>
                     <?php $billing_state = ($billing_state == '' ? '--' :$billing_state); ?>
                     <?php echo $billing_state; ?></span>
                     <br/>
                     <span class="billing_country">
                     <?php $billing_country = (isset($invoice) ? get_country_short_name($invoice->billing_country) : '--'); ?>
                     <?php $billing_country = ($billing_country == '' ? '--' :$billing_country); ?>
                     <?php echo $billing_country; ?></span>,
                     <span class="billing_zip">
                     <?php $billing_zip = (isset($invoice) ? $invoice->billing_zip : '--'); ?>
                     <?php $billing_zip = ($billing_zip == '' ? '--' :$billing_zip); ?>
                     <?php echo $billing_zip; ?></span>
                  </address>
               </div>
               <div class="col-md-6">
                  <p class="bold"><?php echo _l('ship_to'); ?></p>
                  <address>
                     <span class="shipping_street">
                     <?php $shipping_street = (isset($invoice) ? $invoice->shipping_street : '--'); ?>
                     <?php $shipping_street = ($shipping_street == '' ? '--' :$shipping_street); ?>
                     <?php echo $shipping_street; ?></span><br>
                     <span class="shipping_city">
                     <?php $shipping_city = (isset($invoice) ? $invoice->shipping_city : '--'); ?>
                     <?php $shipping_city = ($shipping_city == '' ? '--' :$shipping_city); ?>
                     <?php echo $shipping_city; ?></span>,
                     <span class="shipping_state">
                     <?php $shipping_state = (isset($invoice) ? $invoice->shipping_state : '--'); ?>
                     <?php $shipping_state = ($shipping_state == '' ? '--' :$shipping_state); ?>
                     <?php echo $shipping_state; ?></span>
                     <br/>
                     <span class="shipping_country">
                     <?php $shipping_country = (isset($invoice) ? get_country_short_name($invoice->shipping_country) : '--'); ?>
                     <?php $shipping_country = ($shipping_country == '' ? '--' :$shipping_country); ?>
                     <?php echo $shipping_country; ?></span>,
                     <span class="shipping_zip">
                     <?php $shipping_zip = (isset($invoice) ? $invoice->shipping_zip : '--'); ?>
                     <?php $shipping_zip = ($shipping_zip == '' ? '--' :$shipping_zip); ?>
                     <?php echo $shipping_zip; ?></span>
                  </address>
               </div>
            </div>
            <?php
               $next_invoice_number = get_option('next_invoice_number');
               $format = get_option('invoice_number_format');

               if(isset($invoice)){
                  $format = $invoice->number_format;
               }

               $prefix = get_option('invoice_prefix');

               if ($format == 1) {
                 $__number = $next_invoice_number;
                 if(isset($invoice)){
                   $__number = $invoice->number;
                   $prefix = '<span id="prefix">' . $invoice->prefix . '</span>';
                 }
               } else if($format == 2) {
                 if(isset($invoice)){
                   $__number = $invoice->number;
                   $prefix = $invoice->prefix;
                   $prefix = '<span id="prefix">'. $prefix . '</span><span id="prefix_year">' .date('Y',strtotime($invoice->date)).'</span>/';
                 } else {
                  $__number = $next_invoice_number;
                  $prefix = $prefix.'<span id="prefix_year">'.date('Y').'</span>/';
                }
               } else if($format == 3) {
                  if(isset($invoice)){
                   $yy = date('y',strtotime($invoice->date));
                   $__number = $invoice->number;
                   $prefix = '<span id="prefix">'. $invoice->prefix . '</span>';
                 } else {
                  $yy = date('y');
                  $__number = $next_invoice_number;
                }
               } else if($format == 4) {
                  if(isset($invoice)){
                   $yyyy = date('Y',strtotime($invoice->date));
                   $mm = date('m',strtotime($invoice->date));
                   $__number = $invoice->number;
                   $prefix = '<span id="prefix">'. $invoice->prefix . '</span>';
                 } else {
                  $yyyy = date('Y');
                  $mm = date('m');
                  $__number = $next_invoice_number;
                }
               }

               $_is_draft = (isset($invoice) && $invoice->status == Invoices_model::STATUS_DRAFT) ? true : false;
               $_invoice_number = str_pad($__number, get_option('number_padding_prefixes'), '0', STR_PAD_LEFT);
               $isedit = isset($invoice) ? 'true' : 'false';
               $data_original_number = isset($invoice) ? $invoice->number : 'false';

               ?>
            <div class="form-group">
               <label for="number">
                  <?php echo _l('invoice_add_edit_number'); ?> 
                  <i class="fa fa-question-circle" data-toggle="tooltip" data-title="<?php echo _l('invoice_number_not_applied_on_draft') ?>" data-placement="top"></i>
            </label>
               <div class="input-group">
                  <span class="input-group-addon">
                  <?php if(isset($invoice)){ ?>
                    <a href="#" onclick="return false;" data-toggle="popover" data-container='._transaction_form' data-html="true" data-content="<label class='control-label'><?php echo _l('settings_sales_invoice_prefix'); ?></label><div class='input-group'><input name='s_prefix' type='text' class='form-control' value='<?php echo $invoice->prefix; ?>'></div><button type='button' onclick='save_sales_number_settings(this); return false;' data-url='<?php echo admin_url('invoices/update_number_settings/'.$invoice->id); ?>' class='btn btn-info btn-block mtop15'><?php echo _l('submit'); ?></button>">
                    <i class="fa fa-cog"></i>
                    </a>
                  <?php }
                    echo $prefix;
                  ?>
                  </span>
                  <input type="text" name="number" class="form-control" value="<?php echo ($_is_draft) ? 'DRAFT' : $_invoice_number; ?>" data-isedit="<?php echo $isedit; ?>" data-original-number="<?php echo $data_original_number; ?>" <?php echo ($_is_draft) ? 'disabled' : '' ?>>
                  <?php if($format == 3) { ?>
                  <span class="input-group-addon">
                     <span id="prefix_year" class="format-n-yy"><?php echo $yy; ?></span>
                  </span>
                  <?php } else if($format == 4) { ?>
                   <span class="input-group-addon">
                     <span id="prefix_month" class="format-mm-yyyy"><?php echo $mm; ?></span>
                     /
                     <span id="prefix_year" class="format-mm-yyyy"><?php echo $yyyy; ?></span>
                  </span>
                  <?php } ?>
               </div>
            </div>
            <div class="row">
               <div class="col-md-6">
                  <?php $value = (isset($invoice) ? _d($invoice->date) : _d(date('Y-m-d')));
                  $date_attrs = array();
                  if(isset($invoice) && $invoice->recurring > 0 && $invoice->last_recurring_date != null) {
                    $date_attrs['disabled'] = true;
                  }
                  ?>
                  <?php echo render_date_input('date','invoice_add_edit_date',$value,$date_attrs); ?>
               </div>
               <div class="col-md-6">
                  <?php
                  $value = '';
                  if(isset($invoice)){
                    $value = _d($invoice->duedate);
                  } else {
                    if(get_option('invoice_due_after') != 0){
                        $value = _d(date('Y-m-d', strtotime('+' . get_option('invoice_due_after') . ' DAY', strtotime(date('Y-m-d')))));
                    }
                  }
                   ?>
                  <?php echo render_date_input('duedate','invoice_add_edit_duedate',$value); ?>
               </div>
            </div>
                <?php if(is_invoices_overdue_reminders_enabled()){ ?>
               <div class="form-group">
                  <div class="checkbox checkbox-danger">
                     <input type="checkbox" <?php if(isset($invoice) && $invoice->cancel_overdue_reminders == 1){echo 'checked';} ?> id="cancel_overdue_reminders" name="cancel_overdue_reminders">
                     <label for="cancel_overdue_reminders"><?php echo _l('cancel_overdue_reminders_invoice') ?></label>
                  </div>
               </div>
               <?php } ?>
               <?php $rel_id = (isset($invoice) ? $invoice->id : false); ?>
               <?php
                  if(isset($custom_fields_rel_transfer)) {
                      $rel_id = $custom_fields_rel_transfer;
                  }
               ?>
               <?php echo render_custom_fields('invoice',$rel_id); ?>
         </div>
         <div class="col-md-6">
            <div class="panel_s no-shadow">

                   <div class="form-group">
                  <label for="tags" class="control-label"><i class="fa fa-tag" aria-hidden="true"></i> <?php echo _l('tags'); ?></label>
                  <input type="text" class="tagsinput" id="tags" name="tags" value="<?php echo (isset($invoice) ? prep_tags_input(get_tags_in($invoice->id,'invoice')) : ''); ?>" data-role="tagsinput">
               </div>
               <div class="form-group mbot15 select-placeholder">
                  <label for="allowed_payment_modes" class="control-label"><?php echo _l('invoice_add_edit_allowed_payment_modes'); ?></label>
                  <br />
                  <?php if(count($payment_modes) > 0){ ?>
                  <select class="selectpicker"
                  data-toggle="<?php echo $this->input->get('allowed_payment_modes'); ?>"
                  name="allowed_payment_modes[]"
                  data-actions-box="true"
                  multiple="true"
                  data-width="100%"
                  data-title="<?php echo _l('dropdown_non_selected_tex'); ?>">
                  <?php foreach($payment_modes as $mode){
                     $selected = '';
                     if(isset($invoice)){
                       if($invoice->allowed_payment_modes){
                        $inv_modes = unserialize($invoice->allowed_payment_modes);
                        if(is_array($inv_modes)) {
                         foreach($inv_modes as $_allowed_payment_mode){
                           if($_allowed_payment_mode == $mode['id']){
                             $selected = ' selected';
                           }
                         }
                       }
                     }
                     } else {
                     if($mode['selected_by_default'] == 1){
                        $selected = ' selected';
                     }
                     }
                     ?>
                     <option value="<?php echo $mode['id']; ?>"<?php echo $selected; ?>><?php echo $mode['name']; ?></option>
                  <?php } ?>
                  </select>
                  <?php } else { ?>
                  <p><?php echo _l('invoice_add_edit_no_payment_modes_found'); ?></p>
                  <a class="btn btn-info" href="<?php echo admin_url('paymentmodes'); ?>">
                  <?php echo _l('new_payment_mode'); ?>
                  </a>
                  <?php } ?>
               </div>

               <div class="row">
                  <div class="col-md-6">
                     <?php
                        $currency_attr = array('disabled'=>true,'data-show-subtext'=>true);
                        $currency_attr = apply_filters_deprecated('invoice_currency_disabled', [$currency_attr], '2.3.0', 'invoice_currency_attributes');

                        foreach($currencies as $currency){
                         if($currency['isdefault'] == 1){
                           $currency_attr['data-base'] = $currency['id'];
                         }
                         if(isset($invoice)){
                          if($currency['id'] == $invoice->currency){
                           $selected = $currency['id'];
                         }
                        } else {
                         if($currency['isdefault'] == 1){
                           $selected = $currency['id'];
                         }
                        }
                        }
                        $currency_attr = hooks()->apply_filters('invoice_currency_attributes',$currency_attr);
                        ?>
                     <?php echo render_select('currency', $currencies, array('id','name','symbol'), 'invoice_add_edit_currency', $selected, $currency_attr); ?>
                  </div>
                  <div class="col-md-6">
                     <?php
                        $i = 0;
                        $selected = '';
                        foreach($staff as $member){
                         if(isset($invoice)){
                           if($invoice->sale_agent == $member['staffid']) {
                             $selected = $member['staffid'];
                           }
                         }
                         $i++;
                        }
                        echo render_select('sale_agent',$staff,array('staffid',array('firstname','lastname')),'sale_agent_string',$selected);
                        ?>
                  </div>
                  <div class="col-md-6">
                     <div class="form-group select-placeholder"<?php if(isset($invoice) && !empty($invoice->is_recurring_from)){ ?> data-toggle="tooltip" data-title="<?php echo _l('create_recurring_from_child_error_message', [_l('invoice_lowercase'),_l('invoice_lowercase'), _l('invoice_lowercase')]); ?>"<?php } ?>>
                        <label for="recurring" class="control-label">
                        <?php echo _l('invoice_add_edit_recurring'); ?>
                        </label>
                        <select class="selectpicker"
                        data-width="100%"
                        name="recurring"
                        data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>"
                        <?php
                        // The problem is that this invoice was generated from previous recurring invoice
                        // Then this new invoice you set it as recurring but the next invoice date was still taken from the previous invoice.
                        if(isset($invoice) && !empty($invoice->is_recurring_from)){echo 'disabled';} ?>
                        >
                           <?php for($i = 0; $i <=12; $i++){ ?>
                           <?php
                              $selected = '';
                              if(isset($invoice)){
                                if($invoice->custom_recurring == 0){
                                 if($invoice->recurring == $i){
                                   $selected = 'selected';
                                 }
                               }
                              }
                              if($i == 0){
                               $reccuring_string =  _l('invoice_add_edit_recurring_no');
                              } else if($i == 1){
                               $reccuring_string = _l('invoice_add_edit_recurring_month',$i);
                              } else {
                               $reccuring_string = _l('invoice_add_edit_recurring_months',$i);
                              }
                              ?>
                           <option value="<?php echo $i; ?>" <?php echo $selected; ?>><?php echo $reccuring_string; ?></option>
                           <?php } ?>
                           <option value="custom" <?php if(isset($invoice) && $invoice->recurring != 0 && $invoice->custom_recurring == 1){echo 'selected';} ?>><?php echo _l('recurring_custom'); ?></option>
                        </select>
                     </div>
                  </div>
                  <div class="col-md-6">
                     <div class="form-group select-placeholder">
                        <label for="discount_type" class="control-label"><?php echo _l('discount_type'); ?></label>
                        <select name="discount_type" class="selectpicker" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                           <option value="" selected><?php echo _l('no_discount'); ?></option>
                           <option value="before_tax" <?php
                              if(isset($invoice)){ if($invoice->discount_type == 'before_tax'){ echo 'selected'; }} ?>><?php echo _l('discount_type_before_tax'); ?></option>
                           <option value="after_tax" <?php if(isset($invoice)){if($invoice->discount_type == 'after_tax'){echo 'selected';}} ?>><?php echo _l('discount_type_after_tax'); ?></option>
                        </select>
                     </div>
                  </div>
                  <div class="recurring_custom <?php if((isset($invoice) && $invoice->custom_recurring != 1) || (!isset($invoice))){echo 'hide';} ?>">
                     <div class="col-md-6">
                        <?php $value = (isset($invoice) && $invoice->custom_recurring == 1 ? $invoice->recurring : 1); ?>
                        <?php echo render_input('repeat_every_custom','',$value,'number',array('min'=>1)); ?>
                     </div>
                     <div class="col-md-6">
                        <select name="repeat_type_custom" id="repeat_type_custom" class="selectpicker" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                           <option value="day" <?php if(isset($invoice) && $invoice->custom_recurring == 1 && $invoice->recurring_type == 'day'){echo 'selected';} ?>><?php echo _l('invoice_recurring_days'); ?></option>
                           <option value="week" <?php if(isset($invoice) && $invoice->custom_recurring == 1 && $invoice->recurring_type == 'week'){echo 'selected';} ?>><?php echo _l('invoice_recurring_weeks'); ?></option>
                           <option value="month" <?php if(isset($invoice) && $invoice->custom_recurring == 1 && $invoice->recurring_type == 'month'){echo 'selected';} ?>><?php echo _l('invoice_recurring_months'); ?></option>
                           <option value="year" <?php if(isset($invoice) && $invoice->custom_recurring == 1 && $invoice->recurring_type == 'year'){echo 'selected';} ?>><?php echo _l('invoice_recurring_years'); ?></option>
                        </select>
                     </div>
                  </div>
                  <div id="cycles_wrapper" class="<?php if(!isset($invoice) || (isset($invoice) && $invoice->recurring == 0)){echo ' hide';}?>">
                     <div class="col-md-12">
                        <?php $value = (isset($invoice) ? $invoice->cycles : 0); ?>
                        <div class="form-group recurring-cycles">
                          <label for="cycles"><?php echo _l('recurring_total_cycles'); ?>
                            <?php if(isset($invoice) && $invoice->total_cycles > 0){
                              echo '<small>' . _l('cycles_passed', $invoice->total_cycles) . '</small>';
                            }
                            ?>
                          </label>
                          <div class="input-group">
                            <input type="number" class="form-control"<?php if($value == 0){echo ' disabled'; } ?> name="cycles" id="cycles" value="<?php echo $value; ?>" <?php if(isset($invoice) && $invoice->total_cycles > 0){echo 'min="'.($invoice->total_cycles).'"';} ?>>
                            <div class="input-group-addon">
                              <div class="checkbox">
                                <input type="checkbox"<?php if($value == 0){echo ' checked';} ?> id="unlimited_cycles">
                                <label for="unlimited_cycles"><?php echo _l('cycles_infinity'); ?></label>
                              </div>
                            </div>
                          </div>
                        </div>
                     </div>
                  </div>
               </div>
               <?php $value = (isset($invoice) ? $invoice->adminnote : ''); ?>
               <?php echo render_textarea('adminnote','invoice_add_edit_admin_note',$value); ?>

            </div>
         </div>
      </div>
   </div>
   <div class="panel-body mtop10">
      <div class="row">
         <div class="col-md-4">
            <?php $this->load->view('admin/invoice_items/item_select'); ?>
         </div>
         <?php if(!isset($invoice_from_project) && isset($billable_tasks)){
          ?>
         <div class="col-md-3">
            <div class="form-group select-placeholder input-group-select form-group-select-task_select popover-250">
              <div class="input-group input-group-select">
               <select name="task_select" data-live-search="true" id="task_select" class="selectpicker no-margin _select_input_group" data-width="100%" data-none-selected-text="<?php echo _l('bill_tasks'); ?>">
                  <option value=""></option>
                  <?php foreach($billable_tasks as $task_billable){ ?>
                  <option value="<?php echo $task_billable['id']; ?>"<?php if($task_billable['started_timers'] == true){ ?>disabled class="text-danger important" data-subtext="<?php echo _l('invoice_task_billable_timers_found'); ?>" <?php } else {
                     $task_rel_data = get_relation_data($task_billable['rel_type'],$task_billable['rel_id']);
                     $task_rel_value = get_relation_values($task_rel_data,$task_billable['rel_type']);
                     ?>
                     data-subtext="<?php echo $task_billable['rel_type'] == 'project' ? '' : $task_rel_value['name']; ?>" <?php } ?>><?php echo $task_billable['name']; ?></option>
                  <?php } ?>
               </select>
                <div class="input-group-addon input-group-addon-bill-tasks-help">
                  <?php
                    if(isset($invoice) && !empty($invoice->project_id)) {
                       $help_text = _l('showing_billable_tasks_from_project') . ' ' . get_project_name_by_id($invoice->project_id);
                    } else {
                       $help_text = _l('invoice_task_item_project_tasks_not_included');
                    }
                    echo '<span class="pointer popover-invoker" data-container=".form-group-select-task_select"
                      data-trigger="click" data-placement="top" data-toggle="popover" data-content="'.$help_text.'">
                      <i class="fa fa-question-circle"></i></span>';
                  ?>
                </div>
               </div>
            </div>
         </div>
         <?php } ?>
         <div class="col-md-<?php if(!isset($invoice_from_project)){ echo 5; }else {echo 8;} ?> text-right show_quantity_as_wrapper">
            <div class="mtop10">
               <span><?php echo _l('show_quantity_as'); ?> </span>
               <div class="radio radio-primary radio-inline">
                  <input type="radio" value="1" id="sq_1" name="show_quantity_as" data-text="<?php echo _l('invoice_table_quantity_heading'); ?>" <?php if(isset($invoice) && $invoice->show_quantity_as == 1){echo 'checked';}else if(!isset($hours_quantity) && !isset($qty_hrs_quantity)){echo'checked';} ?>>
                  <label for="sq_1"><?php echo _l('quantity_as_qty'); ?></label>
               </div>
               <div class="radio radio-primary radio-inline">
                  <input type="radio" value="2" id="sq_2" name="show_quantity_as" data-text="<?php echo _l('invoice_table_hours_heading'); ?>" <?php if(isset($invoice) && $invoice->show_quantity_as == 2 || isset($hours_quantity)){echo 'checked';} ?>>
                  <label for="sq_2"><?php echo _l('quantity_as_hours'); ?></label>
               </div>
               <div class="radio radio-primary radio-inline">
                  <input type="radio" value="3" id="sq_3" name="show_quantity_as" data-text="<?php echo _l('invoice_table_quantity_heading'); ?>/<?php echo _l('invoice_table_hours_heading'); ?>" <?php if(isset($invoice) && $invoice->show_quantity_as == 3 || isset($qty_hrs_quantity)){echo 'checked';} ?>>
                  <label for="sq_3"><?php echo _l('invoice_table_quantity_heading'); ?>/<?php echo _l('invoice_table_hours_heading'); ?></label>
               </div>
            </div>
         </div>
      </div>
      <?php if(isset($invoice_from_project)){ echo '<hr class="no-mtop" />'; } ?>
      <div class="table-responsive s_table">
         <table class="table invoice-items-table items table-main-invoice-edit has-calculations no-mtop">
            <thead>
               <tr>
                  <th></th>
                  <th width="20%" align="left"><i class="fa fa-exclamation-circle" aria-hidden="true" data-toggle="tooltip" data-title="<?php echo _l('item_description_new_lines_notice'); ?>"></i> <?php echo _l('invoice_table_item_heading'); ?></th>
                  <th width="25%" align="left"><?php echo _l('invoice_table_item_description'); ?></th>
                  <?php
                  $custom_fields = get_custom_fields('items');
                  foreach($custom_fields as $cf){
                    echo '<th width="15%" align="left" class="custom_field">' . $cf['name'] . '</th>';
                  }
                     $qty_heading = _l('invoice_table_quantity_heading');
                     if(isset($invoice) && $invoice->show_quantity_as == 2 || isset($hours_quantity)){
                      $qty_heading = _l('invoice_table_hours_heading');
                     } else if(isset($invoice) && $invoice->show_quantity_as == 3){
                      $qty_heading = _l('invoice_table_quantity_heading') .'/'._l('invoice_table_hours_heading');
                     }
                     ?>
                  <th width="10%" align="right" class="qty"><?php echo $qty_heading; ?></th>
                  <th width="15%" align="right"><?php echo _l('invoice_table_rate_heading'); ?></th>
                  <th width="20%" align="right"><?php echo _l('invoice_table_tax_heading'); ?></th>
                  <th width="10%" align="right"><?php echo _l('invoice_table_amount_heading'); ?></th>
                  <th align="center"><i class="fa fa-cog"></i></th>
               </tr>
            </thead>
            <tbody>
               <tr class="main">
                  <td></td>
                  <td>
                     <textarea name="description" class="form-control" rows="4" placeholder="<?php echo _l('item_description_placeholder'); ?>"></textarea>
                  </td>
                  <td>
                     <textarea name="long_description" rows="4" class="form-control" placeholder="<?php echo _l('item_long_description_placeholder'); ?>"></textarea>
                  </td>
                  <?php echo render_custom_fields_items_table_add_edit_preview(); ?>
                  <td>
                     <input type="number" name="quantity" min="0" value="1" class="form-control" placeholder="<?php echo _l('item_quantity_placeholder'); ?>">
                     <input type="text" placeholder="<?php echo _l('unit'); ?>" data-toggle="tooltip" data-title="e.q kg, lots, packs" name="unit" class="form-control input-transparent text-right">
                  </td>
                  <td>
                     <input type="number" name="rate" class="form-control" placeholder="<?php echo _l('item_rate_placeholder'); ?>">
                  </td>
                  <td>
                     <?php
                        $default_tax = unserialize(get_option('default_tax'));
                        $select = '<select class="selectpicker display-block tax main-tax" data-width="100%" name="taxname" multiple data-none-selected-text="'._l('no_tax').'">';
                      //  $select .= '<option value=""'.(count($default_tax) == 0 ? ' selected' : '').'>'._l('no_tax').'</option>';
                        foreach($taxes as $tax){
                        $selected = '';
                         if(is_array($default_tax)){
                             if(in_array($tax['name'] . '|' . $tax['taxrate'],$default_tax)){
                                  $selected = ' selected ';
                             }
                        }
                        $select .= '<option value="'.$tax['name'].'|'.$tax['taxrate'].'"'.$selected.'data-taxrate="'.$tax['taxrate'].'" data-taxname="'.$tax['name'].'" data-subtext="'.$tax['name'].'">'.$tax['taxrate'].'%</option>';
                        }
                        $select .= '</select>';
                        echo $select;
                        ?>
                  </td>
                  <td></td>
                  <td>
                     <?php
                        $new_item = 'undefined';
                        if(isset($invoice)){
                         $new_item = true;
                        }
                        ?>
                     <button type="button" onclick="add_item_to_table('undefined','undefined',<?php echo $new_item; ?>); return false;" class="btn pull-right btn-info"><i class="fa fa-check"></i></button>
                  </td>
               </tr>
               <?php if (isset($invoice) || isset($add_items)) {
                  $i               = 1;
                  $items_indicator = 'newitems';
                  if (isset($invoice)) {
                    $add_items       = $invoice->items;
                    $items_indicator = 'items';
                  }
                  foreach ($add_items as $item) {

                    $manual    = false;
                    $table_row = '<tr class="sortable item">';
                    $table_row .= '<td class="dragger">';
                    if (!is_numeric($item['qty'])) {
                      $item['qty'] = 1;
                    }
                    $invoice_item_taxes = get_invoice_item_taxes($item['id']);
                    // passed like string
                    if ($item['id'] == 0) {
                        $invoice_item_taxes = $item['taxname'];
                        $manual             = true;
                    }
                    $table_row .= form_hidden('' . $items_indicator . '[' . $i . '][itemid]', $item['id']);
                    $amount = $item['rate'] * $item['qty'];
                    $amount = app_format_number($amount);
                    // order input
                    $table_row .= '<input type="hidden" class="order" name="' . $items_indicator . '[' . $i . '][order]">';
                    $table_row .= '</td>';
                    $table_row .= '<td class="bold description"><textarea name="' . $items_indicator . '[' . $i . '][description]" class="form-control" rows="5">' . clear_textarea_breaks($item['description']) . '</textarea></td>';
                    $table_row .= '<td><textarea name="' . $items_indicator . '[' . $i . '][long_description]" class="form-control" rows="5">' . clear_textarea_breaks($item['long_description']) . '</textarea></td>';

                    $table_row .= render_custom_fields_items_table_in($item,$items_indicator.'['.$i.']');

                    $table_row .= '<td><input type="number" min="0" onblur="calculate_total();" onchange="calculate_total();" data-quantity name="' . $items_indicator . '[' . $i . '][qty]" value="' . $item['qty'] . '" class="form-control">';

                    $unit_placeholder = '';
                    if(!$item['unit']){
                      $unit_placeholder = _l('unit');
                      $item['unit'] = '';
                    }

                    $table_row .= '<input type="text" placeholder="'.$unit_placeholder.'" name="'.$items_indicator.'['.$i.'][unit]" class="form-control input-transparent text-right" value="'.$item['unit'].'">';

                    $table_row .= '</td>';
                    $table_row .= '<td class="rate"><input type="number" data-toggle="tooltip" title="' . _l('numbers_not_formatted_while_editing') . '" onblur="calculate_total();" onchange="calculate_total();" name="' . $items_indicator . '[' . $i . '][rate]" value="' . $item['rate'] . '" class="form-control"></td>';
                    $table_row .= '<td class="taxrate">' . $this->misc_model->get_taxes_dropdown_template('' . $items_indicator . '[' . $i . '][taxname][]', $invoice_item_taxes, 'invoice', $item['id'], true, $manual) . '</td>';
                    $table_row .= '<td class="amount" align="right">' . $amount . '</td>';
                    $table_row .= '<td><a href="#" class="btn btn-danger pull-left" onclick="delete_item(this,' . $item['id'] . '); return false;"><i class="fa fa-times"></i></a></td>';
                    if (isset($item['task_id'])) {
                      if (!is_array($item['task_id'])) {
                        $table_row .= form_hidden('billed_tasks['.$i.'][]', $item['task_id']);
                      } else {
                        foreach ($item['task_id'] as $task_id) {
                          $table_row .= form_hidden('billed_tasks['.$i.'][]', $task_id);
                        }
                      }
                    } else if (isset($item['expense_id'])) {
                      $table_row .= form_hidden('billed_expenses['.$i.'][]', $item['expense_id']);
                    }
                    $table_row .= '</tr>';
                    echo $table_row;
                    $i++;
                  }
                  }
                  ?>
            </tbody>
         </table>
      </div>
      <div class="col-md-8 col-md-offset-4">
         <table class="table text-right">
            <tbody>
               <tr id="subtotal">
                  <td><span class="bold"><?php echo _l('invoice_subtotal'); ?> :</span>
                  </td>
                  <td class="subtotal">
                  </td>
               </tr>
               <tr id="discount_area">
                  <td>
                     <div class="row">
                        <div class="col-md-7">
                           <span class="bold">
                            <?php echo _l('invoice_discount'); ?>
                         </span>
                        </div>
                        <div class="col-md-5">
                            <div class="input-group" id="discount-total">

                                <input type="number" value="<?php echo (isset($invoice) ? $invoice->discount_percent : 0); ?>" class="form-control pull-left input-discount-percent<?php if(isset($invoice) && !is_sale_discount($invoice,'percent') && is_sale_discount_applied($invoice)){echo ' hide';} ?>" min="0" max="100" name="discount_percent">

                                <input type="number" data-toggle="tooltip" data-title="<?php echo _l('numbers_not_formatted_while_editing'); ?>" value="<?php echo (isset($invoice) ? $invoice->discount_total : 0); ?>" class="form-control pull-left input-discount-fixed<?php if(!isset($invoice) || (isset($invoice) && !is_sale_discount($invoice,'fixed'))){echo ' hide';} ?>" min="0" name="discount_total">

                                <div class="input-group-addon">
                                  <div class="dropdown">
                                    <a class="dropdown-toggle" href="#" id="dropdown_menu_tax_total_type" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                      <span class="discount-total-type-selected">
                                        <?php if(!isset($invoice) || isset($invoice) && (is_sale_discount($invoice,'percent') || !is_sale_discount_applied($invoice))) {
                                          echo '%';
                                        } else {
                                          echo _l('discount_fixed_amount');
                                        }
                                        ?>
                                      </span>
                                      <span class="caret"></span>
                                    </a>
                                    <ul class="dropdown-menu" id="discount-total-type-dropdown" aria-labelledby="dropdown_menu_tax_total_type">
                                      <li>
                                        <a href="#" class="discount-total-type discount-type-percent<?php if(!isset($invoice) || (isset($invoice) && is_sale_discount($invoice,'percent')) || (isset($invoice) && !is_sale_discount_applied($invoice))){echo ' selected';} ?>">%</a>
                                      </li>
                                      <li><a href="#" class="discount-total-type discount-type-fixed<?php if(isset($invoice) && is_sale_discount($invoice,'fixed')){echo ' selected';} ?>">
                                          <?php echo _l('discount_fixed_amount'); ?>
                                        </a>
                                      </li>
                                    </ul>
                                  </div>
                                </div>
                            </div>
                        </div>
                     </div>
                  </td>
                  <td class="discount-total"></td>
               </tr>
               <tr>
                  <td>
                     <div class="row">
                        <div class="col-md-7">
                           <span class="bold"><?php echo _l('invoice_adjustment'); ?></span>
                        </div>
                        <div class="col-md-5">
                           <input type="number" data-toggle="tooltip" data-title="<?php echo _l('numbers_not_formatted_while_editing'); ?>" value="<?php if(isset($invoice)){echo $invoice->adjustment; } else { echo 0; } ?>" class="form-control pull-left" name="adjustment">
                        </div>
                     </div>
                  </td>
                  <td class="adjustment"></td>
               </tr>
               <tr>
                  <td><span class="bold"><?php echo _l('invoice_total'); ?> :</span>
                  </td>
                  <td class="total">
                  </td>
               </tr>
            </tbody>
         </table>
      </div>
      <div id="removed-items"></div>
      <div id="billed-tasks"></div>
      <div id="billed-expenses"></div>
      <?php echo form_hidden('task_id'); ?>
      <?php echo form_hidden('expense_id'); ?>
   </div>
   <div class="row">
      <div class="col-md-12 mtop15">
         <div class="panel-body bottom-transaction">
            <?php $value = (isset($invoice) ? $invoice->clientnote : get_option('predefined_clientnote_invoice')); ?>
            <?php echo render_textarea('clientnote','invoice_add_edit_client_note',$value,array(),array(),'mtop15'); ?>
            <?php $value = (isset($invoice) ? $invoice->terms : get_option('predefined_terms_invoice')); ?>
            <?php echo render_textarea('terms','terms_and_conditions',$value,array(),array(),'mtop15'); ?>
            <div class="btn-bottom-toolbar text-right">
                <?php if(!isset($invoice)){ ?>
                <button class="btn-tr btn btn-default mleft10 text-right invoice-form-submit save-as-draft transaction-submit">
                <?php echo _l('save_as_draft'); ?>
                </button>
                <?php } ?>
              <div class="btn-group dropup">
                <button type="button" class="btn-tr btn btn-info invoice-form-submit transaction-submit"><?php echo _l('submit'); ?></button>
                <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <span class="caret"></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-right width200">
                  <li>
                    <a href="#" class="invoice-form-submit save-and-send transaction-submit">
                      <?php echo _l('save_and_send'); ?>
                    </a>
                  </li>
                  <?php if(!isset($invoice)) { ?>
                  <li>
                    <a href="#" class="invoice-form-submit save-and-send-later transaction-submit">
                      <?php echo _l('save_and_send_later'); ?>
                    </a>
                  </li>
                  <li>
                      <a href="#" class="invoice-form-submit save-and-record-payment transaction-submit">
                        <?php echo _l('save_and_record_payment'); ?>
                      </a>
                  </li>
                <?php } ?>
                </ul>
              </div>
             </div>
         </div>
        <div class="btn-bottom-pusher"></div>
      </div>
   </div>
</div>
