<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_270 extends CI_Migration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function up()
    {
        add_option('tasks_reminder_notification_hour', '21');
        add_option('allow_primary_contact_to_manage_other_contacts', '0');

        if (!$this->db->field_exists('google_auth_secret', 'staff')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'staff` ADD `google_auth_secret` TEXT NULL;');
        }

        $this->db->where('folder', 'inbox');
        $this->db->update('leads_email_integration', ['folder' => 'INBOX']);

        @rename(APPPATH . 'libraries/gateways/Authorize_aim_gateway.php', APPPATH . 'libraries/gateways/Authorize_aim_gateway.php.old');
        @rename(APPPATH . 'libraries/gateways/Authorize_sim_gateway.php', APPPATH . 'libraries/gateways/Authorize_sim_gateway.php.old');
        @rename(APPPATH . 'libraries/gateways/Two_checkout_gateway.php', APPPATH . 'libraries/gateways/Two_checkout_gateway.php.old');
    }
}
