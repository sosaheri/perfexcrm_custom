<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Two_checkout extends App_Controller
{
    public function payment($invoice_id, $invoice_hash)
    {
        check_invoice_restrictions($invoice_id, $invoice_hash);

        $this->load->model('invoices_model');
        $invoice = $this->invoices_model->get($invoice_id);
        load_client_language($invoice->clientid);

        $data['invoice']  = $invoice;
        $data['total']    = $this->session->userdata('two_checkout_total');
        $data['description'] = $this->two_checkout_gateway->description($invoice_id);
        $data['merchant_code'] = $this->two_checkout_gateway->merchant_code();
        $data['testMode'] = $this->two_checkout_gateway->getSetting('test_mode_enabled') == '1';
        $data['reference'] = $this->session->userdata('two_checkout_reference');
        $data['address_2_required'] = false;
        $data['state_required']     = false;
        $data['zip_code_required']  = false;
        $data['billing_email'] = '';

        if (is_client_logged_in()) {
            $contact               = $this->clients_model->get_contact(get_contact_user_id());
            $data['billing_email'] = $contact->email;
            $data['billing_name'] = get_contact_full_name($contact->id);
        } else {
            $contact = $this->clients_model->get_contact(get_primary_contact_user_id($invoice->clientid));
            if ($contact) {
                $data['billing_email'] = $contact->email;
                $data['billing_firstname'] = $contact->firstname;
                $data['billing_lastname'] = $contact->lastname;
                $data['billing_name'] = get_contact_full_name($contact->id);
            }
        }

        echo $this->get_html($data);
    }

    public function get_html($data)
    {
        ob_start(); ?>
        <?php echo payment_gateway_head() ?>
        <script>
            (function(document, src, libName, config) {
                var script = document.createElement('script');
                script.src = src;
                script.async = true;
                var firstScriptElement = document.getElementsByTagName('script')[0];
                script.onload = function() {
                    for (var namespace in config) {
                        if (config.hasOwnProperty(namespace)) {
                            window[libName].setup.setConfig(namespace, config[namespace]);
                        }
                    }
                    window[libName].register();
                };

                firstScriptElement.parentNode.insertBefore(script, firstScriptElement);
            })(document, 'https://secure.2checkout.com/checkout/client/twoCoInlineCart.js', 'TwoCoInlineCart', {
                "app": {
                    "merchant": "<?php echo $data['merchant_code'] ?>"
                },
                "cart": {
                    "host": "https:\/\/secure.2checkout.com"
                }
            });
        </script>

        <body class="gateway-two-checkout">
            <div class="container">
                <div class="col-md-8 col-md-offset-2 mtop30">
                    <div class="mbot30 text-center">
                        <?php echo payment_gateway_logo(); ?>
                    </div>
                    <div class="row">
                        <div class="panel_s">
                            <div class="panel-body">
                                <h4 class="no-margin">
                                    <?php echo _l('payment_for_invoice'); ?> <a href="<?php echo site_url('invoice/' . $data['invoice']->id . '/' . $data['invoice']->hash); ?>"><?php echo format_invoice_number($data['invoice']->id); ?></a>
                                </h4>
                                <hr />
                                <h4 class="mbot20">
                                    <?php echo _l('payment_total', app_format_money($data['total'], $data['invoice']->currency_name)); ?>
                                </h4>
                                <a href="#" class="btn btn-success disabled" id="buy-button"><?php echo _l('invoice_html_online_payment_button_text'); ?></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php echo payment_gateway_scripts(); ?>
            <script type="text/javascript">
                window.document.getElementById('buy-button').addEventListener('click', function() {

                    TwoCoInlineCart.events.subscribe('cart:closed', function(e) {
                        window.location.replace("<?php echo site_url('gateways/two_checkout/cancelled/' . $data['invoice']->id . '/' . $data['invoice']->hash); ?>");
                    });

                    TwoCoInlineCart.setup.setMerchant("<?php echo $data['merchant_code'] ?>");
                    TwoCoInlineCart.setup.setMode('DYNAMIC'); // product type
                    TwoCoInlineCart.register();

                    TwoCoInlineCart.products.add({
                        name: "<?php echo  $data['description']; ?>",
                        quantity: 1,
                        price: "<?php echo  $data['total']; ?>",
                    });

                    TwoCoInlineCart.cart.setOrderExternalRef("<?php echo $data['reference'] ?>");
                    TwoCoInlineCart.cart.setExternalCustomerReference("<?php echo $data['invoice']->client->userid ?>"); // external customer reference 
                    TwoCoInlineCart.cart.setCurrency("<?php echo $data['invoice']->currency_name ?>");
                    TwoCoInlineCart.cart.setTest(new Boolean("<?php echo $data['testMode'] ?>"));
                    TwoCoInlineCart.cart.setReturnMethod({
                        type: 'redirect',
                        url: "<?php echo site_url('gateways/two_checkout/verify/' . $data['invoice']->id . '/' . $data['invoice']->hash); ?>",
                    });

                    TwoCoInlineCart.cart.checkout(); // start checkout process
                });

                setTimeout(function() {
                    $('#buy-button').removeClass('disabled');
                }, 3000);
            </script>
            <?php echo payment_gateway_footer(); ?>
    <?php
        $contents = ob_get_contents();
        ob_end_clean();

        return $contents;
    }

    public function verify($invoice_id, $invoice_hash)
    {
        check_invoice_restrictions($invoice_id, $invoice_hash);
        $this->session->unset_userdata(['two_checkout_total', 'two_checkout_reference']);
        set_alert('info', _l('two_checkout_payment_processing'));
        redirect(site_url('invoice/' . $invoice_id . '/' . $invoice_hash));
    }

    public function cancelled($invoice_id, $invoice_hash)
    {
        check_invoice_restrictions($invoice_id, $invoice_hash);
        $this->session->unset_userdata(['two_checkout_total', 'two_checkout_reference']);
        set_alert('danger', _l('two_checkout_payment_cancelled'));
        redirect(site_url('invoice/' . $invoice_id . '/' . $invoice_hash));
    }

    public function webhook()
    {
        if ($this->input->post()) {
            /* Instant Payment Notification */
            $secret        = $this->two_checkout_gateway->secret_key();
            $signature     = $_POST["HASH"];
            $result        = "";
            $return        = "";
            $body          = "";
            /* read info received */
            ob_start();
            foreach ($_POST as $key => $val) {
                if ($key != "HASH") {
                    if (is_array($val)) $result .= $this->two_checkout_gateway->ArrayExpand($val);
                    else {
                        $size        = strlen(StripSlashes($val)); /*StripSlashes function to be used only for PHP versions <= PHP 5.3.0, only if the magic_quotes_gpc function is enabled */
                        $result    .= $size . StripSlashes($val);  /*StripSlashes function to be used only for PHP versions <= PHP 5.3.0, only if the magic_quotes_gpc function is enabled */
                    }
                }
            }
            $body = ob_get_contents();
            ob_end_flush();

            $date_return = date("YmdHis");
            $return = strlen($_POST["IPN_PID"][0]) . $_POST["IPN_PID"][0] . strlen($_POST["IPN_PNAME"][0]) . $_POST["IPN_PNAME"][0];
            $return .= strlen($_POST["IPN_DATE"]) . $_POST["IPN_DATE"] . strlen($date_return) . $date_return;
            $hash =  $this->two_checkout_gateway->hmac($secret, $result); /* HASH for data received */
            $body .= $result . "\r\n\r\nHash: " . $hash . "\r\n\r\nSignature: " . $signature . "\r\n\r\nReturnSTR: " . $return;
            if ($hash == $signature) {
                /* ePayment response */
                $result_hash =  $this->two_checkout_gateway->hmac($secret, $return);
                echo "<EPAYMENT>" . $date_return . "|" . $result_hash . "</EPAYMENT>";
                /* Payment Record process*/
                $payload = $this->input->post();
                $this->load->model('twocheckout_model');
                $order = $this->twocheckout_model->get($payload['REFNOEXT']);
                if ($order && $payload['ORDERSTATUS'] == 'COMPLETE') {
                    $this->two_checkout_gateway->addPayment(
                        [
                            'amount'        => $order->amount,
                            'invoiceid'     => $order->invoice_id,
                            'transactionid' => $order->reference,
                            'paymentmethod' => $payload['PAYMETHOD'],
                        ]
                    );

                    $this->twocheckout_model->delete($order->id);
                } else {
                    log_activity('2CHECKOUT- WEBHOOK RECEIVED: IPN_PID=' . $payload['IPN_PID'][0] . 'IPN_PNAME=' . $payload['IPN_PID'][0] . 'Status=' . $payload['ORDERSTATUS']);
                }
            } else {
                show_404();
            }
        }
    }
}
