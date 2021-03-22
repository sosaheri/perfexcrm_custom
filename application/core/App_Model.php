<?php

defined('BASEPATH') or exit('No direct script access allowed');

class App_Model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();

        $this->db->reconnect();

        $timezone = get_option('default_timezone');

        if ($timezone != '') {
            date_default_timezone_set($timezone);
        }

        hooks()->do_action('model_init', $this);
    }
}
