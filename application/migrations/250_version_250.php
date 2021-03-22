<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_250 extends CI_Migration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function up()
    {
        add_option('recaptcha_ignore_ips', '');
        add_option('show_task_reminders_on_calendar', 1);
        $this->db->query('ALTER TABLE `' . db_prefix() . "subscriptions` ADD `tax_id_2` INT NOT NULL DEFAULT '0' AFTER `stripe_tax_id`, ADD `stripe_tax_id_2` VARCHAR(50) NULL DEFAULT NULL AFTER `tax_id_2`;");

        // Fix for version 2.3.4, try to create the template if not exists
        $eventTemplateMessage = 'Hi {staff_firstname}! <br /><br />This is a reminder for event <a href=\"{event_link}\">{event_title}</a> scheduled at {event_start_date}. <br /><br />Regards.';
        create_email_template('Upcoming Event - {event_title}', $eventTemplateMessage, 'staff', 'Event Notification (Calendar)', 'event-notification-to-staff');

        $this->db->query('ALTER TABLE `' . db_prefix() . 'contracts` ADD `project_id` INT NULL DEFAULT NULL AFTER `contract_type`;');

        $this->db->where('name', 'available_features');
        $projectSettings = $this->db->get(db_prefix() . 'project_settings')->result_array();
        foreach ($projectSettings as $availableFeature) {
            @$setting = unserialize($availableFeature['value']);
            $modified = false;
            if (is_array($setting) && !array_key_exists('project_contracts', $setting)) {
                $setting['project_contracts'] = 1;
                $modified                     = true;
            }

            if ($modified) {
                $this->db->where('id', $availableFeature['id']);
                $this->db->update(db_prefix() . 'project_settings', ['value' => serialize($setting)]);
            }
        }

        $this->db->where('name', 'clients_default_theme');
        $theme = $this->db->get(db_prefix() . 'options')->row()->value;

        if ($theme != 'perfex') {
            @copy(APPPATH . 'views/themes/perfex/template_parts/contracts_table.php', APPPATH . 'views/themes/' . active_clients_theme() . '/template_parts/contracts_table.php');

            @copy(APPPATH . 'views/themes/perfex/template_parts/projects/project_contracts.php', APPPATH . 'views/themes/' . active_clients_theme() . '/template_parts/projects/project_contracts.php');
        }

        // for lead_value
        $this->db->query('ALTER TABLE `' . db_prefix() . 'leads` ADD `lead_value` DECIMAL(15,2) NULL DEFAULT NULL AFTER `client_id`;');
    }
}
