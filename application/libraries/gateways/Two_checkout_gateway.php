<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Two_checkout_gateway extends App_gateway
{
    public function __construct()
    {
        /**
         * Call App_gateway __construct function
         */
        parent::__construct();
        /**
         * REQUIRED
         * Gateway unique id
         * The ID must be alpha/alphanumeric
         */
        $this->setId('two_checkout');

        /**
         * REQUIRED
         * Gateway name
         */
        $this->setName('2Checkout');

        /**
         * Add gateway settings
         */
        $this->setSettings(
            [
                [
                    'name'      => 'merchant_code',
                    'encrypted' => true,
                    'label'     => 'two_checkout_merchant_code',
                ],
                [
                    'name'      => 'secret_key',
                    'encrypted' => true,
                    'label'     => 'two_checkout_secret_Key',
                ],
                [
                    'name'          => 'description',
                    'label'         => 'settings_paymentmethod_description',
                    'type'          => 'textarea',
                    'default_value' => 'Payment for Invoice {invoice_number}',
                ],
                [
                    'name'             => 'currencies',
                    'label'            => 'settings_paymentmethod_currencies',
                    'default_value'    => 'USD, EUR, GBP',
                ],
                [
                    'name'          => 'test_mode_enabled',
                    'type'          => 'yes_no',
                    'default_value' => 1,
                    'label'         => 'settings_paymentmethod_testing_mode',
                ],
            ]
        );
        hooks()->add_action('before_render_payment_gateway_settings', 'two_checkout_gateway_webhook_notice');
    }


    /**
     * REQUIRED FUNCTION
     * @param  array $data
     * @return mixed
     */
    public function process_payment($data)
    {
        $reference = $this->reference($data['invoice']->id);
        $logPayment = $this->logTransaction([
            'invoice_id' =>  $data['invoice']->id,
            'amount'    => $data['amount'],
            'reference' => $reference,
        ]);

        if (!$logPayment) {
            set_alert('warning', _l('something_went_wrong'));
            redirect(site_url('invoices/' .  $data['invoice']->id . '/' . $data['invoice']->hash));
        }

        $this->ci->session->set_userdata([
            'two_checkout_total' => $data['amount'],
            'two_checkout_reference' => $reference
        ]);

        redirect(site_url('gateways/two_checkout/payment/' . $data['invoice']->id . '/' . $data['invoice']->hash));
    }
    /**
     * Generate payment reference
     * @param int $id
     * @return string 
     */
    public function reference($id)
    {
        return  md5($id . time());
    }

    public function description($id)
    {
        return str_replace('{invoice_number}', format_invoice_number($id),  $this->getSetting('description'));
    }

    public function logTransaction($data)
    {
        $this->ci->load->model('twocheckout_model');
        return $this->ci->twocheckout_model->add($data);
    }
    public function merchant_code()
    {
        return $this->decryptSetting('merchant_code');
    }

    public function secret_key()
    {
        return $this->decryptSetting('secret_key');
    }

    public function ArrayExpand($array)
    {
        $retval = "";
        for ($i = 0; $i < sizeof($array); $i++) {
            $size        = strlen(StripSlashes($array[$i]));  /*StripSlashes function to be used only for PHP versions <= PHP 5.3.0, only if the magic_quotes_gpc function is enabled */
            $retval    .= $size . StripSlashes($array[$i]);  /*StripSlashes function to be used only for PHP versions <= PHP 5.3.0, only if the magic_quotes_gpc function is enabled */
        }
        return $retval;
    }

    public function hmac($key, $data)
    {
        $b = 64; // byte length for md5
        if (strlen($key) > $b) {
            $key = pack("H*", md5($key));
        }
        $key  = str_pad($key, $b, chr(0x00));
        $ipad = str_pad('', $b, chr(0x36));
        $opad = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad;
        $k_opad = $key ^ $opad;
        return md5($k_opad  . pack("H*", md5($k_ipad . $data)));
    }
}


function two_checkout_gateway_webhook_notice($gateway)
{
    if ($gateway['id'] === 'two_checkout') {
        echo '<div class="alert alert-info">';
        echo _l('two_gateway_webhook_notice', site_url('gateways/two_checkout/webhook'));
        echo '</div>';
    }
}
