<?php

defined('BASEPATH') or exit('No direct script access allowed');
// For Stripe Checkout
class Stripe_core
{
    protected $ci;

    protected $secretKey;

    protected $publishableKey;

    protected $apiVersion = '2020-03-02';

    /**
     * Initialize Stripe_core class
     */
    public function __construct()
    {
        $this->ci             = &get_instance();
        $this->secretKey      = $this->ci->stripe_gateway->decryptSetting('api_secret_key');
        $this->publishableKey = $this->ci->stripe_gateway->getSetting('api_publishable_key');

        \Stripe\Stripe::setApiVersion($this->apiVersion);
        \Stripe\Stripe::setApiKey($this->secretKey);
    }

    /**
     * Create new customer in strip
     *
     * @param  array $data
     *
     * @return \Stripe\Customer
     */
    public function create_customer($data)
    {
        return \Stripe\Customer::create($data);
    }

    /**
     * Retrieve customer
     *
     * @param  array|string $id
     *
     * @return \Stripe\Customer
     */
    public function get_customer($id)
    {
        return \Stripe\Customer::retrieve($id);
    }

    /**
     * Update customer
     *
     * @param  string $id
     * @param  array $payload
     *
     * @return \Stripe\Customer
     */
    public function update_customer($id, $payload)
    {
        return \Stripe\Customer::update($id, $payload);
    }

    /**
     * Get Stripe publishable key
     *
     * @return string|null
     */
    public function get_publishable_key()
    {
        return $this->publishableKey;
    }

    /**
     * List the created webhook endpoint for the current environment
     *
     * @return array
     */
    public function list_webhook_endpoints()
    {
        return \Stripe\WebhookEndpoint::all();
    }

    /**
     * Get the necessary Stripe integration webhook events
     *
     * @return array
     */
    public function get_webhook_events()
    {
        $events = [
            'checkout.session.completed',
            'invoice.payment_succeeded',
            'invoice.payment_action_required',
            'invoice.payment_failed',
            'customer.subscription.created',
            'customer.subscription.deleted',
            'customer.subscription.updated',
        ];

        return hooks()->apply_filters('stripe_webhook_events', $events);
    }

    /**
     * Get available Stripe tax rates
     *
     * @return array
     */
    public function get_tax_rates()
    {
        return \Stripe\TaxRate::all(['limit' => 100]);
    }

    /**
     * Retrieve tax rate by given id
     *
     * @param  array|string $id
     *
     * @return \Stripe\TaxRate
     */
    public function retrieve_tax_rate($id)
    {
        return \Stripe\TaxRate::retrieve($id);
    }

    /**
     * Create webhook in Stripe for the integration
     *
     * @return \Stripe\WebhookEndpoint
     */
    public function create_webhook()
    {
        $webhook = \Stripe\WebhookEndpoint::create([
            'url'            => $this->ci->stripe_gateway->webhookEndPoint,
            'enabled_events' => $this->get_webhook_events(),
            'api_version'    => $this->apiVersion,
        ]);

        update_option('stripe_webhook_id', $webhook->id);
        update_option('stripe_webhook_signing_secret', $webhook->secret);

        return $webhook;
    }

    /**
     * Enable webhook by given id
     *
     * @param  string $id
     *
     * @return void
     */
    public function enable_webhook($id)
    {
        \Stripe\WebhookEndpoint::update($id, [
            'disabled' => false,
          ]);
    }

    /**
     * Delete the given webhook
     *
     * @param  string $id
     *
     * @return void
     */
    public function delete_webhook($id)
    {
        $endpoint = \Stripe\WebhookEndpoint::retrieve($id);
        $endpoint->delete();
    }

    /**
     * Create new checkout session
     *
     * @param  array $data
     *
     * @return \Stripe\Checkout\Session
     */
    public function create_session($data)
    {
        return \Stripe\Checkout\Session::create($data);
    }

    /**
     * Retrieve checkout session
     *
     * @param  array|string $data
     *
     * @return \Stripe\Checkout\Session
     */
    public function retrieve_session($data)
    {
        return \Stripe\Checkout\Session::retrieve($data);
    }

    /**
     * Retrieve payment intent
     *
     * @param  array|string $data
     *
     * @return \Stripe\PaymentIntent
     */
    public function retrieve_payment_intent($data)
    {
        return \Stripe\PaymentIntent::retrieve($data);
    }

    /**
     * Retrieve payment method
     *
     * @param  array|string $data
     *
     * @return \Stripe\PaymentMethod
     */
    public function retrieve_payment_method($data)
    {
        return \Stripe\PaymentMethod::retrieve($data);
    }

    /**
     * Create constturct event
     *
     * @param  array $payload
     * @param  string $secret
     *
     * @return mixed
     */
    public function construct_event($payload, $secret)
    {
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

        return \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $secret
          );
    }

    /**
     * Check whether there is api key added for the integration
     *
     * @return boolean
     */
    public function has_api_key()
    {
        return $this->secretKey != '';
    }
}
