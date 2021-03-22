<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_241 extends CI_Migration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function up()
    {
        add_option('show_php_version_notice', '1', 0);

        if (!$this->db->field_exists('marked_as_signed', 'contracts')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'contracts` ADD `marked_as_signed` BOOLEAN NOT NULL DEFAULT FALSE AFTER `signature`;');
        }

        if (!table_exists('scheduled_emails')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . "scheduled_emails` (
                  `id` int(11) NOT NULL,
                  `rel_id` int(11) NOT NULL,
                  `rel_type` varchar(15) NOT NULL,
                  `scheduled_at` datetime NOT NULL,
                  `contacts` varchar(197) NOT NULL,
                  `cc` text,
                  `attach_pdf` tinyint(1) NOT NULL DEFAULT '1',
                  `template` varchar(197) NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

            $this->db->query('ALTER TABLE `' . db_prefix() . 'scheduled_emails` ADD PRIMARY KEY (`id`);');
            $this->db->query('ALTER TABLE `' . db_prefix() . 'scheduled_emails` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
        }
    }
}
