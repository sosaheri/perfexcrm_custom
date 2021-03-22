<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="widget" id="widget-<?php echo create_widget_id(); ?>" data-name="<?php echo _l('contracts_about_to_expire'); ?>">
    <?php if(staff_can('view', 'contracts') || staff_can('view_own', 'contracts')) { ?>
    <div class="panel_s contracts-expiring">
        <div class="panel-body padding-10">
            <div class="widget-dragger"></div>
            <p class="padding-5"><?php echo _l('contracts_about_to_expire'); ?></p>
            <hr class="hr-panel-heading-dashboard">
            <?php if (!empty($expiringContracts)) { ?>
                <div class="table-vertical-scroll">
                    <a href="<?php echo admin_url('contracts'); ?>" class="mbot20 inline-block full-width"><?php echo _l('home_widget_view_all'); ?></a>
                    <table class="table dt-table" data-order-col="3" data-order-type="desc">
                        <thead>
                            <tr>
                                <th><?php echo _l('contract_list_subject'); ?> #</th>
                                <th class="<?php echo (isset($client) ? 'not_visible' : ''); ?>"><?php echo _l('contract_list_client'); ?></th>
                                <th><?php echo _l('contract_list_start_date'); ?></th>
                                <th><?php echo _l('contract_list_end_date'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expiringContracts as $contract) { ?>
                                <tr>
                                    <td>
                                        <?php echo '<a href="' . admin_url("contracts/contract/" . $contract["id"]) . '">' . $contract["subject"] . '</a>'; ?>
                                    </td>
                                    <td>
                                        <?php echo '<a href="' . admin_url("clients/client/" . $contract["client"]) . '">' . get_company_name($contract["client"]) . '</a>'; ?>
                                    </td>
                                    <td>
                                        <?php echo _d($contract['datestart']); ?>
                                    </td>
                                    <td>
                                        <?php echo _d($contract['dateend']); ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } else { ?>
                <div class="text-center padding-5">
                    <i class="fa fa-check fa-5x" aria-hidden="true"></i>
                    <h4><?php echo _l('no_contracts_about_to_expire',["7"]) ; ?> </h4>
                </div>
            <?php } ?>
        </div>
    </div>
    <?php } ?>
</div>
