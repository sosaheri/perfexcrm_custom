<?php

defined('BASEPATH') or exit('No direct script access allowed');

class App_gateway
{
    /**
     * Whether the gateway is registered
     * Used when class is initialized more times to prevent registering again and again
     * E.q. When passed via register_payment_gateway(new Example());
     *
     * @since 2.3.4
     * @var boolean
     */
    protected static $registered = [];

    /**
     * Hold Codeigniter instance
     * @var object
     */
    protected $ci;

    /**
     * Stores the gateway id
     * @var alphanumeric
     */
    protected $id = '';

    /**
     * Gateway name
     * @var mixed
     */
    protected $name = '';

    /**
     * All gateway settings
     * @var array
     */
    protected $settings = [];

    /**
     * Must be called from the main gateway class that extends this class
     * @param alphanumeric $id   Gateway id - required
     * @param mixed $name Gateway name
     */
    public function __construct()
    {
        $this->ci = & get_instance();

        // App_gateway is not only autoloaded, has subclass
        if (method_exists($this, 'process_payment')) {
            hooks()->add_action('before_get_payment_gateways', [$this, 'tryToAutoRegisterPaymentGateway'], 11, 1);
        }
    }

    /**
     * Try to autoload the gateway
     * This function only works for autoloaded libraries,
     * NOTE: This does not work with modules, with modules, you must register via register_payment_gateway($gateway, $module_name);
     *
     * @since  2.3.4
     * @internal
     * @return null
     */
    public function tryToAutoRegisterPaymentGateway()
    {
        if (!in_array(static::fqcn(), static::$registered)) {
            register_payment_gateway($this, null);
        }
    }

    public function initMode($modes)
    {
        /**
         * Autoload the options defined below
         * Options are used over the system while working and it's necessary to be autoloaded for performance.
         * @var array
         */

        $autoload = [
            'label', 'default_selected', 'active',
        ];

        /**
         * Try to add the options if the gateway is first time added or is options page in admin area
         * May happen there is new options added so let the script re-check
         * add_option will not re-add the option if already exists
         */
        if (!$this->isInitialized() || $this->isOptionsPage()) {
            foreach ($this->settings as $option) {
                $val = isset($option['default_value']) ? $option['default_value'] : '';
                add_option('paymentmethod_' . $this->getId() . '_' . $option['name'], $val, (in_array($option['name'], $autoload) ? 1 : 0));
            }
            add_option('paymentmethod_' . $this->getId() . '_initialized', 1);
        }

        if (in_array(static::fqcn(), self::$registered)) {
            return $modes;
        }

        /**
         * Inject the mode with other modes with action hook
         */
        $modes[] = [
            'id'                  => $this->getId(),
            'name'                => $this->getSetting('label'),
            'description'         => '',
            'selected_by_default' => $this->getSetting('default_selected'),
            'active'              => $this->getSetting('active'),
            'instance'            => $this,
        ];

        self::$registered[] = static::fqcn();

        return $modes;
    }

    /**
     * Set gateway name
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Return gateway name
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set gateway id
     * @param string alphanumeric $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Return gateway id
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set gateway settings
     * @param array $settings
     */
    public function setSettings($settings)
    {

        /**
         * Append on top the dafault settings active and label
         */
        array_unshift(
            $settings,
            [
                'name'          => 'active',
                'type'          => 'yes_no',
                'default_value' => 0,
                'label'         => 'settings_paymentmethod_active',
                ],
            [
                'name'          => 'label',
                'default_value' => $this->getName(),
                'label'         => 'settings_paymentmethod_mode_label',
                ]
        );

        /**
         * Add on bottom default selected on invoice setting
         */
        $settings[] = [
            'name'          => 'default_selected',
            'type'          => 'yes_no',
            'default_value' => 1,
            'label'         => 'settings_paymentmethod_default_selected_on_invoice',
            ];

        $this->settings = $settings;
    }

    /**
     * Add payment based on payment method
     * @param array $data payment data
     * Params
     * amount - Required
     * invoiceid - Required
     * transactionid - Optional but recommended
     * paymentmethod - Optional
     * note - Optional
     */
    public function addPayment($data)
    {
        $data['paymentmode'] = $this->getId();
        $this->ci->load->model('payments_model');

        return $this->ci->payments_model->add($data);
    }

    /**
     * Get all gateway settings
     * @param  boolean $formatted Should the setting be formated like is on db or like it passed from the settings
     * @return array
     */
    public function getSettings($formatted = true)
    {
        $settings = $this->settings;
        if ($formatted) {
            foreach ($settings as $key => $option) {
                $settings[$key]['name'] = 'paymentmethod_' . $this->getId() . '_' . $option['name'];
            }
        }

        return $settings;
    }

    /**
     * Return single setting passed by name
     * @param  mixed $name Option name
     * @return string
     */
    public function getSetting($name)
    {
        return trim(get_option('paymentmethod_' . $this->getId() . '_' . $name));
    }

    /**
     * Decrypt setting value
     * @return string
     */
    public function decryptSetting($name)
    {
        return trim($this->ci->encryption->decrypt($this->getSetting($name)));
    }

    /**
     * Check if payment gateway is initialized and options are added into database
     * @return boolean
     */
    protected function isInitialized()
    {
        return $this->getSetting('initialized') == '' ? false : true;
    }

    /**
     * Check if is settings page in admin area
     * @return boolean
     */
    private function isOptionsPage()
    {
        return $this->ci->input->get('group') == 'payment_gateways' && $this->ci->uri->segment(2) == 'settings';
    }

    /**
     * Get Fully Qualified Class Name
     * @return string
     */
    public static function fqcn()
    {
        return static::class;
    }

    /**
     * @deprecated
     * @return string
     */
    public function get_id()
    {
        return $this->getId();
    }

    /**
     * @deprecated
     * @return array
     */
    public function get_settings()
    {
        return $this->getSettings();
    }

    /**
     * @deprecated
     * @return string
     */
    public function get_name()
    {
        return $this->getName();
    }

    /**
     * @deprecated
     * @return string
     */
    public function get_setting_value($name)
    {
        return $this->getSetting($name);
    }
}
