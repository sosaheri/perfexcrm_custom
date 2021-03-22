<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_271 extends CI_Migration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function up()
    {
        $CI = &get_instance();

        add_option('items_table_amounts_exclude_currency_symbol', 1);
        add_option('round_off_task_timer_option', 0);
        add_option('round_off_task_timer_time', 5);

        if (!$CI->db->field_exists('folder', db_prefix() . 'departments')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'departments` ADD `folder` VARCHAR(191) NOT NULL DEFAULT \'INBOX\' AFTER `encryption`;');
        }

        $CI->db->query(
            'CREATE TABLE IF NOT EXISTS ' . db_prefix() . 'templates(
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
            `name` VARCHAR(255) NOT NULL,
            `type` VARCHAR(100) NOT NULL,
            `addedfrom` INT NOT NULL,
            `content` TEXT DEFAULT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;'
        );

        $CI->db->query(
            'CREATE TABLE IF NOT EXISTS ' . db_prefix() . 'twocheckout_log(
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
            `reference` VARCHAR(64) NOT NULL,
            `invoice_id` INT NOT NULL,
            `amount` VARCHAR(25) NOT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (invoice_id) REFERENCES `' . db_prefix() . 'invoices`(id) ON DELETE CASCADE
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;'
        );

        $proposalDir       = APPPATH . 'views/admin/proposals/templates/';
        $proposalTemplates = get_filenames($proposalDir);

        if ($proposalTemplates) {
            foreach ($proposalTemplates as $template) {
                $path         = $proposalDir . $template;
                $data['name'] = pathinfo($path, PATHINFO_FILENAME);
                if ($content = file_get_contents($path)) {
                    $data['content']   = html_purify($content);
                    $data['type']      = 'proposals';
                    $data['addedfrom'] = 1;
                    $CI->db->insert(db_prefix() . 'templates', $data);
                }
            }
        }

        $contractDir       = APPPATH . 'views/admin/contracts/templates/';
        $contractTemplates = get_filenames($contractDir);
        if ($contractTemplates) {
            foreach ($contractTemplates as $template) {
                $path         = $contractDir . $template;
                $data['name'] = pathinfo($path, PATHINFO_FILENAME);
                if ($content = file_get_contents($path)) {
                    $data['content']   = html_purify($content);
                    $data['type']      = 'contracts';
                    $data['addedfrom'] = 1;
                    $CI->db->insert(db_prefix() . 'templates', $data);
                }
            }
        }

        @rename(APPPATH . 'views/admin/proposals/templates', APPPATH . 'views/admin/proposals/templates.old');
        @rename(APPPATH . 'views/admin/proposals/contracts', APPPATH . 'views/admin/contracts/contracts.old');

        $this->db->query('ALTER TABLE `' . db_prefix() . 'clients` CHANGE `address` `address` VARCHAR(191) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;');
        $this->db->query('ALTER TABLE `' . db_prefix() . 'clients` ADD INDEX(`active`);');
    }
}
