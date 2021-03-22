<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Paypal extends App_Controller
{
    public function complete_purchase()
    {
        $invoiceid             = $this->input->get('invoiceid');
        $hash                  = $this->input->get('hash');
        $online_payment_amount = $this->session->userdata('online_payment_amount');
        $currency              = $this->session->userdata('currency');

        $this->session->unset_userdata('online_payment_amount');
        $this->session->unset_userdata('currency');

        $token = $this->input->get('token');
        check_invoice_restrictions($invoiceid, $hash);
        // Check if token is the same like the one in the database
        $this->db->where('token', $token);
        $this->db->where('id', $invoiceid);
        $db_token = $this->db->get(db_prefix().'invoices')->row()->token;
        if ($db_token != $token) {
            set_alert('danger', _l('payment_getaway_token_not_found'));
            redirect(site_url('invoice/' . $invoiceid . '/' . $hash));
        }

        $paypalResponse = $this->paypal_gateway->complete_purchase([
            'token'    => $db_token,
            'amount'   => $online_payment_amount,
            'currency' => $currency,
            ]);

        // Check if error exists in the response
        if (isset($paypalResponse['L_ERRORCODE0'])) {
            set_alert('warning', $paypalResponse['L_SHORTMESSAGE0'] . '<br />' . $paypalResponse['L_LONGMESSAGE0']);
            log_activity('Paypal Payment Error [Error CODE: ' . $paypalResponse['L_ERRORCODE0'] . ' Message: ' . $paypalResponse['L_SHORTMESSAGE0'] . '<br />' . $paypalResponse['L_LONGMESSAGE0'] . ']');
            redirect(site_url('invoice/' . $invoiceid . '/' . $hash));
        } elseif (isset($paypalResponse['PAYMENTINFO_0_ACK']) && $paypalResponse['PAYMENTINFO_0_ACK'] === 'Success') {
            $success = $this->paypal_gateway->addPayment(
            [
              'amount'        => $online_payment_amount,
              'invoiceid'     => $invoiceid,
              'transactionid' => $paypalResponse['PAYMENTINFO_0_TRANSACTIONID'],
              ]
           );

            if ($success) {
                set_alert('success', _l('online_payment_recorded_success'));
            } else {
                set_alert('danger', _l('online_payment_recorded_success_fail_database'));
            }
            redirect(site_url('invoice/' . $invoiceid . '/' . $hash));
        }
    }
}
