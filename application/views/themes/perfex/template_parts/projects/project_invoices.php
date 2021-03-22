<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<table class="table dt-table table-invoices" data-order-col="1" data-order-type="desc">
    <thead>
        <tr>
            <th><?php echo _l('clients_invoice_dt_number'); ?></th>
            <th><?php echo _l('clients_invoice_dt_date'); ?></th>
            <th><?php echo _l('clients_invoice_dt_duedate'); ?></th>
            <th><?php echo _l('clients_invoice_dt_amount'); ?></th>
            <th><?php echo _l('clients_invoice_dt_status'); ?></th>
            <?php
            $custom_fields = get_custom_fields('invoice',array('show_on_client_portal'=>1));
            foreach($custom_fields as $field){ ?>
                <th><?php echo $field['name']; ?></th>
            <?php } ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach($invoices as $invoice){ ?>
            <tr>
                <td data-order="<?php echo $invoice['number']; ?>"><a href="<?php echo site_url('invoice/' . $invoice['id'] . '/' . $invoice['hash']); ?>" class="invoice-number"><?php echo format_invoice_number($invoice['id']); ?></a></td>
                <td data-order="<?php echo $invoice['date']; ?>"><?php echo _d($invoice['date']); ?></td>
                <td data-order="<?php echo $invoice['duedate']; ?>"><?php echo _d($invoice['duedate']); ?></td>
                <td data-order="<?php echo $invoice['total']; ?>"><?php echo app_format_money($invoice['total'], $invoice['currency_name']); ?></td>
                <td><?php echo format_invoice_status($invoice['status'], 'pull-left', true); ?></td>
                <?php foreach($custom_fields as $field){ ?>
                    <td><?php echo get_custom_field_value($invoice['id'],$field['id'],'invoice'); ?></td>
                <?php } ?>
            </tr>
        <?php } ?>
    </tbody>
</table>

