<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_273 extends CI_Migration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function up()
    {
        $CI = &get_instance();

        add_option('bitly_access_token', '');

        if (!$CI->db->field_exists('short_link', db_prefix() . 'invoices')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'invoices` ADD `short_link` VARCHAR(100) DEFAULT NULL');
        }
        if (!$CI->db->field_exists('short_link', db_prefix() . 'estimates')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'estimates` ADD `short_link` VARCHAR(100) DEFAULT NULL');
        }
        if (!$CI->db->field_exists('short_link', db_prefix() . 'proposals')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'proposals` ADD `short_link` VARCHAR(100) DEFAULT NULL');
        }
        if (!$CI->db->field_exists('short_link', db_prefix() . 'contracts')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'contracts` ADD `short_link` VARCHAR(100) DEFAULT NULL');
        }

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

        if ($CI->db->table_exists(db_prefix() . 'tbltwocheckout_log')) {
            $CI->db->query('DROP TABLE ' . db_prefix() . 'tbltwocheckout_log');
        }
    }
}
