<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Authorize_acceptjs extends App_Controller
{
    /**
     * Capture payment
     *
     * @return void
     */
    public function capture()
    {
        error_reporting(1);
        if ($this->input->post()) {
            $data = $this->input->post();

            $this->load->model('invoices_model');
            $invoice = $this->invoices_model->get($this->input->post('invoiceid'));
            check_invoice_restrictions($invoice->id, $invoice->hash);
            load_client_language($invoice->clientid);

            $data['amount']   = $this->session->userdata('authorize_acceptjs_total');
            $data['currency'] = $invoice->currency_name;
            $data['invoice']  = $invoice;

            $response = $this->authorize_acceptjs_gateway->capture_payment($data);
            if ($response != null) {
                // Check to see if the API request was successfully received and acted upon
                if ($response->getMessages()->getResultCode() == 'Ok') {
                    // Since the API request was successful, look for a transaction response
                    // and parse it to display the results of authorizing the card
                    $tresponse = $response->getTransactionResponse();

                    if ($tresponse != null && $tresponse->getMessages() != null) {
                        $success = $this->authorize_acceptjs_gateway->addPayment([
                          'amount'        => $data['amount'],
                          'invoiceid'     => $invoice->id,
                          'transactionid' => $tresponse->getTransId(),
                        ]);

                        set_alert($success
                          ? 'success' : 'danger', _l($success ? 'online_payment_recorded_success'
                          : 'online_payment_recorded_success_fail_database'));
                    } else {
                        $errorMessage = _l('invoice_payment_record_failed') . ' ';

                        if ($tresponse->getErrors() != null) {
                            $errorMessage .= ': Code: ' . $tresponse->getErrors()[0]->getErrorCode() . ', ' . $tresponse->getErrors()[0]->getErrorText();
                        }

                        set_alert('danger', $errorMessage);
                    }
                } else {
                    $errorMessage = _l('invoice_payment_record_failed') . ' ';

                    $tresponse = $response->getTransactionResponse();
                    if ($tresponse != null && $tresponse->getErrors() != null) {
                        $errorMessage .= ': Code: ' . $tresponse->getErrors()[0]->getErrorCode() . ', ' . $tresponse->getErrors()[0]->getErrorText();
                    } else {
                        $errorMessage .= ': Code: ' . $response->getMessages()->getMessage()[0]->getCode() . ', ' . $response->getMessages()->getMessage()[0]->getText();
                    }

                    set_alert('danger', $errorMessage);
                }
            } else {
                // No response returned
                set_alert('danger', _l('invoice_payment_record_failed'));
            }

            $this->session->unset_userdata('authorize_acceptjs_total');
        }

        redirect(site_url('invoice/' . $invoice->id . '/' . $invoice->hash));
    }

    /**
     * Show payment form
     *
     * @return void
     */
    public function payment()
    {
        check_invoice_restrictions($this->input->get('invoiceid'), $this->input->get('hash'));

        $this->load->model('invoices_model');
        $invoice = $this->invoices_model->get($this->input->get('invoiceid'));
        load_client_language($invoice->clientid);

        $data['invoice']  = $invoice;
        $data['total']    = $this->session->userdata('authorize_acceptjs_total');
        $data['testMode'] = $this->authorize_acceptjs_gateway->getSetting('test_mode_enabled') == '1';
        echo $this->get_html($data);
    }

    /**
     * Get payment gateway html view
     *
     * @param  array  $data
     *
     * @return string
     */
    protected function get_html($data = [])
    {
        ob_start(); ?>
        <?php echo payment_gateway_head(_l('payment_for_invoice') . ' ' . format_invoice_number($data['invoice']->id)); ?>
        <script type="text/javascript"
           src="https://<?php echo $data['testMode'] ? 'jstest' : 'js'; ?>.authorize.net/v3/AcceptUI.js"
           charset="utf-8"></script>
        <script type="text/javascript"
           src="https://<?php echo $data['testMode'] ? 'jstest' : 'js'; ?>.authorize.net/v1/Accept.js"
           charset="utf-8"></script>
        <body class="gateway-authorize-aim">
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
                          <div id="errors" class="alert alert-danger" style="display:none;"></div>
                          <?php echo form_open(site_url('gateways/authorize_acceptjs/capture'), ['id' => 'paymentForm']); ?>
                          <?php echo form_hidden('invoiceid', $data['invoice']->id); ?>
                          <input type="hidden" name="dataValue" id="dataValue" />
                          <input type="hidden" name="dataDescriptor" id="dataDescriptor" />
                          <input type="hidden" name="firstName" id="firstName" />
                          <input type="hidden" name="lastName" id="lastName" />
                          <button type="button"
                             class="AcceptUI btn btn-info"
                             id="payNowButton"
                             data-billingAddressOptions='{"show":true, "required":false}'
                             data-apiLoginID="<?php echo $this->authorize_acceptjs_gateway->getSetting('api_login_id'); ?>"
                             data-clientKey="<?php echo $this->authorize_acceptjs_gateway->getSetting('public_key'); ?>"
                             data-acceptUIFormBtnTxt="Pay Now"
                             data-acceptUIFormHeaderTxt="Card Information"
                             data-responseHandler="responseHandler">
                          <?php echo _l('invoice_html_online_payment_button_text'); ?>
                          </button>
                          </form>
                       </div>
                    </div>
                 </div>
              </div>
           </div>
           <?php echo payment_gateway_scripts(); ?>
           <?php echo payment_gateway_footer(); ?>
           <script type="text/javascript">
              $(function(){
                setTimeout(function(){
                   $('#payNowButton').click();
                }, 500)
              })

              function responseHandler(response) {
                  var errorsElement = document.getElementById('errors');
                  if (response.messages.resultCode === "Error") {
                    errorsElement.style.display = 'block';
                      var i = 0;

                      while (i < response.messages.message.length) {
                          errorsElement.innerHTML = '<p>' +response.messages.message[i].code + ": " +
                                response.messages.message[i].text + '</p>';
                          i = i + 1;
                      }
                  } else {
                    errorsElement.innerHTML = '';
                    errorsElement.style.display = 'none';

                      paymentFormUpdate(response);
                  }
              }

              function paymentFormUpdate(response) {
                  document.getElementById("dataDescriptor").value = response.opaqueData.dataDescriptor;
                  document.getElementById("dataValue").value = response.opaqueData.dataValue;
                  document.getElementById("firstName").value = response.customerInformation.firstName;
                  document.getElementById("lastName").value = response.customerInformation.lastName;

                  document.getElementById("paymentForm").submit();
              }
           </script>
           <?php
           $contents = ob_get_contents();
        ob_end_clean();

        return $contents;
    }
}
