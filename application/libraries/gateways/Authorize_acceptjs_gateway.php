<?php

defined('BASEPATH') or exit('No direct script access allowed');

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

class Authorize_acceptjs_gateway extends App_gateway
{
    /**
     * Initialize Authorize_acceptjs_gateway class
     */
    public function __construct()
    {
        /**
        * Call App_gateway __construct function
        */
        parent::__construct();

        /**
         * REQUIRED
         *
         * Gateway unique id
         * The ID must be alpha/alphanumeric
         */
        $this->setId('authorize_acceptjs');

        /**
         * REQUIRED
         *
         * Gateway name
         */
        $this->setName('Authorize.net Accept.js');

        /**
         * Add gateway settings
        */
        $this->setSettings([
            [
                'name'  => 'public_key',
                'label' => 'Public Key',
            ],
            [
                'name'      => 'api_login_id',
                'label'     => 'settings_paymentmethod_authorize_api_login_id',
            ],
            [
                'name'      => 'api_transaction_key',
                'label'     => 'settings_paymentmethod_authorize_api_transaction_key',
                'encrypted' => true,
            ],
            [
                'name'          => 'description_dashboard',
                'label'         => 'settings_paymentmethod_description',
                'type'          => 'textarea',
                'default_value' => 'Payment for Invoice {invoice_number}',
            ],
            [
                'name'          => 'currencies',
                'label'         => 'currency',
                'default_value' => 'USD',
            ],
            [
                'name'          => 'test_mode_enabled',
                'type'          => 'yes_no',
                'default_value' => 0,
                'label'         => 'settings_paymentmethod_testing_mode',
            ],
        ]);

        hooks()->add_action('before_render_payment_gateway_settings', 'authorize_acceptjs_notice');
    }

    /**
     * Process the payment
     *
     * @param  array $data
     *
     * @return void
     */
    public function process_payment($data)
    {
        $this->ci->session->set_userdata(['authorize_acceptjs_total' => $data['amount']]);

        redirect(
                site_url('gateways/authorize_acceptjs/payment?invoiceid=' .
                $data['invoiceid']
                . '&hash=' . $data['invoice']->hash)
        );
    }

    /**
     * Capture payment
     *
     * @param  array $data
     *
     * @return net\authorize\api\contract\v1\CreateTransactionResponse
     */
    public function capture_payment($data)
    {
        $endpoint = $this->getSetting('test_mode_enabled') == '1'
            ? \net\authorize\api\constants\ANetEnvironment::SANDBOX
            : \net\authorize\api\constants\ANetEnvironment::PRODUCTION;

        $invoiceNumber = format_invoice_number($data['invoice']->id);

        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName($this->getSetting('api_login_id'));
        $merchantAuthentication->setTransactionKey($this->decryptSetting('api_transaction_key'));

        // Set the transaction's refId
        $refId = 'ref' . time();

        // Create order information
        $order = new AnetAPI\OrderType();
        $order->setInvoiceNumber($invoiceNumber);
        $order->setDescription(
            str_replace('{invoice_number}', $invoiceNumber, $this->getSetting('description_dashboard'))
        );

        // Set the customer's Bill To address
        $billingName    = [];
        $billingCountry = get_country($data['invoice']->billing_country);
        if (!empty($data['firstName']) && !empty($data['lastName'])) {
            if (is_client_logged_in()) {
                $contact                  = $this->ci->clients_model->get_contact(get_contact_user_id());
                $billingName['firstname'] = $contact->firstname;
                $billingName['lastname']  = $contact->lastname;
            } else {
                if (total_rows(db_prefix() . 'contacts', ['userid' => $data['invoice']->clientid]) == 1) {
                    $contact = $this->ci->clients_model->get_contact(get_primary_contact_user_id($data['invoice']->clientid));

                    if ($contact) {
                        $billingName['firstname'] = $contact->firstname;
                        $billingName['lastname']  = $contact->lastname;
                    }
                }
            }
        } else {
            // user filled the first name and lastname in the Accept.js form
            // use the values from the form in this case
            $billingName['firstname'] = $data['firstName'];
            $billingName['lastname']  = $data['lastName'];
        }

        $customerAddress = new AnetAPI\CustomerAddressType();

        if (!empty($billingName)) {
            $customerAddress->setFirstName($billingName['firstname']);
            $customerAddress->setLastName($billingName['lastname']);
        }

        if (!is_empty_customer_company($data['invoice']->clientid)) {
            $customerAddress->setCompany($data['invoice']->client->company);
        }

        $customerAddress->setAddress($data['invoice']->billing_street);
        $customerAddress->setCity($data['invoice']->billing_city);
        $customerAddress->setState($data['invoice']->billing_state);
        $customerAddress->setZip($data['invoice']->billing_zip);

        if ($billingCountry) {
            $customerAddress->setCountry($billingCountry->iso2);
        }

        if (isset($contact)) {
            $customerAddress->setEmail($contact->email);
        }

        $payment           = new AnetAPI\PaymentType();
        $opaqueDataPayment = new AnetAPI\OpaqueDataType();
        $opaqueDataPayment->setDataDescriptor($data['dataDescriptor']);
        $opaqueDataPayment->setDataValue($data['dataValue']);
        $payment->setOpaqueData($opaqueDataPayment);

        // Create a TransactionRequestType object and add the previous objects to it
        $transactionRequestType = new AnetAPI\TransactionRequestType();
        $transactionRequestType->setTransactionType('authCaptureTransaction');
        $transactionRequestType->setCurrencyCode($data['currency']);
        $transactionRequestType->setAmount(number_format($data['amount'], 2, '.', ''));
        $transactionRequestType->setOrder($order);
        $transactionRequestType->setPayment($payment);
        $transactionRequestType->setBillTo($customerAddress);

        // Assemble the complete transaction request
        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication($merchantAuthentication);
        $request->setRefId($refId);
        $request->setTransactionRequest($transactionRequestType);

        // Create the controller and get the response
        $controller = new AnetController\CreateTransactionController($request);

        return $controller->executeWithApiResponse($endpoint);
    }
}

/**
 * Add Authorize Accept JS Payment Gateway Notice
 *
 * @param  array $gateway
 *
 * @return void
 */
function authorize_acceptjs_notice($gateway)
{
    if ($gateway['id'] == 'authorize_acceptjs') {
        echo '<p class="text-warning">' . _l('authorize_notice') . '</p>';
        echo '<p>If you are enabling test mode, make sure to set test credentials from <a href="https://sandbox.authorize.net" target="_blank">https://sandbox.authorize.net</a></p>';
        echo '<p><b>' . _l('currently_supported_currencies') . '</b>: USD, CAD, CHF, DKK, EUR, GBP, NOK, PLN, SEK, AUD, NZD</p>';
    }
}
