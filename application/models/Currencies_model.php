<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Currencies_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param  integer ID (optional)
     * @return mixed
     * Get currency object based on passed id if not passed id return array of all currencies
     */
    public function get($id = false)
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);

            $currency = $this->db->get(db_prefix() . 'currencies')->row();
            $this->app_object_cache->set('currency-' . $currency->name, $currency);

            return $currency;
        }

        $currencies = $this->app_object_cache->get('currencies-data');

        if (!$currencies && !is_array($currencies)) {
            $currencies = $this->db->get(db_prefix() . 'currencies')->result_array();
            $this->app_object_cache->add('currencies-data', $currencies);
        }

        return $currencies;
    }

    /**
     * Get currency by name/iso code
     * @since  2.3.2
     * @param  string $name currency name/iso code
     * @return object
     */
    public function get_by_name($name)
    {
        $currency = $this->app_object_cache->get('currency-' . $name);
        if (!$currency && !is_object($currency)) {
            $this->db->where('name', $name);
            $currency = $this->db->get(db_prefix() . 'currencies')->row();

            $this->app_object_cache->add('currency-' . $name, $currency);
        }

        return $currency;
    }

    /**
     * @param array $_POST data
     * @return boolean
     */
    public function add($data)
    {
        unset($data['currencyid']);
        $data['name'] = strtoupper($data['name']);
        $this->db->insert(db_prefix() . 'currencies', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New Currency Added [ID: ' . $data['name'] . ']');

            return true;
        }

        return false;
    }

    /**
     * @param  array $_POST data
     * @return boolean
     * Update currency values
     */
    public function edit($data)
    {
        $currencyid = $data['currencyid'];
        unset($data['currencyid']);
        $data['name'] = strtoupper($data['name']);
        $this->db->where('id', $currencyid);
        $this->db->update(db_prefix() . 'currencies', $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('Currency Updated [' . $data['name'] . ']');

            return true;
        }

        return false;
    }

    /**
     * @param  integer ID
     * @return mixed
     * Delete currency from database, if used return array with key referenced
     */
    public function delete($id)
    {
        foreach ($this->app->get_tables_with_currency() as $tt) {
            if (is_reference_in_table($tt['field'], $tt['table'], $id)) {
                return [
                    'referenced' => true,
                ];
            }
        }

        $currency = $this->get($id);
        if ($currency->isdefault == 1) {
            return [
                'is_default' => true,
            ];
        }

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'currencies');
        if ($this->db->affected_rows() > 0) {
            $this->load->dbforge();
            $columns = $this->db->list_fields(db_prefix() . 'items');
            foreach ($columns as $column) {
                if ($column == 'rate_currency_' . $id) {
                    $this->dbforge->drop_column('items', 'rate_currency_' . $id);
                }
            }
            log_activity('Currency Deleted [' . $id . ']');

            return true;
        }

        return false;
    }

    /**
     * @param  integer ID
     * @return boolean
     * Make currency your base currency for better using reports if found invoices with more then 1 currency
     */
    public function make_base_currency($id)
    {
        $base = $this->get_base_currency();
        foreach ($this->app->get_tables_with_currency() as $tt) {
            if (is_reference_in_table($tt['field'], $tt['table'], $base->id)) {
                return [
                    'has_transactions_currency' => true,
                ];
            }
        }

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'currencies', [
            'isdefault' => 1,
        ]);
        if ($this->db->affected_rows() > 0) {
            $this->db->where('id !=', $id);
            $this->db->update(db_prefix() . 'currencies', [
                'isdefault' => 0,
            ]);

            return true;
        }

        return false;
    }

    /**
     * @return object
     * Get base currency
     */
    public function get_base_currency()
    {
        $this->db->where('isdefault', 1);

        return $this->db->get(db_prefix() . 'currencies')->row();
    }

    /**
     * @param  integer ID
     * @return string
     * Get the symbol from the currency
     */
    public function get_currency_symbol($id)
    {
        if (!is_numeric($id)) {
            $id = $this->get_base_currency()->id;
        }

        $currencies = $this->app_object_cache->get('currencies-data');
        if ($currencies) {
            foreach ($currencies as $currency) {
                if ($id == $currency['id']) {
                    return $currency['symbol'];
                }
            }
        }

        $this->db->select('symbol');
        $this->db->from(db_prefix() . 'currencies');
        $this->db->where('id', $id);

        return $this->db->get()->row()->symbol;
    }
}
