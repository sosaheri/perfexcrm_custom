<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Braintree extends App_Controller
{
    public function complete_purchase($invoice_id, $invoice_hash)
    {
        if ($this->input->post()) {
            check_invoice_restrictions($invoice_id, $invoice_hash);

            $data = $this->input->post();

            $this->load->model('invoices_model');
            $invoice = $this->invoices_model->get($invoice_id);

            load_client_language($invoice->clientid);
            $data['currency'] = $invoice->currency_name;

            $oResponse = $this->paypal_braintree_gateway->finish_payment($data);

            if ($oResponse->isSuccessful()) {
                $transactionid   = $oResponse->getTransactionReference();
                $paymentResponse = $this->paypal_braintree_gateway->fetch_payment($transactionid);
                $paymentData     = $paymentResponse->getData();

                $success = $this->paypal_braintree_gateway->addPayment(
          [
            'amount'        => $data['amount'],
            'invoiceid'     => $invoice->id,
            'paymentmethod' => $paymentData->paymentInstrumentType,
            'transactionid' => $transactionid,
          ]
        );

                set_alert($success ? 'success' : 'danger', _l($success ? 'online_payment_recorded_success' : 'online_payment_recorded_success_fail_database'));
            } else {
                set_alert('danger', $oResponse->getMessage());
            }
        }
    }

    public function make_payment()
    {
        check_invoice_restrictions($this->input->get('invoiceid'), $this->input->get('hash'));
        $this->load->model('invoices_model');
        $invoice = $this->invoices_model->get($this->input->get('invoiceid'));
        load_client_language($invoice->clientid);
        $data['invoice']      = $invoice;
        $data['total']        = $this->input->get('total');
        $data['client_token'] = $this->paypal_braintree_gateway->generate_token();
        $data['email']        = '';
        if (is_client_logged_in()) {
            $contact = $this->clients_model->get_contact(get_contact_user_id());
            $client  = $this->clients_model->get(get_client_user_id());

            if (!empty($contact->phonenumber)) {
                $data['phone'] = $contact->phonenumber;
            } elseif (!empty($client->phonenumber)) {
                $data['phone'] = $client->phonenumber;
            }
            $data['contact'] = $contact;
        } else {
            $client = $this->clients_model->get($invoice->clientid);
            if (!empty($client->phonenumber)) {
                $data['phone'] = $client->phonenumber;
            }
        }

        $country = get_country($invoice->billing_country);

        if ($country) {
            $data['country'] = $country->iso2;
        }

        echo $this->get_view($data);
    }
  public function get_view($data = []){
  ?>
  <?php echo payment_gateway_head(_l('payment_for_invoice') . ' ' . format_invoice_number($data['invoice']->id)); ?>
  <body class="gateway-braintree">
     <div class="container">
     <div class="col-md-8 col-md-offset-2 mtop30">
        <div class="mbot30 text-center">
           <?php echo payment_gateway_logo(); ?>
        </div>
        <div class="row">
           <div class="panel_s">
              <div class="panel-body">
                 <h3 class="no-margin">
                    <b><?php echo _l('payment_for_invoice'); ?></b>
                    <a href="<?php echo site_url('invoice/' . $data['invoice']->id . '/' . $data['invoice']->hash); ?>">
                    <b><?php echo format_invoice_number($data['invoice']->id); ?></b>
                    </a>
                 </h3>
                 <h4><?php echo _l('payment_total', app_format_money($data['total'], $data['invoice']->currency_name)); ?></h4>
                 <div class="row">
                    <div class="col-xs-12" >
                       <div class="form-group">
                          <label for="email"><?php echo _l('payment_billing_email'); ?></label>
                          <input type="email" class="form-control" id="email" value="<?php echo isset($data['contact']) ? $data['contact']->email : ''; ?>">
                          <span id="help-email" class="help-block"></span>
                       </div>
                       <div class="form-group">
                          <label for="billing-phone"><?php echo _l('client_phonenumber'); ?></label>
                          <input type="billing-phone" class="form-control" id="billing-phone"
                             value="<?php echo isset($data['phone']) ? $data['phone'] : ''; ?>">
                          <span id="help-billing-phone" class="help-block"></span>
                       </div>
                       <div class="row">
                          <div class="col-md-6">
                             <div class="form-group">
                                <label for="billing-given-name"><?php echo _l('client_firstname'); ?></label>
                                <input type="billing-given-name" class="form-control" id="billing-given-name"
                                   value="<?php echo isset($data['contact']) ? $data['contact']->firstname : ''; ?>">
                                <span id="help-billing-given-name" class="help-block"></span>
                             </div>
                          </div>
                          <div class="col-md-6">
                             <div class="form-group">
                                <label for="billing-surname"><?php echo _l('client_lastname'); ?></label>
                                <input type="billing-surname" class="form-control" id="billing-surname"
                                   value="<?php echo isset($data['contact']) ? $data['contact']->lastname : ''; ?>">
                                <span id="help-billing-surname" class="help-block"></span>
                             </div>
                          </div>
                       </div>
                       <div class="row">
                          <div class="col-md-6">
                             <div class="form-group">
                                <label for="billing-street-address"><?php echo _l('billing_address'); ?></label>
                                <input type="billing-street-address" class="form-control" id="billing-street-address"
                                   value="<?php echo $data['invoice']->billing_street; ?>">
                                <span id="help-billing-street-address" class="help-block"></span>
                             </div>
                          </div>
                          <div class="col-md-6">
                             <div class="form-group">
                                <label for="billing-locality"><?php echo _l('billing_city'); ?></label>
                                <input type="billing-locality" class="form-control" id="billing-locality"
                                   value="<?php echo $data['invoice']->billing_city; ?>">
                                <span id="help-billing-locality" class="help-block"></span>
                             </div>
                          </div>
                       </div>
                       <div class="row">
                          <div class="col-md-6">
                             <div class="form-group">
                                <label for="billing-region"><?php echo _l('billing_state'); ?></label>
                                <input type="billing-region" class="form-control" id="billing-region">
                                <span id="help-billing-region" class="help-block"></span>
                             </div>
                          </div>
                          <div class="col-md-6">
                             <div class="form-group">
                                <label for="billing-postal-code"><?php echo _l('billing_zip'); ?></label>
                                <input type="billing-postal-code" class="form-control" id="billing-postal-code"
                                   value="<?php echo $data['invoice']->billing_zip; ?>">
                                <span id="help-billing-postal-code" class="help-block"></span>
                             </div>
                          </div>
                       </div>
                       <div class="form-group">
                          <label for="billing-country-code"><?php echo _l('billing_country'); ?></label>
                          <select type="billing-country-code" class="form-control" id="billing-country-code">
                             <?php foreach(get_all_countries() as $country) { ?>
                             <option value="<?php echo $country['iso2']; ?>"<?php if(isset($data['country']) && $data['country'] == $country['iso2']){echo ' selected'; } ?>><?php echo $country['short_name']; ?></option>
                             <?php } ?>
                          </select>
                          <span id="help-billing-country-code" class="help-block"></span>
                       </div>
                    </div>
                 </div>
                 <div id="request-errors" class="alert alert-danger" style="display:none;"></div>
                 <hr />
                 <div class="bt-drop-in-wrapper">
                    <div id="bt-dropin"></div>
                 </div>
                 <div class="text-center" style="margin-top:15px;">
                    <button class="btn btn-info" type="button" id="submit-button" style="display:none;">
                    <?php echo _l('submit_payment'); ?>
                    </button>
                 </div>
              </div>
           </div>
        </div>
     </div>
     <script src="https://js.braintreegateway.com/web/dropin/1.20.4/js/dropin.min.js"></script>
     <?php echo payment_gateway_scripts(); ?>
     <script>
        var invoiceUrl = '<?php echo site_url('invoice/' . $data['invoice']->id . '/' . $data['invoice']->hash); ?>';
        var completePaymentUrl = '<?php echo site_url('gateways/braintree/complete_purchase/' . $data['invoice']->id . '/' . $data['invoice']->hash); ?>';
        var amount = <?php echo number_format($data['total'], 2, '.', ''); ?>;
        var currencyName = "<?php echo $data['invoice']->currency_name; ?>";
        var clientToken = "<?php echo $data['client_token']; ?>";
        var button = document.querySelector('#submit-button');
        var locale = '';
        var paypalEnabled = "<?php echo $this->paypal_braintree_gateway->getSetting('paypal_enabled'); ?>";
        var requestErrors = document.getElementById('request-errors');

        if(typeof(window.navigator.language) != 'undefined') {
            locale = window.navigator.language;
            locale = locale.replace('-','_');
        }

        var billingFields = [
            'email',
            'billing-phone',
            'billing-given-name',
            'billing-surname',
            'billing-street-address',
            'billing-locality',
            'billing-region',
            'billing-postal-code',
            'billing-country-code'
        ].reduce(function (fields, fieldName) {
          var field = fields[fieldName] = {
            input: document.getElementById(fieldName),
            help: document.getElementById('help-' + fieldName)
          };

          field.input.addEventListener('focus', function() {
            clearFieldValidations(field);
          });

          return fields;
        }, {});

        function clearFieldValidations (field) {
          field.help.innerText = '';
          field.help.parentNode.classList.remove('has-error');
        }

        billingFields['billing-region'].optional = true;

        function enableSubmitButton() {
          button.removeAttribute('disabled');
        }

        function disableSubmitButton() {
          button.disabled = true;
        }

        function validateBillingFields() {
          var isValid = true;

          Object.keys(billingFields).forEach(function (fieldName) {
            let validationMessage ="<?php echo _l('form_validation_required'); ?>"
            var fieldEmpty = false;
            var field = billingFields[fieldName];

            if (field.optional) {
              return;
            }

            fieldEmpty = field.input.value.trim() === '';
            if (fieldEmpty) {
              isValid = false;
              field.help.innerText = validationMessage.replace('{field}', document.querySelector('[for="'+fieldName+'"]').innerText)
              field.help.parentNode.classList.add('has-error');
            } else {
              clearFieldValidations(field);
            }
          });

          return isValid;
        }

        var dropInOptions = {
          authorization: clientToken,
          container: '#bt-dropin',
          locale: locale,
          threeDSecure: true,
        };

        if(paypalEnabled == '1') {
          dropInOptions.paypal = {
            flow: 'checkout',
            amount: amount,
            currency: currencyName
          };
        }

        braintree.dropin.create(dropInOptions, function (createErr, instance) {

          button.addEventListener('click', function () {
            var billingIsValid = validateBillingFields();
            requestErrors.style.display = 'none';
            if (!billingIsValid) {
              enableSubmitButton();
              return;
            }

            disableSubmitButton();

            instance.requestPaymentMethod({
              threeDSecure: {
                amount: amount,
                email: billingFields.email.input.value,
                billingAddress: {
                  givenName: billingFields['billing-given-name'].input.value,
                  surname: billingFields['billing-surname'].input.value,
                  phoneNumber: billingFields['billing-phone'].input.value.replace(/[\(\)\s\-]/g, ''), // remove (), spaces, and - from phone number
                  streetAddress: billingFields['billing-street-address'].input.value,
                  locality: billingFields['billing-locality'].input.value,
                  region: billingFields['billing-region'].input.value,
                  postalCode: billingFields['billing-postal-code'].input.value,
                  countryCodeAlpha2: billingFields['billing-country-code'].input.value
        }
        }
        }, function (err, payload) {

        enableSubmitButton();

        if (err) {
        console.log(err);

        requestErrors.style.display = 'block';
        if(err.code === 'THREEDS_LOOKUP_VALIDATION_ERROR') {
          requestErrors.innerText = err.details.originalError.details.originalError.error.message
        } else {
          requestErrors.innerText = err.message
        }

        return;
        }

        if(payload) {
        disableSubmitButton();

        $.post(completePaymentUrl, {
        amount: amount,
        payment_method_nonce:payload.nonce,
        }).done(function(){
        window.location.href = invoiceUrl;
        })
        }
        });
          });

          instance.on('paymentMethodRequestable', function(){
            button.style.display = '';
          });

          instance.on('noPaymentMethodRequestable', function(){
            button.style.display = 'none';
          });
        });
     </script>
     <?php echo payment_gateway_footer();
     }
}
