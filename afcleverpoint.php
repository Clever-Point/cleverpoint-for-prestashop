<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 *
 * @author    Afternet <info@afternet.gr>
 * @copyright Afternet
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once('classes/AfCleverPointDeliveryRequest.php');
require_once('classes/AfCleverPointDeliveryStation.php');
require_once('src/autoload.php');

use CleverPoint\Api;
use CleverPoint\Override;

class AfCleverPoint extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'afcleverpoint';
        $this->tab = 'payments_gateways';
        $this->tabClass = 'AdminAfCleverPoint';
        $this->version = '2.0.0';
        $this->author = 'Afternet';
        $this->need_instance = 1;
        $this->identifier = 'afcleverpoint';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->controllers = ['payment', 'validation'];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Clever Point');
        $this->description = $this->l('Clever Point for Prestashop 1.7');

        $this->limited_countries = array('GR');
        $this->limited_currencies = array('EUR');
    }

    public function isUsingNewtranslationSystem()
    {
        return false;
    }

    /**
     * Install Module
     *
     * @return bool
     */
    public function install()
    {
        if (
            !parent::install() ||
            !$this->registerHook('header') ||
            !$this->registerHook('displayAdminOrderLeft') ||
            !$this->registerHook('displayAdminOrderMain') ||
            !$this->registerHook('actionCarrierUpdate') ||
            !$this->registerHook('displayOrderConfirmation') ||
            !$this->registerHook('displayBackOfficeHeader') ||
            !$this->registerHook('displayBeforeCarrier') ||
            !$this->registerHook('actionGetExtraMailTemplateVars') ||
            !$this->registerHook('actionValidateOrder') ||
            !$this->registerHook('actionCarrierProcess') ||
            !$this->registerHook('actionCleverPointCartGetOrderTotal') ||
            !$this->registerHook('actionCleverPointOverrideCod') ||
            !$this->registerHook('displayPaymentReturn') ||
            !$this->registerHook('paymentOptions')
        ) {
            $this->_errors[] = $this->l('Unable to register hooks');
            return false;
        }

        if (!$this->installDbTables()) {
            $this->_errors[] = $this->l('Unable to create tables');
            return false;
        }

        if (!$this->installOverrides()) {
            $this->_errors[] = $this->l('Unable to add overrides');
            return false;
        }

        if (!$this->installTab()) {
            $this->_errors[] = $this->l('Unable to create tabs');
            return false;
        }

        return true;
    }

    /**
     * Uninstall module
     *
     * @return mixed
     */
    public function uninstall()
    {
        if (!$this->uninstallTab()) {
            $this->_errors[] = $this->l('Unable to uninstall tabs');
            return false;
        }

        if (!$this->uninstallOverrides()) {
            $this->_errors[] = $this->l('Unable to uninstall overrides');
            return false;
        }

        if (!parent::uninstall()) {
            $this->_errors[] = $this->l('Unable to uninstall');
            return false;
        }

        return true;
    }

    /**
     * Load SQL file and install module's tables
     *
     * @param $sql_file
     *
     * @return bool
     */
    public function loadSQLFile($sql_file)
    {
        $sql_content = file_get_contents($sql_file);
        $sql_content = str_replace('PREFIX_', _DB_PREFIX_, $sql_content);
        $sql_requests = preg_split("/;\s*[\r\n]+/", $sql_content);

        $result = true;
        foreach ($sql_requests as $request) {
            if (!empty($request)) {
                $result &= Db::getInstance()->execute(trim($request));
            }
        }

        return $result;
    }

    /**
     * Install necessary tables
     * @return bool
     */
    public function installDbTables()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."af_cleverpoint_delivery_request` (
                    `id_cleverpoint_delivery_request` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    `id_cart` int(11) DEFAULT 0,
                    `id_address_delivery` int(11) DEFAULT 0,
                    `previous_id_address_delivery` int(11) DEFAULT 0,
                    `id_order` int(11) DEFAULT 0,
                    `id_cleverpoint_delivery_station` int(11) DEFAULT 0,
                    `is_cod` int(1) NOT NULL DEFAULT 0,
                    `shipping_cost` decimal(20,6) DEFAULT 0.000000,
                    `service_cost` decimal(20,6) DEFAULT 0.000000,
                    `delivered` int(1) NOT NULL DEFAULT 0,
                    `deliver_order_with_cp` int(1) NOT NULL DEFAULT 0,
                    `ShipmentMasterId` varchar(100) DEFAULT NULL,
                    `ExternalCarrierId` varchar(100) DEFAULT NULL,
                    `ExternalCarrierName` varchar(50) DEFAULT NULL,
                    `ShipmentAwb` varchar(100) DEFAULT NULL,
                    `PickupComments` varchar(255) DEFAULT NULL,
                    `parcels` INT (11) DEFAULT 1,
                    `json_response` TEXT DEFAULT NULL,
                    `date_add` datetime NOT NULL,
                    `date_upd` datetime NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }

        $sql = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."af_cleverpoint_delivery_station` (
                `id_cleverpoint_delivery_station` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `StationId` varchar(50) DEFAULT NULL,
                `Prefix` varchar(50) DEFAULT NULL,
                `Code` varchar(50) DEFAULT NULL,
                `Name` varchar(255) NOT NULL,
                `Category` varchar(100) DEFAULT NULL,
                `ShortName` varchar(50) DEFAULT NULL,
                `AddressLine1` varchar(255) NOT NULL,
                `AddressLine2` varchar(255) DEFAULT NULL,
                `City` varchar(255) DEFAULT NULL,
                `Perfecture` varchar(50) DEFAULT NULL,
                `ZipCode` varchar(12) NOT NULL,
                `Phones` varchar(255) DEFAULT NULL,
                `Emails` varchar(255) DEFAULT NULL,
                `MaxDimension` decimal(20,6) DEFAULT NULL,
                `MaxWeight` decimal(20,6) DEFAULT NULL,
                `Schedule` varchar(255) DEFAULT NULL,
                `WorkHoursFormattedWithDaysV2` varchar(255) DEFAULT NULL,
                `ExtraVars` text DEFAULT NULL,
                `IsOperationalForCOD` INT(1) NOT NULL DEFAULT 1,
                `Lat` decimal(13,8) DEFAULT NULL,
                `Lng` decimal(13,8) DEFAULT NULL,
                `date_add` datetime NOT NULL,
                `date_upd` datetime NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }

        return true;
    }

    /**
     * Intall Tab
     *
     */
    public function installTab()
    {
        // Cleverpoint admin tab
        $new_tab = new Tab();
        $new_tab->active = 1;
        $new_tab->class_name = $this->tabClass;
        $new_tab->id_parent = Tab::getIdFromClassName('AdminParentOrders');
        $new_tab->module = $this->name;
        $new_tab->name[(int)(Configuration::get('PS_LANG_DEFAULT'))] = $this->l('Clever Point Orders');
        try {
            $new_tab->add();
            return true;
        } catch (Exception $e) {
            $this->_errors[] = $e->getMessage();
        }

        return false;
    }

    /**
     * Uninstall tab
     *
     * @param void
     * @return bool
     */
    public function uninstallTab()
    {
        // Retrieve Tab ID
        $id_tab = (int)Tab::getIdFromClassName($this->tabClass);
        // Load tab
        $tab = new Tab((int)$id_tab);

        // Delete it
        try {
            $tab->delete();
            return true;
        } catch (Exception $e) {
            $this->_errors[] = $e->getMessage();
        }

        return false;
    }

    /**
     * Display assets in front end
     *
     * @param $params
     */
    public function hookHeader($params)
    {
        $customer_address = null;

        if ($this->context->controller->php_self == 'order' && $this->isMethodAvailableForCart()) {

            if (!empty($this->context->cart->id_address_delivery)) {
                $delivery_address = new Address((int)$this->context->cart->id_address_delivery);
                $customer_address = isset($delivery_address->address1) ? $delivery_address->address1 : '';
                if (isset($delivery_address->postcode)) {
                    $customer_address .= ', '.$delivery_address->postcode;
                }
                if (isset($delivery_address->city)) {
                    $customer_address .= ', '.$delivery_address->city;
                }
            }

            // Onepagecheckout module
            $afcp_opc_enabled = $this->tcIsModuleEnabled();

            if ($afcp_opc_enabled) {
                $afcp_checkout_btn_selector = 'button#confirm_order';
                // Load thecheckout assets
                $this->tcLoadAssetsFront();
            } else {
                $afcp_checkout_btn_selector = 'form#js-delivery button[name="confirmDeliveryOption"]';
                $this->context->controller->registerStylesheet(
                    $this->name, 'modules/'.$this->name.'/views/css/front.css',
                    ['priority' => 150, 'version' => $this->version]
                );
            }

            $this->context->controller->registerJavascript(
                'modules'.$this->name.'-script',
                'modules/'.$this->name.'/views/js/clevermap.js',
                ['position' => 'bottom', 'priority' => 10, 'version' => $this->version]
            );

            if (Configuration::get('AFCP_SANDBOX')) {
                $this->context->controller->registerJavascript(
                    'modules'.$this->name.'-script-external',
                    'https://test.cleverpoint.gr/portal/content/clevermap_v2/script/cleverpoint-map.js',
                    ['position' => 'bottom', 'priority' => 150, 'server' => 'remote']
                );
            } else {
                $this->context->controller->registerJavascript(
                    'modules'.$this->name.'-script-external',
                    'https://platform.cleverpoint.gr/portal/content/clevermap_v2/script/cleverpoint-map.js',
                    ['position' => 'bottom', 'priority' => 150, 'server' => 'remote']
                );
            }

            $AFCP_HEADER = Configuration::get('AFCP_HEADER');
            $AFCP_SINGLE_SELECT = Configuration::get('AFCP_SINGLE_SELECT');
            $AFCP_ADDRESS_BAR = Configuration::get('AFCP_ADDRESS_BAR');
            $AFCP_POINT_LIST = Configuration::get('AFCP_POINT_LIST');

            // CleverPoint default carriers
            $AFCP_DEFAULT_CARRIER = Configuration::get('AFCP_DEFAULT_CARRIER');
            $afcp_carriers = unserialize(Configuration::get('AFCP_CARRIER_IDS'));
            if (empty($AFCP_DEFAULT_CARRIER)) {
                $afcp_default_cp_carrier = $afcp_carriers[0];
            } else {
                $afcp_default_cp_carrier = Db::getInstance()->getValue(
                    'SELECT `id_carrier` FROM `' . _DB_PREFIX_ . 'carrier`
			            WHERE id_reference = ' . (int) $AFCP_DEFAULT_CARRIER . ' AND deleted = 0 ORDER BY id_carrier DESC'
                );
            }

            Media::addJsDef(
                array(
                    'afcp_ajax_url' => $this->context->link->getModuleLink($this->name, 'ajax', array(), null),
                    'afcp_hash' => $this->generateHash(),
                    'afcp_id_cart' => $this->context->cart->id,
                    'afcp_header' => ($AFCP_HEADER ? true : false),
                    'afcp_single_select' => ($AFCP_SINGLE_SELECT ? true : false),
                    'afcp_address_bar' => ($AFCP_ADDRESS_BAR ? true : false),
                    'afcp_point_list' => ($AFCP_POINT_LIST ? true : false),
                    'afcp_info_type' => Configuration::get('AFCP_INFO_TYPE'),
                    'afcp_cleverpoint_api_key' => Configuration::get('AFCP_CLEVERPOINT_API_KEY'),
                    'afcp_googlemaps_api_key' => Configuration::get('AFCP_GOOGLEMAPS_API_KEY'),
                    'afcp_arcgis_api_key' => Configuration::get('AFCP_ARCGIS_API_KEY'),
                    'afcp_address_delivery' => $customer_address,
                    'afcp_display_method' => Configuration::get('AFCP_DISPLAY_METHOD'),
                    'afcp_msg_error_500' => $this->l('Service is unavailable please try again later.'),
                    'afcp_text_popup_btn' => $this->l('Change Clever point'),
                    'afcp_text_select_point' => $this->l('Select clever point in order to proceed.'),
                    'afcp_carriers' => $afcp_carriers,
                    // Which carriers are assigned to Clever Point?
                    'afcp_costtocust' => Configuration::get('AFCP_COSTTOCUST'),
                    'afcp_default_cp_carrier' => $afcp_default_cp_carrier,
                    'afcp_checkout_btn_selector' => $afcp_checkout_btn_selector,
                    'afcp_opc_enabled' => $afcp_opc_enabled
                )
            );
        }
    }
    ## BEGIN Configuration

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        return $this->run();
    }

    /**
     * Module configuration
     *
     * @return mixed
     */
    public function run()
    {
        $this->processConfiguration();

        // Get carriers that are set for Clever Point carriers
        $cp_carrier_ids = unserialize(Configuration::get('AFCP_CARRIER_IDS'));
        if (empty($cp_carrier_ids)) {
            $ps_carriers = [];
        } else {
            $ps_carriers = Carrier::getCarriers(
                $this->context->language->id,
                false,
                false,
                false,
                null,
                Carrier::ALL_CARRIERS
            );
            if (!empty($ps_carriers)) {
                foreach ($ps_carriers as $key => $carrier) {
                    if (!in_array($carrier['id_carrier'], $cp_carrier_ids)) {
                        unset($ps_carriers[$key]);
                    }
                }
            }
        }

        $afcp_module_url = Context::getContext()->link->getMediaLink(
            __PS_BASE_URI__.'modules/'.$this->name
        );

        $this->context->smarty->assign(
            'afcp_module_url',
            $afcp_module_url
        );

        $html_confirmation_messages = $this->display($this->_path, 'getContent.tpl');
        $this->context->smarty->assign(
            'tpl_vars',
            [
                'ps_carriers' => $ps_carriers,
                'cp_carriers' => $this->getCleverPointCarriers(),
                'carrier_mapping' => Tools::jsonDecode(
                    Configuration::get(
                        'AFCP_CARRIER_MAPPING'
                    ),
                    true
                ),
                'afcp_module_url' => $afcp_module_url,
                // Check if thecheckout module is enabled
                'opc_is_enabled' => $this->tcIsModuleEnabled(),
                // Module thecheckout compatibility
                'opc_compatibility' => $this->tcVersionCompatibility()
            ]
        );

        // Optionally you may load another tpl in footer
        $footer_tpl = $this->display($this->_path, 'getContentFooter.tpl');

        return $html_confirmation_messages.$this->renderForm().$footer_tpl.$this->renderFormCategories();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    public function renderForm()
    {
        $fields_values = $this->getConfigFormValues();

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitAfCleverPointModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $fields_values, /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm($this->getConfigForm($fields_values));
    }

    /**
     * Configuration form for Categories
     *
     * @return mixed
     */
    public function renderFormCategories()
    {
        $afcp_id_categories_exclude = [];
        $AFCP_CATEGORY_IDS = explode(',', Configuration::get('AFCP_CATEGORY_IDS'));
        if (!empty($AFCP_CATEGORY_IDS)) {
            foreach ($AFCP_CATEGORY_IDS as $id_category) {
                if (!empty($id_category)) {
                    $afcp_id_categories_exclude[] = sprintf(
                        '%d:%s',
                        $id_category,
                        strip_tags(
                            Tools::getPath(
                                '',
                                $id_category
                            )
                        )
                    );
                }
            }
        }

        $fields_values = [
            'AFCP_CATEGORY_IDS' => implode(',', $afcp_id_categories_exclude),
            'AFCP_CAT_EXCL_TYPE' => Configuration::get('AFCP_CAT_EXCL_TYPE'),
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitAfCleverPointModuleCategories';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $fields_values, /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm($this->getConfigFormCategories($fields_values));
    }

    /**
     * Configuration structure form.
     *
     * @param array
     *
     * @return array
     */
    public function getConfigForm($fields_values)
    {
        $display_map_options = [
            ['id_option' => 'popup', 'name' => $this->l('Modal')],
            ['id_option' => 'embed', 'name' => $this->l('Embed')],
        ];

        $info_type_options = [
            ['id_option' => 'docked', 'name' => $this->l('Docked')],
            ['id_option' => 'floating', 'name' => $this->l('Floating')],
            ['id_option' => 'dockedSmall', 'name' => $this->l('Docked (small)')],
        ];

        $voucher_management = [
            ['id_option' => 'prestashop', 'name' => $this->l('Prestashop')],
            ['id_option' => 'platform', 'name' => $this->l('Platform')],
        ];

        $payments = Module::getPaymentModules();
        $all_payments = [];
        foreach ($payments as $payment) {
            $all_payments[$payment['id_module']] = [];
            $all_payments[$payment['id_module']]['name'] = $payment['name'];
            $all_payments[$payment['id_module']]['module'] = $payment['name'];
        }

        $carriers = Carrier::getCarriers(
            $this->context->language->id,
            false,
            false,
            false,
            null,
            Carrier::ALL_CARRIERS
        );

        $cp_carriers = [];
        if (!empty($fields_values['AFCP_CARRIER_IDS'])) {
            if (!empty($carriers)) {
                foreach ($carriers as $carrier) {
                    if (in_array($carrier['id_carrier'], $fields_values['AFCP_CARRIER_IDS'])) {
                        $cp_carriers[] = $carrier;
                    }
                }
            }
        }

        if (empty($cp_carriers)) {
            $cp_carriers = [
                ['id_reference' => 0, 'name' => $this->l('-- Select --')]
            ];
        }

        // Get available order states
        $order_states = array_merge([['id_order_state' => 0, 'name' => $this->l('-- Select --')]],
            OrderState::getOrderStates($this->context->language->id));

        return
            array(
                array(
                    'form' =>
                        array(
                            'legend' => array(
                                'title' => $this->l('Clever Point Appearance Settings'),
                                'icon' => 'icon-cogs',
                            ),
                            'input' => array(
                                array(
                                    'col' => 6,
                                    'type' => 'text',
                                    'name' => 'AFCP_CLEVERPOINT_API_KEY',
                                    'label' => $this->l('Clever Point API KEY'),
                                ),
                                array(
                                    'col' => 6,
                                    'type' => 'text',
                                    'name' => 'AFCP_GOOGLEMAPS_API_KEY',
                                    'label' => $this->l('Google Maps API KEY'),
                                ),
                                array(
                                    'col' => 6,
                                    'type' => 'text',
                                    'name' => 'AFCP_ARCGIS_API_KEY',
                                    'label' => $this->l('ArcGIS API KEY'),
                                ),
                                array(
                                    'type' => 'select',
                                    'label' => $this->l('Trigger'),
                                    'name' => 'AFCP_DISPLAY_METHOD',
                                    //'desc' => $this->l('Select display method for checkout.'),
                                    'required' => true,
                                    'multiple' => false,
                                    'options' => array(
                                        'query' => $display_map_options,
                                        'id' => 'id_option',
                                        'name' => 'name',
                                    ),
                                ),
//                                array(
//                                    'col' => 6,
//                                    'type' => 'text',
//                                    'name' => 'AFCP_MAP_WIDTH',
//                                    'label' => $this->l('Map width (in px)'),
//                                ),
                                array(
                                    'type' => 'text',
                                    'name' => 'AFCP_MAP_HEIGHT',
                                    'label' => $this->l('Map height (in px)'),
                                    'class' => 'fixed-width-md'
                                ),
                                array(
                                    'type' => 'switch',
                                    'label' => $this->l('Header'),
                                    'name' => 'AFCP_HEADER',
                                    'is_bool' => true,
                                    'desc' => $this->l('Header on map.'),
                                    'values' => array(
                                        array(
                                            'id' => 'AFCP_HEADER_1',
                                            'value' => 1,
                                            'label' => $this->l('Display'),
                                        ),
                                        array(
                                            'id' => 'AFCP_HEADER_0',
                                            'value' => 0,
                                            'label' => $this->l('Hide'),
                                        ),
                                    ),
                                ),
                                array(
                                    'type' => 'switch',
                                    'label' => $this->l('Single Select'),
                                    'name' => 'AFCP_SINGLE_SELECT',
                                    'is_bool' => true,
                                    'desc' => $this->l('Single select on map.'),
                                    'values' => array(
                                        array(
                                            'id' => 'AFCP_SINGLE_SELECT_1',
                                            'value' => 1,
                                            'label' => $this->l('Enable'),
                                        ),
                                        array(
                                            'id' => 'AFCP_SINGLE_SELECT_0',
                                            'value' => 0,
                                            'label' => $this->l('Disable'),
                                        ),
                                    ),
                                ),
                                array(
                                    'type' => 'switch',
                                    'label' => $this->l('Address Bar'),
                                    'name' => 'AFCP_ADDRESS_BAR',
                                    'is_bool' => true,
                                    'desc' => $this->l('Display address bar on map.'),
                                    'values' => array(
                                        array(
                                            'id' => 'AFCP_ADDRESS_BAR_1',
                                            'value' => 1,
                                            'label' => $this->l('Display'),
                                        ),
                                        array(
                                            'id' => 'AFCP_ADDRESS_BAR_0',
                                            'value' => 0,
                                            'label' => $this->l('Hide'),
                                        ),
                                    ),
                                ),
                                array(
                                    'type' => 'switch',
                                    'label' => $this->l('Point List'),
                                    'name' => 'AFCP_POINT_LIST',
                                    'is_bool' => true,
                                    'desc' => $this->l('Display point list on map.'),
                                    'values' => array(
                                        array(
                                            'id' => 'AFCP_POINT_LIST_1',
                                            'value' => 1,
                                            'label' => $this->l('Display'),
                                        ),
                                        array(
                                            'id' => 'AFCP_POINT_LIST_0',
                                            'value' => 0,
                                            'label' => $this->l('Hide'),
                                        ),
                                    ),
                                ),
                                array(
                                    'type' => 'select',
                                    'label' => $this->l('Info Type'),
                                    'name' => 'AFCP_INFO_TYPE',
                                    'desc' => $this->l('Display type of point info on map.'),
                                    'required' => true,
                                    'multiple' => false,
                                    'options' => array(
                                        'query' => $info_type_options,
                                        'id' => 'id_option',
                                        'name' => 'name',
                                    ),
                                ),
                            ),
                            'submit' => array(
                                'title' => $this->l('Save'),
                            ),
                        ),
                ),
                array(
                    'form' =>
                        array(
                            'legend' => array(
                                'title' => $this->l('Clever Point Settings'),
                                'icon' => 'icon-cogs',
                            ),
                            'input' => array(
                                array(
                                    'type' => 'select',
                                    'label' => $this->l('Voucher management method'),
                                    'name' => 'AFCP_VOUCHER_MANAGEMENT',
                                    'required' => true,
                                    'multiple' => false,
                                    'options' => array(
                                        'query' => $voucher_management,
                                        'id' => 'id_option',
                                        'name' => 'name',
                                    ),
                                ),
                                array(
                                    'type' => 'switch',
                                    'label' => $this->l('Charges'),
                                    'name' => 'AFCP_COSTTOCUST',
                                    'is_bool' => true,
                                    'desc' => $this->l('Add service fee as order fee'),
                                    'values' => array(
                                        array(
                                            'id' => 'AFCP_COSTT0CUST_1',
                                            'value' => 1,
                                            'label' => $this->l('Yes'),
                                        ),
                                        array(
                                            'id' => 'AFCP_COSTT0CUST_0',
                                            'value' => 0,
                                            'label' => $this->l('No'),
                                        ),
                                    ),
                                ),
                                array(
                                    'type' => 'switch',
                                    'label' => $this->l('Sandbox'),
                                    'name' => 'AFCP_SANDBOX',
                                    'is_bool' => true,
                                    'desc' => $this->l('Enable Sandbox mode.'),
                                    'values' => array(
                                        array(
                                            'id' => 'AFCP_MODE_1',
                                            'value' => 1,
                                            'label' => $this->l('Yes'),
                                        ),
                                        array(
                                            'id' => 'AFCP_MODE_0',
                                            'value' => 0,
                                            'label' => $this->l('No'),
                                        ),
                                    ),
                                ),
                            ),
                            'submit' => array(
                                'title' => $this->l('Save'),
                            ),
                        ),
                ),
                array(
                    'form' =>
                        array(
                            'legend' => array(
                                'title' => $this->l('Prestashop Settings'),
                                'icon' => 'icon-cogs',
                            ),
                            'input' => array(
                                array(
                                    'type' => 'select',
                                    'label' => $this->l('Cash on delivery'),
                                    'name' => 'AFCP_COD_MODULE[]',
                                    'desc' => $this->l('Select cash on delivery methods.'),
                                    'required' => true,
                                    'multiple' => true,
                                    'options' => array(
                                        'query' => $all_payments,
                                        'id' => 'name',
                                        'name' => 'name',
                                    ),
                                ),
                                array(
                                    'type' => 'select',
                                    'label' => $this->l('Carriers'),
                                    'name' => 'AFCP_CLEVERPOINT_CARRIER[]',
                                    'desc' => $this->l('Select Clever Point carriers.'),
                                    'required' => true,
                                    'multiple' => true,
                                    'options' => array(
                                        'query' => $carriers,
                                        'id' => 'id_reference',
                                        'name' => 'name',
                                    ),
                                ),
                                array(
                                    'type' => 'select',
                                    'label' => $this->l('Default carrier'),
                                    'name' => 'AFCP_DEFAULT_CARRIER',
                                    'desc' => $this->l('Select the default carrier that will be selected when a customer selects CleverPoint pickup. If no option displayed select Clever Point carriers from the option above and press Save.'),
                                    'required' => true,
                                    'multiple' => false,
                                    'options' => array(
                                        'query' => $cp_carriers,
                                        'id' => 'id_reference',
                                        'name' => 'name',
                                    ),
                                ),
                                array(
                                    'type' => 'select',
                                    'label' => $this->l('Initial order status'),
                                    'name' => 'AFCP_CLEVERPOINT_OS',
                                    'desc' => $this->l('Initial status when an order is placed with COD.'),
                                    'required' => true,
                                    'multiple' => false,
                                    'options' => array(
                                        'query' => $order_states,
                                        'id' => 'id_order_state',
                                        'name' => 'name',
                                    ),
                                ),
                            ),
                            'submit' => array(
                                'title' => $this->l('Save'),
                            ),
                        ),
                )
            );
    }

    /**
     * Configuration structure form.
     *
     * @param array
     *
     * @return array
     */
    public function getConfigFormCategories($fields_values)
    {
        return
            array(
                array(
                    'form' =>
                        array(
                            'legend' => array(
                                'title' => $this->l('Product Categories'),
                                'icon' => 'icon-cogs',
                            ),
                            'input' => array(
                                array(
                                    'type' => 'switch',
                                    'label' => $this->l('Display/ Hide'),
                                    'name' => 'AFCP_CAT_EXCL_TYPE',
                                    'is_bool' => true,
                                    'desc' => $this->l(
                                        'Select whether you would like to display or hide Clever Point in specified categories.'
                                    ),
                                    'values' => array(
                                        array(
                                            'id' => 'AFCP_CAT_EXCL_TYPE_1',
                                            'value' => 1,
                                            'label' => $this->l('Display'),
                                        ),
                                        array(
                                            'id' => 'AFCP_CAT_EXCL_TYPE_0',
                                            'value' => 0,
                                            'label' => $this->l('Hide'),
                                        ),
                                    ),
                                ),
                                array(
                                    'col' => 6,
                                    'type' => 'text',
                                    'name' => 'AFCP_CATEGORY_IDS',
                                    'label' => $this->l('Select categories'),
                                ),
                            ),
                            'submit' => array(
                                'title' => $this->l('Save'),
                            ),
                        ),
                ),
            );
    }

    /**
     * Set values for the inputs.
     */
    public function getConfigFormValues()
    {
        $values = array();
        $config_values = $this->getConfigDefaultValues();
        $languages = Language::getLanguages(false);

        foreach ($config_values as $key => $options) {

            $values[$key] = null;

            // Check if field is file
            if ($options['type'] == 'file') {

                $db_value = Configuration::get($key);

                // Check if file exists
                if (
                    !empty($db_value) &&
                    file_exists(self::getUploadDir($db_value))
                ) {
                    $image_url = self::getUploadDirUrl($db_value).'?time='.strtotime(date('Y-m-d H:i:s'));
                    $image = '<div class="col-lg-6"><img src="'.$image_url.'" class="img-thumbnail" width="400"></div>';
                    $values[$key] = $image;
                }
            }

            // Check if field is multilingual
            if (isset($options['lang']) && $options['lang']) {
                $values[$key] = $options['default'];
                foreach ($languages as $lang) {
                    $values[$key][$lang['id_lang']] =
                        Configuration::get(
                            $key,
                            $lang['id_lang']
                        );
                }
            }

            // Simple field
            if (empty($values[$key])) {

                if (is_array($options['default'])) {

                    $db_value = unserialize(Configuration::get($key));

                    if (empty($db_value)) {
                        $db_value = $options['default'];
                    }

                    $values[$key.'[]'] = $db_value;
                    $values[$key] = $db_value;

                } else {
                    $values[$key] =
                        Configuration::get(
                            $key,
                            null,
                            null,
                            null,
                            $options['default']
                        );
                }
            }
        }

        return $values;
    }

    /**
     * Get module's default values
     *
     * @param void
     *
     * @return array
     */
    public function getConfigDefaultValues()
    {
        $languages = Language::getLanguages(false);
        $lang_ids = array();
        foreach ($languages as $lang) {
            $lang_ids[$lang['id_lang']] = null;
        }

        return array(
            'AFCP_SANDBOX' =>
                array(
                    'type' => 'switch',
                    'default' => false,
                ),
            'AFCP_COSTTOCUST' =>
                array(
                    'type' => 'switch',
                    'default' => false,
                ),
            'AFCP_CLEVERPOINT_API_KEY' =>
                array(
                    'type' => 'text',
                    'default' => false,
                ),
            'AFCP_GOOGLEMAPS_API_KEY' =>
                array(
                    'type' => 'text',
                    'default' => false,
                ),
            'AFCP_ARCGIS_API_KEY' =>
                array(
                    'type' => 'text',
                    'default' => false,
                ),
            'AFCP_COD_MODULE' =>
                array(
                    'type' => 'select',
                    'default' => ['afcleverpoint', 'ps_cashondelivery', 'codfee'],
                ),
            'AFCP_CLEVERPOINT_CARRIER' =>
                array(
                    'type' => 'select',
                    'default' => [],
                ),
            'AFCP_CARRIER_IDS' =>
                array(
                    'type' => 'select',
                    'default' => array(),
                ),
            'AFCP_DEFAULT_CARRIER' =>
                array(
                    'type' => 'select',
                    'default' => null,
                ),
            'AFCP_DISPLAY_METHOD' =>
                array(
                    'type' => 'select',
                    'default' => 'popup',
                ),
            'AFCP_MAP_WIDTH' =>
                array(
                    'type' => 'text',
                    'default' => 500,
                ),
            'AFCP_MAP_HEIGHT' =>
                array(
                    'type' => 'text',
                    'default' => 500,
                ),
            'AFCP_HEADER' =>
                array(
                    'type' => 'switch',
                    'default' => false,
                ),
            'AFCP_SINGLE_SELECT' =>
                array(
                    'type' => 'switch',
                    'default' => false,
                ),
            'AFCP_ADDRESS_BAR' =>
                array(
                    'type' => 'switch',
                    'default' => false,
                ),
            'AFCP_POINT_LIST' =>
                array(
                    'type' => 'switch',
                    'default' => false,
                ),
            'AFCP_INFO_TYPE' =>
                array(
                    'type' => 'select',
                    'default' => 'docked',
                ),
            'AFCP_VOUCHER_MANAGEMENT' =>
                array(
                    'type' => 'select',
                    'default' => 'prestashop',
                ),
            // id_country for GR
            'AFCP_GR_ID_COUNTRY' =>
                [
                    'type' => 'text',
                    'default' => 0,
                ],
            // id_state for Attika
            'AFCP_GR_ID_STATE' =>
                [
                    'type' => 'text',
                    'default' => 0,
                ],
            'AFCP_CARRIER_MAPPING' =>
                [
                    'type' => 'text',
                    'default' => null,
                ],
            'AFCP_CATEGORY_IDS' =>
                [
                    'type' => 'text',
                    'default' => null,
                ],
            'AFCP_CAT_EXCL_TYPE' =>
                array(
                    'type' => 'switch',
                    'default' => false,
                ),
            'AFCP_CLEVERPOINT_OS' =>
                array(
                    'type' => 'select',
                    'default' => '',
                )
        );
    }

    /**
     * Handle form
     *
     */
    public function processConfiguration()
    {
        $errors = [];

        if (Tools::isSubmit('submitAfCleverPointModule')) {

            // Handle simple fields
            $this->handleSimpleFields($errors);

            // Handle multilingual fields
            $this->handleLangFields($errors);

            // Handle files
            $this->handleFileFields($errors);

            // Update carrier ids based on id_references
            if (!$this->updateCarrierIds()) {
                $errors[] = $this->l('Unable to save carriers.');
            }

            if (empty($errors)) {
                Tools::redirectAdmin(
                    $this->context->link->getAdminLink('AdminModules', true).
                    '&conf=6&configure='.$this->name.'&tab_module='.
                    $this->tab.'&module_name='.
                    $this->name
                );
            } else {
                $this->context->smarty->assign('errors', $errors);
            }
        }

        // Save category data
        if (Tools::isSubmit('submitAfCleverPointModuleCategories')) {

            $AFCP_CATEGORY_IDS_EXCL = [];

            $explode = explode(',', Tools::getValue('AFCP_CATEGORY_IDS'));
            if (!empty($explode)) {
                foreach ($explode as $category_data) {
                    if (!empty($category_data)) {
                        $exp = explode(':', $category_data);
                        if (!empty($exp)) {
                            $AFCP_CATEGORY_IDS_EXCL[] = (int)$exp[0];
                        }
                    }
                }
            }

            if (
                !Configuration::updateValue(
                    'AFCP_CATEGORY_IDS',
                    implode(',', $AFCP_CATEGORY_IDS_EXCL)
                ) ||
                !Configuration::updateValue(
                    'AFCP_CAT_EXCL_TYPE',
                    Tools::getValue('AFCP_CAT_EXCL_TYPE')
                )
            ) {
                $errors = $this->l('Unable to save settings please try again.');
            }

            if (empty($errors)) {
                Tools::redirectAdmin(
                    $this->context->link->getAdminLink('AdminModules', true).
                    '&conf=6&configure='.$this->name.'&tab_module='.
                    $this->tab.'&module_name='.
                    $this->name
                );
            } else {
                $this->context->smarty->assign('errors', $errors);
            }
        }
    }

    /**
     * Get simple fields
     *
     * @param void
     *
     * @return array
     */
    public function getSimpleFields()
    {
        $fields = array();

        $default_values = $this->getConfigDefaultValues();

        foreach ($default_values as $key => $options) {
            if (
                (isset($options['lang']) && !$options['lang']) ||
                $options['type'] != 'file'
            ) {
                $fields[$key] = $options;
            }
        }

        return $fields;
    }

    /**
     * Get multilingual fields
     *
     * @param void
     *
     * @return array
     */
    public function getLangFields()
    {
        $lang_fields = array();

        $default_values = $this->getConfigDefaultValues();

        foreach ($default_values as $key => $options) {
            if (isset($options['lang']) && $options['lang']) {
                $lang_fields[$key] = $options;
            }
        }

        return $lang_fields;
    }

    /**
     * Get file fields
     *
     * @param void
     *
     * @return array
     */
    public function getFileFields()
    {
        $file_fields = array();

        $default_values = $this->getConfigDefaultValues();

        foreach ($default_values as $key => $options) {
            if ($options['type'] == 'file') {
                $file_fields[$key] = $options;
            }
        }

        return $file_fields;
    }

    /**
     * Handle simple fields
     *
     * @param $errors
     *
     * @return bool
     */
    public function handleSimpleFields(&$errors)
    {
        $handler_errors = 0;

        $simple_fields = $this->getSimpleFields();

        if (!empty($simple_fields)) {
            foreach ($simple_fields as $key => $options) {
                if (is_array($options['default'])) {

                    $field_value = Tools::getValue($key);

                    if (!is_array($field_value)) {
                        $field_value = array($field_value);
                    }

                    if (!Configuration::updateValue(
                        $key,
                        serialize(Tools::getValue($key))
                    )) {
                        $errors[] =
                            sprintf(
                                $this->l('Unable to save option: %s'),
                                $key
                            );
                        $handler_errors++;
                    }

                } else {
                    if (!Configuration::updateValue(
                        $key,
                        Tools::getValue($key)
                    )) {
                        $errors[] =
                            sprintf(
                                $this->l('Unable to save option: %s'),
                                $key
                            );
                        $handler_errors++;
                    }
                }
            }
        }

        $status = empty($handler_errors);

        return $status;
    }

    /**
     * Handle multilingual fields
     *
     * @param $errors
     *
     * @return bool
     */
    public function handleLangFields(&$errors)
    {
        $handler_errors = 0;

        $lang_fields = $this->getLangFields();

        if (!empty($lang_fields)) {

            $languages = Language::getLanguages(false);

            foreach ($lang_fields as $key => $options) {

                $field_value = array();

                foreach ($languages as $lang) {
                    $field_value[$lang['id_lang']] =
                        Tools::getValue($key.'_'.$lang['id_lang']);
                }

                if (!Configuration::updateValue(
                    $key,
                    $field_value
                )) {
                    $errors[] =
                        sprintf(
                            $this->l('Unable to save option: %s'),
                            $key
                        );
                    $handler_errors++;
                }
            }
        }

        $status = empty($handler_errors);

        return $status;
    }

    /**
     * Handle file fields
     *
     * @param object
     *
     * @return boolean
     */
    public function handleFileFields(&$errors)
    {
        $handler_errors = 0;

        $file_fields = $this->getFileFields();

        if (!empty($file_fields)) {

            foreach ($file_fields as $field => $file_data) {

                if (
                    isset($_FILES[$field]['tmp_name']) &&
                    !empty($_FILES[$field]['tmp_name'])
                ) {

                    if (empty($_FILES[$field]['error'])) {

                        // Remove current file if any
                        $current_value = Configuration::get($field);
                        if (
                            $current_value &&
                            file_exists(self::getUploadDir($current_value))
                        ) {
                            if (!unlink(self::getUploadDir($current_value))) {

                                $errors[] = $this->l('Unable to delete current file.');
                                $handler_errors++;

                                return false;
                            }
                        }

                        // Get uploaded file information
                        $pathinfo = pathinfo($_FILES[$field]['name']);

                        $file_extension = strtolower($pathinfo['extension']);

                        if (in_array($file_extension, $file_data['allowed_extensions'])) {

                            $destination_filename =
                                sprintf(
                                    '%s_%s.%s',
                                    $file_data['prefix'],
                                    $pathinfo['filename'],
                                    $file_extension
                                );

                            // The destination path to be uploaded
                            $destination_path = self::getUploadDir($destination_filename);

                            if (!file_exists($destination_path)) {

                                if (move_uploaded_file($_FILES[$field]['tmp_name'], $destination_path)) {
                                    // Update value
                                    if (!Configuration::updateValue($field, $destination_filename)) {
                                        $errors[] = sprintf(
                                            $this->l('Unable to save file: %s to db.'),
                                            $_FILES[$field]['name']
                                        );
                                        $handler_errors++;
                                    }
                                } else {
                                    $errors[] = sprintf($this->l('Unable to upload file: %s'), $_FILES[$field]['name']);
                                    $handler_errors++;
                                }

                            } else {
                                $errors[] = $this->l('Unable to upload file. Please try again.');
                                $handler_errors++;
                            }

                        } else {
                            $errors[] =
                                sprintf(
                                    $this->l('File: %s has invalid extension. Allowed extensions: %s'),
                                    $_FILES[$field]['name'],
                                    implode(',', $file_data['allowed_extensions'])
                                );
                            $handler_errors++;
                        }

                    } else {
                        $errors[] =
                            sprintf(
                                $this->l('#1150 - There was an error while uploading file: %s. Error: %s'),
                                $this->uploadErrorCodeToMessage($_FILES[$field]['error']),
                                implode(',', error_get_last())
                            );
                        $handler_errors++;
                    }
                }
            }
        }

        $status = empty($handler_errors);

        return $status;
    }

    /**
     * Get upload error message
     *
     * @param $code
     *
     * @return mixed|string
     */
    public function uploadErrorCodeToMessage($code)
    {
        $phpFileUploadErrors = array(
            0 => $this->l('There is no error, the file uploaded with success'),
            1 => $this->l('The uploaded file exceeds the upload_max_filesize directive in php.ini'),
            2 => $this->l('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'),
            3 => $this->l('The uploaded file was only partially uploaded'),
            4 => $this->l('No file was uploaded'),
            6 => $this->l('Missing a temporary folder'),
            7 => $this->l('Failed to write file to disk.'),
            8 => $this->l('A PHP extension stopped the file upload.'),
        );

        return (isset($phpFileUploadErrors[$code]) ? $phpFileUploadErrors[$code] : "");
    }
    ## END Configuration


    /**
     * Save carrier ids
     * @return boolean
     */
    public function updateCarrierIds()
    {
        $AFCP_CLEVER_POINT_CARRIER_ID_REF = unserialize(Configuration::get('AFCP_CLEVERPOINT_CARRIER'));
        $cleverpoint_references = [];
        if (!empty($AFCP_CLEVER_POINT_CARRIER_ID_REF)) {
            foreach ($AFCP_CLEVER_POINT_CARRIER_ID_REF as $carrier_id) {
                $carrier = new Carrier ((int)$carrier_id);
                $cleverpoint_references[] = (int)$carrier->id_reference;
            }
            $id_carriers = [];
            $sql = "SELECT `id_carrier` FROM `"._DB_PREFIX_."carrier` WHERE `id_reference` IN (".implode(
                    ',',
                    $cleverpoint_references
                ).")";
            $res = Db::getInstance()->executeS($sql);
            if (!empty($res)) {
                foreach ($res as $row) {
                    $id_carriers[] = $row['id_carrier'];
                }
                Configuration::updateValue('AFCP_CARRIER_IDS', serialize($id_carriers));
            }
        }

        return true;
    }

    /**
     * Get template dir
     *
     * @param $template
     * @return string
     */
    public function getTplDir($template)
    {
        if (file_exists(_PS_THEME_DIR_."modules/{$this->name}/{$template}")) {
            return _PS_THEME_DIR_."modules/{$this->name}/";
        } else {
            return _PS_MODULE_DIR_."{$this->name}/";
        }
    }

    /**
     * Get template path
     *
     * @param $template
     * @return string
     */
    public function getTplPath($template)
    {
        return sprintf('%s/%s', $this->getTplDir($template), $template);
    }

    /**
     * Generate security hashes
     *
     * @param string
     * @return string
     */
    public function generateHash($string = null)
    {
        $s = "%s%s%s%s";

        return
            md5(
                sprintf(
                    $s,
                    $string,
                    $this->name,
                    _COOKIE_KEY_,
                    Tools::getRemoteAddr()
                )
            );
    }

    /**
     * Compatibility with PS 1.6
     *
     * @param $id
     * @param array $parameters
     * @param $domain
     * @param $locale
     * @return mixed
     */
    public function translate($id, array $parameters = [], $domain = null, $locale = null)
    {
        $messages = array(
            'Invalid request.' => $this->l('Invalid request.'),
            'Invalid method.' => $this->l('Invalid method.'),
            'Method does not exist.' => $this->l('Method does not exist.'),
            'Clever point details saved successfully.' => $this->l('Clever point details saved successfully.'),
            'Unable to save clever point details please try again.' => $this->l(
                'Unable to save clever point details please try again.'
            ),
            'Error occurred please try again.' => $this->l('Error occurred please try again.'),
            'Service cost' => $this->l('Service cost'),
            'Clever Point cost' => $this->l('Clever Point cost'),
            'Invalid delivery request.' => $this->l('Invalid delivery request.'),
            'Invalid order.' => $this->l('Invalid order.'),
            'This order is not assigned to specified Delivery Request.' => $this->l(
                'This order is not assigned to specified Delivery Request.'
            ),
            'Unable to save Delivery Request Data: %s.' => $this->l('Unable to save Delivery Request Data: %s.'),
            'Unable to save Delivery Request Data.' => $this->l('Unable to save Delivery Request Data.'),
            'Unable to load order shipping data.' => $this->l('Unable to load order shipping data.'),
            'Voucher created successfully.' => $this->l('Voucher created successfully.'),
            'Empty ShipmentAwb.' => $this->l('Empty ShipmentAwb.'),
            ' Please select print type.' => $this->l(' Please select print type.'),
            'Clever point details updated successfully.' => $this->l('Clever point details updated successfully.'),
            'Unable to complete request. Please try again later.' => $this->l(
                'Unable to complete request. Please try again later.'
            ),
            'Unable to get Clever Point details. Please try again later.' => $this->l(
                'Unable to get Clever Point details. Please try again later.'
            ),
            'Unable to save Clever Point details. Please try again later.' => $this->l(
                'Unable to save Clever Point details. Please try again later.'
            ),
            'Please select a clever point in order to proceed.' => $this->l(
                'Please select a clever point in order to proceed.'
            ),
            'Invalid request' => $this->l('Invalid request'),
            'Clever point details cleared successfully.' => $this->l('Clever point details cleared successfully.'),
            'Unable to clear clever point details please try again.' => $this->l(
                'Unable to clear clever point details please try again.'
            ),
            'Option saved successfully.' => $this->l('Option saved successfully.'),
            'Unable to save option please try again.' => $this->l('Unable to save option please try again.'),
            'Cost saved successfully.' => $this->l('Cost saved successfully.'),
            'Unable to save cost please try again.' => $this->l('Unable to save cost please try again.'),
            'Please select courier.' => $this->l('Please select courier.'),
            'Please enter ShipmentAwb.' => $this->l('Please enter ShipmentAwb.'),
            'Voucher cancelled successfully.' => $this->l('Voucher cancelled successfully.'),
            'Carriers saved successfully' => $this->l('Carriers saved successfully'),
            'Categories saved successfully.' => $this->l('Categories saved successfully.'),
            'Cash on delivery' => $this->l('Cash on delivery'),
            'Carriers refresh success' => $this->l('Carriers refresh success'),
            'Found no Clever Point carriers' => $this->l('Found no Clever Point carriers'),
            'Script installed successfully' => $this->l('Script installed successfully'),

        );

        return (isset($messages[$id]) ? $messages[$id] : $id);
    }

    /**
     * Check if PS version is 1.6
     * @return bool
     */
    public function is16()
    {
        return (Tools::substr(_PS_VERSION_, 0, 3) === '1.6');
    }

    /**
     * Check if $module is cash on delivery
     * @param $module
     * @param $cod_modules
     * @return boolean
     */
    public function isCod($module, $cod_modules = [])
    {
        if (!empty($cod_modules)) {
            $modules = $cod_modules;
        } else {
            $modules = unserialize(Configuration::get('AFCP_COD_MODULE'));
        }

        if (!in_array($this->name, $modules)) {
            $modules = array_merge([$this->name], $modules);
        }

        if (empty($modules)) {
            return false;
        } else {
            return in_array($module, $modules);
        }
    }

    /**
     * Refresh carrier ids when a carrier is updated
     * @param $params
     * @return void
     */
    public function hookActionCarrierUpdate($params)
    {
        $this->updateCarrierIds();
    }

    /**
     * Check if order is Clever Point
     *
     * @param $id_cart
     * @param $id_order
     * @return boolean
     */
    public function isCleverPointOrder($id_cart = null, $id_order = null)
    {
        if (empty($id_cart) && empty($id_order)) {
            return false;
        }

        $deliver_order_with_cp = null;
        $where_arr = [];
        $params = [
            'id_cart' => $id_cart,
            'id_order' => $id_order,
        ];
        if (!empty($params)) {
            foreach ($params as $column => $value) {
                if (!empty($value)) {
                    $db_value = $value;
                    if (AfCleverPointDeliveryRequest::$definition['fields'][$column]['type'] == AfCleverPointDeliveryRequest::TYPE_STRING) {
                        $db_value = "'".$value."'";
                    }
                    $where_arr[] = sprintf(
                        "`%s` = %s",
                        $column,
                        $db_value
                    );
                }
            }
            $sql = sprintf(
                "SELECT `deliver_order_with_cp` FROM `%s%s` WHERE %s",
                _DB_PREFIX_,
                AfCleverPointDeliveryRequest::$definition['table'],
                implode(' AND ', $where_arr)
            );

            $deliver_order_with_cp = Db::getInstance()->getValue($sql);
        }

        return !empty($deliver_order_with_cp);
    }

    /**
     * Update cart's delivery address based on delivery station's address
     *
     * @param $cart
     * @param array point
     * @param array errors
     * @return int
     */
    public function updateCartAddressFromStationDelivery($cart, $point, &$errors = [])
    {
        $id_address_delivery = null;
        if (Validate::isLoadedObject($cart)) {

            // Check if there is already defined id_delivery_address with this alias
            $address_cp_alias = $this->getStoreAddressAlias(
                $point,
                $cart->id_customer
            );

            // New instance for current delivery address
            $address_delivery = new Address($cart->id_address_delivery);

            // Check if Clever point address exists for the specified customer
            $sql = "SELECT `id_address` FROM `"._DB_PREFIX_."address` WHERE `alias` = '".$address_cp_alias."' AND `deleted` = 0";
            $id_address_delivery = Db::getInstance()->getValue($sql);

            $id_states_countries = $this->getCountryAndStateIds();

            if (!empty($id_address_delivery)) {
                // Update existing address
                $address_clever_point = new Address($id_address_delivery);
                $address_clever_point->address1 = $point['AddressLine1'];
                $address_clever_point->address2 = $point['AddressLine2'];
                $address_clever_point->city = $point['City'];
                $address_clever_point->postcode = $point['ZipCode'];
                $address_clever_point->other = $point['Name'].' '.$address_delivery->other;
                $address_clever_point->id_state = $id_states_countries['id_state_attika'];
            } else {
                // Create new delivery address
                $address_clever_point = new Address();
                $address_clever_point->alias = $address_cp_alias;
                $address_clever_point->firstname = $address_delivery->firstname;
                $address_clever_point->lastname = $address_delivery->lastname;
                $address_clever_point->phone = $address_delivery->phone;
                $address_clever_point->phone_mobile = $address_delivery->phone_mobile;
                $address_clever_point->id_customer = $cart->id_customer;
                $address_clever_point->address1 = $point['AddressLine1'];
                $address_clever_point->address2 = $point['AddressLine2'];
                $address_clever_point->city = $point['City'];
                $id_country_gr = $id_states_countries['id_country_gr'];
                $address_clever_point->id_country = $id_country_gr;
                $address_clever_point->postcode = $point['ZipCode'];
                $address_clever_point->id_state = $id_states_countries['id_state_attika'];
                $address_clever_point->other = $point['Name'].' '.$address_delivery->other;
                if (version_compare(_PS_VERSION_, '1.7.7', '>=')) {
                    if (Address::dniRequired($id_country_gr)) {
                        $address_clever_point->dni = $address_delivery->dni;
                    }
                }
            }

            try {
                if ($address_clever_point->save()) {
                    if (empty($id_address_delivery)) {
                        $sql = "SELECT `id_address` FROM `"._DB_PREFIX_."address` WHERE `alias` = '".$address_cp_alias."' AND `deleted` = 0";
                        $id_address_delivery = Db::getInstance()->getValue($sql);
                    }
                } else {
                    $errors[] = $this->l('Unable to save address data please try again later.');
                }
            } catch (Exception $e) {
                $errors[] = $this->l('Service is unavailable please try again later.').$e->getMessage();
            }

        } else {
            $errors[] = $this->l('Invalid cart please try again.');
        }

        return $id_address_delivery;
    }

    /**
     * Display CleverPoint details in admin order page
     *
     * @param $params
     * @return mixed
     */
    public function hookDisplayAdminOrderLeft($params)
    {
        if (version_compare(_PS_VERSION_, '1.7.7', '>=')) {
            return;
        }

        $id_order = $params['id_order'];
        if ($this->isCleverPointOrder(null, $id_order)) {

            $cp_delivery_request = AfCleverPointDeliveryRequest::getObject([
                'id_order' => $id_order,
            ]);

            $cp_delivery_station = new AfCleverPointDeliveryStation(
                $cp_delivery_request->id_cleverpoint_delivery_station
            );
            $order = new Order($id_order);

            // PrintVoucher URL
            $print_url = null;
            $print_hash = null;
            if (!empty($cp_delivery_request->ShipmentMasterId)) {
                $print_hash = $this->generateHash($cp_delivery_request->ShipmentAwb);
                $print_url = sprintf(
                    '%smodules/%s/PrintVoucher.php',
                    Tools::getHttpHost(true).__PS_BASE_URI__,
                    $this->name
                );
            }
            $this->context->smarty->assign(
                'tpl_vars',
                [
                    'id_order' => $id_order,
                    'cp_carriers' => $this->getCleverPointCarriers(),
                    'cp_delivery_request' => $cp_delivery_request,
                    'cp_delivery_station' => $cp_delivery_station,
                    'order_total_weight' => $order->getTotalWeight(),
                    'ShipmentAwb' =>
                        (!empty($cp_delivery_request->ShipmentAwb) ?
                            $cp_delivery_request->ShipmentAwb : $order->getWsShippingNumber()
                        ),
                    'print_url' => $print_url,
                    'print_hash' => $print_hash,
                    'afcp_voucher_management' => Configuration::get('AFCP_VOUCHER_MANAGEMENT')
                ]
            );
        }

        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_.'/'.$this->name.'/views/templates/hook/displayAdminOrderLeft.tpl'
        );
    }

    /**
     * Display order main
     *
     * @param $params
     * @return mixed
     */
    public function hookDisplayAdminOrderMain($params)
    {
        $id_order = $params['id_order'];
        if ($this->isCleverPointOrder(null, $id_order)) {

            $cp_delivery_request = AfCleverPointDeliveryRequest::getObject([
                'id_order' => $id_order,
            ]);

            $cp_delivery_station = new AfCleverPointDeliveryStation(
                $cp_delivery_request->id_cleverpoint_delivery_station
            );
            $order = new Order($id_order);

            // PrintVoucher URL
            $print_url = null;
            $print_hash = null;
            if (!empty($cp_delivery_request->ShipmentMasterId)) {
                $print_hash = $this->generateHash($cp_delivery_request->ShipmentAwb);
                $print_url = sprintf(
                    '%smodules/%s/PrintVoucher.php',
                    Tools::getHttpHost(true).__PS_BASE_URI__,
                    $this->name
                );
            }
            $this->context->smarty->assign(
                'tpl_vars',
                [
                    'id_order' => $id_order,
                    'cp_carriers' => $this->getCleverPointCarriers(),
                    'cp_delivery_request' => $cp_delivery_request,
                    'cp_delivery_station' => $cp_delivery_station,
                    'order_total_weight' => $order->getTotalWeight(),
                    'ShipmentAwb' =>
                        (!empty($cp_delivery_request->ShipmentAwb) ?
                            $cp_delivery_request->ShipmentAwb : $order->getWsShippingNumber()
                        ),
                    'print_url' => $print_url,
                    'print_hash' => $print_hash,
                    'afcp_voucher_management' => Configuration::get('AFCP_VOUCHER_MANAGEMENT')
                ]
            );

            return $this->context->smarty->fetch(
                _PS_MODULE_DIR_.'/'.$this->name.'/views/templates/hook/displayAdminOrderMain.tpl'
            );
        }

        return;
    }

    /**
     * Get formatted address
     *
     * @param $selected_point
     * @return string
     */
    public function getAddressFormatted($selected_point)
    {
        if (!empty($selected_point->station_address_line_1)) {
            return sprintf(
                "%s, %s, %s",
                $selected_point->station_address_line_1,
                $selected_point->station_address_line_2,
                $selected_point->station_postcode
            );
        } else {
            return null;
        }
    }

    /**
     * Get id_country for GR and id_state for Attiki in order to update delivery addresses
     *
     * @param void
     * @return array
     */
    public function getCountryAndStateIds()
    {
        $id_country_gr = 0;
        $id_state_attika = 0;

        // Get id_country for GR
        $sql = "SELECT `id_country` FROM `"._DB_PREFIX_."country` WHERE `iso_code` = 'GR'";
        $id_country_gr = Db::getInstance()->getValue($sql);

        // Get id_state for Attika
        $sql = "SELECT `id_state` FROM `"._DB_PREFIX_."state` WHERE `iso_code` = 'GR-A1'";
        $id_state_attika = Db::getInstance()->getValue($sql);

        return [
            'id_country_gr' => $id_country_gr,
            'id_state_attika' => $id_state_attika,
        ];
    }

    /**
     * Get store address alias
     *
     * @param object $cleverpoint_delivery_station
     * @param int $id_customer
     * @return string
     */
    public function getStoreAddressAlias($cleverpoint_delivery_station, $id_customer)
    {
        $cleverpoint_delivery_station = (array)$cleverpoint_delivery_station;

        $alias = sprintf('cleverpoint_%s_%d', $cleverpoint_delivery_station['Code'], $id_customer);

        return $alias;
    }

    /**
     * Update address delivery with clever point
     *
     * @param $params
     * @return void
     */
    public function hookDisplayOrderConfirmation($params)
    {
        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            $order = $params['objOrder'];
        } else {
            $order = $params['order'];
        }

        // Is Clever point order?
        if ($this->isCleverPointOrder($order->id_cart)) {

            // Update delivery request with order
            $cp_delivery_request = AfCleverPointDeliveryRequest::getObject([
                'id_order' => $order->id,
            ]);

            if (
                Validate::isLoadedObject($cp_delivery_request) &&
                $cp_delivery_request->deliver_order_with_cp &&
                !empty($cp_delivery_request->id_cleverpoint_delivery_station)
            ) {

                $shipping_cost_formatted = $this->context->getCurrentLocale()->formatPrice(
                    $cp_delivery_request->shipping_cost,
                    $this->context->currency->iso_code
                );
                $service_cost_formatted = $this->context->getCurrentLocale()->formatPrice(
                    $cp_delivery_request->service_cost,
                    $this->context->currency->iso_code
                );

                // Update order with new id_address_delivery
                if (!empty($cp_delivery_request->id_address_delivery)) {
                    $sql = "UPDATE `"._DB_PREFIX_."orders` SET `id_address_delivery` = ".$cp_delivery_request->id_address_delivery." WHERE `id_order` = ".$order->id;
                    Db::getInstance()->execute($sql);
                    // Set clever point delivery addressed as deleted for this customer
                    // we use LIKE statement because during checkout customer might leave orphan addresses
                    $sql =
                        sprintf(
                            "UPDATE `%saddress` SET `deleted` = 1 
                                    WHERE `alias` LIKE 'cleverpoint_%%' AND 
                                          `id_customer` = %d AND 
                                          `id_address` NOT IN (
                                    SELECT `id_address_delivery` FROM `%sorders` WHERE `id_customer` = %d
                                    )",
                            _DB_PREFIX_,
                            $order->id_customer,
                            _DB_PREFIX_,
                            $order->id_customer
                        );
                    Db::getInstance()->execute($sql);
                }

                $this->context->smarty->assign(
                    'afcp_tpl_vars',
                    [
                        'tpl_dir' => $this->getTplDir("views/templates/front/"),
                        'module_url' => $this->context->link->getBaseLink().'modules/'.$this->name,
                        'cleverpoint_order_data' =>
                            [
                                'shipping_cost_formatted' => $shipping_cost_formatted,
                                'service_cost_formatted' => $service_cost_formatted,
                            ],
                        'total_to_pay' => $this->context->getCurrentLocale()->formatPrice(
                            $params['order']->getOrdersTotalPaid(),
                            (new Currency($params['order']->id_currency))->iso_code
                        ),
                        'delivery_station' => (array)new AfCleverPointDeliveryStation(
                            $cp_delivery_request->id_cleverpoint_delivery_station
                        ),
                        'shop_name' => $this->context->shop->name,
                        'contact_url' => $this->context->link->getPageLink('contact', true),
                    ]
                );

                return $this->context->smarty->fetch(
                    'module:'.$this->name.'/views/templates/hook/displayOrderConfirmation.tpl'
                );
            }
        }

        return null;
    }

    /**
     * Display back office header
     *
     * @param $params
     */
    public function hookDisplayBackOfficeHeader($params)
    {
        $extra_js_vars = [];

        $embed_scripts = false;
        $current_controller = null;

        if (version_compare(_PS_VERSION_, '1.7.7.0', '<')) {
            if (Tools::getValue('controller') == 'AdminOrders' && Tools::getValue('id_order') > 0) {
                $id_order = Tools::getValue('id_order');
                $embed_scripts = true;
            }
        } else {
            if ($this->context->controller->controller_name == 'AdminOrders') {
                $current_controller = 'AdminOrders';
                global $kernel;
                $request = $kernel->getContainer()->get('request_stack')->getCurrentRequest();
                if (!isset($request->attributes)) {
                    return;
                }
                $id_order = (int)$request->attributes->get('orderId');
                if (!empty($id_order)) {
                    $embed_scripts = true;
                }
            }
        }

        if (Tools::getValue('configure') == $this->name) {
            $embed_scripts = true;
            // Prestashop categories
            $category_ids = Db::getInstance()->executeS(
                "SELECT `id_category` FROM `"._DB_PREFIX_."category` WHERE `id_parent` > 0"
            );

            foreach ($category_ids as $key => $row) {
                $ps_categories[] = sprintf(
                    '%d:%s',
                    $row['id_category'],
                    strip_tags(
                        Tools::getPath(
                            '',
                            $row['id_category']
                        )
                    )
                );
            }
            $this->context->controller->addJqueryUI('ui.sortable');
            $this->context->controller->addJqueryPlugin('select2');

            // Get all prestashop categories for select 2
            $extra_js_vars['afcp_select2_fields'] = [
                [
                    'name' => 'AFCP_CATEGORY_IDS',
                    'values' => $ps_categories,
                ],
            ];
        }

        if ($embed_scripts) {

            $js_vars = [
                // Generate security hash for ajax requests
                'afcp_hash' => $this->generateHash($this->context->employee->id),
                // Define which controller will handle ajax
                'afcp_ajax_url' => $this->context->link->getAdminLink($this->tabClass),
                // Enter which method will be executed in ajax request
                'afcp_action' => 'AfCleverPointAdmin',
                'afcp_id_order' => (isset($id_order) ? $id_order : 0),
                'afcp_current_controller' => $current_controller,
            ];

            $this->context->controller->addJquery();
            $this->context->controller->addJs($this->_path.'views/js/lib.js');
            $this->context->controller->addJs($this->_path.'views/js/admin.js');

            // Add variables in JS file
            Media::addJsDef(
                array_merge($js_vars, $extra_js_vars)
            );
        }
    }

    /**
     * Create API instance
     *
     * @return object
     */
    public function cpApiInstance()
    {
        $cp = new \CleverPoint\Api\ApiClient(
            Configuration::get('AFCP_CLEVERPOINT_API_KEY'),
            Configuration::get('AFCP_SANDBOX')
        );

        return $cp;
    }

    /**
     * Get Clever Point carriers list
     *
     * @boolean $force_refresh
     * @return array
     */
    public function getCleverPointCarriers($force_refresh = false)
    {
        // Check if local json file exists
        $json_file = _PS_MODULE_DIR_.$this->name.'/data/carriers.json';

        $carriers = [];

        if (!$force_refresh) {
            if (file_exists($json_file)) {
                // Check if file is not older than 2 hours
                if (time() - filemtime($json_file) <= 2 * 3600) {
                    $carriers = json_decode(file_get_contents($json_file), true);
                }
            }
        }

        if (empty($carriers)) {
            $cp = $this->cpApiInstance();
            $carriers = $cp->getCarriers();
            file_put_contents($json_file, json_encode($carriers));
        }

        return $carriers;
    }

    /**
     * Get order data for https://docs.cleverpoint.gr/cleverpoint-api/shipping/
     *
     * @param object $order
     * @param object $cp_delivery_request
     * @return array
     */
    public function getShippingOrderData($order, $cp_delivery_request)
    {
        if (!Validate::isLoadedObject($order)) {
            $order = new Order($order);
        }

        $customer = new Customer($order->id_customer);
        $address_delivery = new Address($order->id_address_delivery);

        // Get delivery station Id
        $sql =
            sprintf(
                "SELECT `StationId` FROM `%s%s` WHERE `%s` = %d",
                _DB_PREFIX_,
                AfCleverPointDeliveryStation::$definition['table'],
                AfCleverPointDeliveryStation::$definition['primary'],
                $cp_delivery_request->id_cleverpoint_delivery_station
            );

        $StationId = Db::getInstance()->getValue($sql);

        $is_cod = $this->isCod($order->module);
        $cod_fee = 0;
        if ($is_cod) {
            $cod_fee = $this->getCodFeeAmount($order);
        }
        $data = [
            'ItemsDescription' => sprintf('Order %d', $order->id),
            'PickupComments' => $address_delivery->other,
            'Consignee' => [
                'ContactName' => sprintf('%s %s', $address_delivery->firstname, $address_delivery->lastname),
                'Address' => $address_delivery->address1,
                'Area' => 'Attica',
                'City' => $address_delivery->city,
                'PostalCode' => $address_delivery->postcode,
                'Phones' => (!empty($address_delivery->phone_mobile) ? $address_delivery->phone_mobile : $address_delivery->phone),
                'NotificationPhone' => (!empty($address_delivery->phone_mobile) ? $address_delivery->phone_mobile : $address_delivery->phone),
                'Emails' => $customer->email,
                'ShipmentCost' => (float)Tools::ps_round(($order->total_shipping - $cod_fee), 2),
                'CustomerReferenceId' => $order->id_customer,
            ],
            'DeliveryStation' => $StationId,
            'CODs' => [],
            'Items' => [],
            'ExternalCarrierId' => $cp_delivery_request->ExternalCarrierId,
            'ExternalCarrierName' => $cp_delivery_request->ExternalCarrierName,
            'ShipmentAwb' => $cp_delivery_request->ShipmentAwb,
        ];

        // Items
        $weight_per_parcel = round($order->getTotalWeight() / $cp_delivery_request->parcels, 2);

        for ($x = 1; $x <= $cp_delivery_request->parcels; $x++) {
            $to_push = [
                'Description' => sprintf('Order %d', ($x / $cp_delivery_request->parcels)),
                'IsFragile' => 'false',
                'Weight' => [
                    'UnitType' => 'kg',
                    'Value' => $weight_per_parcel > 0 ? $weight_per_parcel : 0.5,
                ],
            ];
            array_push($data['Items'], $to_push);
        }

        // COD
        if ($is_cod) {
            $data['CODs'][] = [
                'Amount' => [
                    "CurrencyCode" => "EUR",
                    "Value" => (float)Tools::ps_round($order->total_paid_tax_incl, 2),
                ],
            ];
        }

        return $data;
    }

    public function hookDisplayBeforeCarrier($params)
    {
        $id_cart = (int)$this->context->cart->id;

        // Get module's options
        $options = $this->getConfigFormValues();

        // Check if Clever Point should be displayed or not based on Cart products' categories
        $is_clever_point_available = $this->isMethodAvailableForCart($options);

        // Get display method
        $afcp_display_method = $options['AFCP_DISPLAY_METHOD'];

        $delivery_request = AfCleverPointDeliveryRequest::getObject(
            ['id_cart' => $id_cart]
        );

        $delivery_station = [];
        if (!empty($delivery_request->id_cleverpoint_delivery_station)) {
            $delivery_station = (array)new AfCleverPointDeliveryStation(
                $delivery_request->id_cleverpoint_delivery_station
            );
        }

        // Format prices
        $shipping_cost_formatted = $this->context->getCurrentLocale()->formatPrice(
            $delivery_request->shipping_cost,
            $this->context->currency->iso_code
        );
        $service_cost_formatted = $this->context->getCurrentLocale()->formatPrice(
            $delivery_request->service_cost,
            $this->context->currency->iso_code
        );

        $sql = "SELECT `name` FROM `"._DB_PREFIX_."carrier` 
                WHERE `id_carrier` IN (".implode(',', $options['AFCP_CARRIER_IDS']).") AND
                `active` = 1 AND `deleted` = 0
                ORDER BY `position`";

        $carrier_names = Db::getInstance()->executeS($sql);

        $this->context->smarty->assign(
            'afcp_tpl_vars',
            [
                'tpl_dir' => $this->getTplDir("views/templates/front/"),
                'module_url' => $this->context->link->getBaseLink().'modules/'.$this->name,
                'display_method' => $afcp_display_method,
                'map_width' => $options['AFCP_MAP_WIDTH'],
                'map_height' => $options['AFCP_MAP_HEIGHT'],
                // Has customer selected o locker?
                'station_customer_selected' => (!empty($delivery_request->id_cleverpoint_delivery_station)),
                'delivery_request' => (array)$delivery_request,
                'delivery_station' => $delivery_station,
                // Convert to array for tpl display
                'shipping_cost_formatted' => $shipping_cost_formatted,
                'service_cost_formatted' => $service_cost_formatted,
                'shipping_cost_to_customer' => $options['AFCP_COSTTOCUST'],
                'deliver_order_with_cp' => $delivery_request->deliver_order_with_cp,
                'carrier_names' => array_map('array_shift', $carrier_names),
                'is_clever_point_available' => $is_clever_point_available,
            ]
        );

        if ($is_clever_point_available) {
            return $this->context->smarty->fetch(
                _PS_MODULE_DIR_.'/'.$this->name.'/views/templates/hook/displayBeforeCarrier.tpl'
            );
        }
    }

    /**
     * For selected PS carrier get Clever Point carrier data
     *
     * @param $id_carrier
     * @return array
     */
    public function getCleverPointCarrierData($id_carrier)
    {
        $result = [];

        // Get Clever Point carriers
        $cp_carriers = $this->getCleverPointCarriers();

        // Get PS carrier id_references
        $sql = "SELECT `id_reference` FROM `"._DB_PREFIX_."carrier` WHERE `id_carrier` = ".$id_carrier;
        $id_reference = Db::getInstance()->getValue($sql);

        $AFCP_CARRIER_MAPPING = Tools::jsonDecode(Configuration::get('AFCP_CARRIER_MAPPING'), true);
        if (!empty($AFCP_CARRIER_MAPPING) && !empty($id_reference)) {
            $carrierId = $AFCP_CARRIER_MAPPING[$id_reference];

            foreach ($cp_carriers as $carrier) {
                if ($carrier['Id'] == $carrierId) {
                    $result = $carrier;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Update the Address ID of the Cart.
     *
     * @param $cart
     * @param int $id_address Current Address ID to change
     * @param int $id_address_new New Address ID
     */
    public function updateAddressId($cart, $id_address, $id_address_new)
    {
        $sql = "UPDATE `"._DB_PREFIX_."cart` SET `id_address_delivery` = ".$id_address_new." WHERE `id_cart` = ".$cart->id;
        Db::getInstance()->execute($sql);

        $sql = 'UPDATE `'._DB_PREFIX_.'cart_product`
        SET `id_address_delivery` = '.(int)$id_address_new.'
        WHERE  `id_cart` = '.(int)$cart->id.'
            AND `id_address_delivery` = '.(int)$id_address;
        Db::getInstance()->execute($sql);

        $sql = 'UPDATE `'._DB_PREFIX_.'customization`
            SET `id_address_delivery` = '.(int)$id_address_new.'
            WHERE  `id_cart` = '.(int)$cart->id.'
                AND `id_address_delivery` = '.(int)$id_address;
        Db::getInstance()->execute($sql);
    }

    /**
     * Add some extra variables in email content for Clever Point Orders
     *
     * @param $params
     * @return void
     */
    public function hookActionGetExtraMailTemplateVars($params)
    {
        if ($params['template'] == 'order_conf' ||
            $params['template'] == 'new_order'
        ) {
            $order_reference = $params['template_vars']['{order_name}'];
            $sql =
                sprintf(
                    "SELECT `id_order`, `id_cart`, `id_customer`, `id_address_delivery` FROM `%sorders` WHERE `reference` = '%s'",
                    _DB_PREFIX_,
                    $order_reference
                );
            $res = Db::getInstance()->getRow($sql);
            $id_order = (isset($res['id_order']) ? $res['id_order'] : 0);
            $id_cart = (isset($res['id_cart']) ? $res['id_cart'] : 0);
            $id_address_delivery = (isset($res['id_cart']) ? $res['id_cart'] : 0);

            if (!empty($id_order)) {
                // Get delivery request data
                $cp_delivery_request = AfCleverPointDeliveryRequest::getObject(['id_cart' => $id_cart]);

                if (Validate::isLoadedObject($cp_delivery_request)) {

                    $shipping_cost_formatted = $this->context->getCurrentLocale()->formatPrice(
                        $cp_delivery_request->shipping_cost,
                        $this->context->currency->iso_code
                    );
                    $service_cost_formatted = $this->context->getCurrentLocale()->formatPrice(
                        $cp_delivery_request->service_cost,
                        $this->context->currency->iso_code
                    );
                    // Separate Clever Point Cost from shipping cost
                    $params['extra_template_vars']['{total_shipping}'] = sprintf(
                        "%s: %s <br>%s: %s",
                        $this->l('Service cost'),
                        $service_cost_formatted,
                        $this->l('Courier cost'),
                        $shipping_cost_formatted
                    );

                    // Add to email Clever Point delivery address
                    if (!empty($cp_delivery_request->id_address_delivery)) {

                        // Get clever point delivery station details
                        $cp_delivery_station = new AfCleverPointDeliveryStation(
                            $cp_delivery_request->id_cleverpoint_delivery_station
                        );

                        // Create instance for Clever Point delivery address
                        $delivery_address = new Address($cp_delivery_request->id_address_delivery);
                        $delivery_address->firstname = $this->l('Clever Point Pick-up');
                        $delivery_address->lastname = null;
                        // Add delivery station name to addresse's Company
                        $delivery_address->company = $cp_delivery_station->Name;
                        $delivery_address->phone = $cp_delivery_station->Phones;
                        // Get order's messages
                        $other = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                            '
                                SELECT `message`
                                FROM `'._DB_PREFIX_.'message`
                                WHERE `id_order` = '.$id_order.'
                                ORDER BY `id_message`
                            '
                        );
                        $delivery_address->other = '<br>'.$other;

                        // Override email params with Clever Point delivery address data
                        $params['extra_template_vars']['{delivery_block_txt}'] =
                            AddressFormat::generateAddress(
                                $delivery_address,
                                [],
                                AddressFormat::FORMAT_NEW_LINE
                            );

                        $params['extra_template_vars']['{delivery_block_html}'] =
                            AddressFormat::generateAddress(
                                $delivery_address,
                                ['avoid' => []],
                                '<br />',
                                ' ',
                                [
                                    'firstname' => '<span style="font-weight:bold;">%s</span>',
                                ]
                            );
                    }
                }
            }
        }
    }

    /**
     * Validate order update Clever Point delivery request
     *
     * @param $params
     * @return void
     */
    public function hookActionValidateOrder($params)
    {
        $id_order = (int)$params['order']->id;
        if ($this->isCleverPointOrder($params['order']->id_cart)) {
            $order = new Order($id_order);
            // Is order cash on delivery?
            $is_cod = $this->isCod($order->module);
            // Get order comments if any
            $first_message = $order->getFirstMessage();

            // Update Clever Point carrier
            $ExternalCarrierId = null;
            $ExternalCarrierName = null;
            $cp_carrier = $this->getCleverPointCarrierData($order->id_carrier);
            if (!empty($cp_carrier)) {
                $ExternalCarrierId = $cp_carrier['Id'];
                $ExternalCarrierName = $cp_carrier['Name'];
            }

            $sql =
                sprintf(
                    "UPDATE `%s%s` SET 
                                `id_order` = %s, 
                                `is_cod` = '%s', 
                                `ExternalCarrierId` = '%s', 
                                `ExternalCarrierName` = '%s', 
                                `PickupComments` = '%s', 
                                `date_upd` = '%s'  
                            WHERE `id_cart` = %d",
                    _DB_PREFIX_,
                    AfCleverPointDeliveryRequest::$definition['table'],
                    $order->id,
                    $is_cod,
                    $ExternalCarrierId,
                    $ExternalCarrierName,
                    $first_message,
                    date('Y-m-d H:i:s'),
                    $order->id_cart
                );
            Db::getInstance()->execute($sql);
        }
    }

    public function hookActionCarrierProcess($params)
    {
        // Check if is cleverPoint order and update shipping cost
        if ($this->isCleverPointOrder($params['cart']->id)) {
            $cp_delivery_request = AfCleverPointDeliveryRequest::getObject(['id_cart' => $params['cart']->id]);
            $cp_delivery_request->id_cart = $params['cart']->id;
            $cp_delivery_request->shipping_cost = $this->context->cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
            $cp_delivery_request->save();
        }

        return true;
    }

    /**
     * Get cart totals with Clever Point costs
     *
     * @param $params
     * @return void
     */
    public function hookActionCleverPointCartGetOrderTotal(&$params)
    {
        // Do not include service cost in specific cart total calculations
        $types = [Cart::ONLY_DISCOUNTS, Cart::ONLY_WRAPPING, Cart::ONLY_SHIPPING];
        if ($params['type'] == Cart::BOTH) {
            if ($this->isMethodAvailableForCart([], $params['object'])) {
                if (Configuration::get('AFCP_COSTTOCUST')) {
                    if ($this->isCleverPointOrder($params['object']->id)) {
                        // Get Clever Point service cost
                        $sql =
                            sprintf(
                                "SELECT `service_cost`, `deliver_order_with_cp` FROM `%s%s` WHERE `id_cart` = %d",
                                _DB_PREFIX_,
                                AfCleverPointDeliveryRequest::$definition['table'],
                                $params['object']->id
                            );
                        $result = Db::getInstance()->getRow($sql);
                        if ($result['deliver_order_with_cp']) {
                            $params['service_cost'] = (float)$result['service_cost'];
                            $params['return'] = true;
                        }
                    }
                }
            }
        }

        return;
    }

    /**
     * Hook to hide other cod methods
     *
     * @param $params
     * @return void
     */
    public function hookActionCleverPointOverrideCod(&$params)
    {
        // Hide other cod payment methods if Clever Point is selected
        if ($this->isCleverPointOrder($params['args']['cart']->id)) {
            $params['hide_method'] = true;
        }
    }

    /**
     * Get codfee amount
     *
     * @param $order
     * @param $args
     * @return float
     */
    public function getCodFeeAmount($order, $args = [])
    {
        if ($order->module == 'codfee') {
            $sql = "SELECT `codfee` FROM `"._DB_PREFIX_."orders` WHERE `id_order` = ".$order->id;

            return (float)Tools::ps_round(Db::getInstance()->getValue($sql), 2);
        }

        return 0;
    }

    /**
     * Check if Clever Point is available in checkout
     *
     * @param $options
     * @param object $cart
     * @return boolean
     */
    public function isMethodAvailableForCart($options = [], $cart = null)
    {
        if (empty($cart)) {
            $cart = $this->context->cart;
        }

        if (!isset($options['AFCP_CATEGORY_IDS'])) {
            $options['AFCP_CATEGORY_IDS'] = Configuration::get('AFCP_CATEGORY_IDS');
        }

        if (!empty($options['AFCP_CATEGORY_IDS'])) {
            $ids_category = explode(',', $options['AFCP_CATEGORY_IDS']);
            if (!isset($options['AFCP_CAT_EXCL_TYPE'])) {
                $options['AFCP_CAT_EXCL_TYPE'] = Configuration::get('AFCP_CAT_EXCL_TYPE'); // True: Display, False: Hide
            }

            // Get cart products and check categories
            $cart_products = $cart->getProducts();
            if (!empty($cart_products)) {
                foreach ($cart_products as $product) {
                    // Get product's categories
                    $sql = "SELECT `id_category` FROM `"._DB_PREFIX_."category_product` WHERE `id_product` = ".$product['id_product'];
                    $product_categories = array_map('array_shift', Db::getInstance()->executeS($sql));
                    if ($options['AFCP_CAT_EXCL_TYPE']) {
                        // Hide Clever Point if any of the products are not in specified categories
                        if (!array_intersect($ids_category, $product_categories)) {
                            return false;
                        }
                    } else {
                        // Hide Clever Point if any of the products are in specified categories
                        if (array_intersect($ids_category, $product_categories)) {
                            return false;
                        }
                    }
                }
            }
        }

        // There are no limitations regarding Clever Point
        return true;
    }

    /**
     * @param array{cookie: Cookie, cart: Cart, altern: int} $params
     *
     * @return array|PaymentOption[] Should always returns an array to avoid issue
     */
    /**
     * Return payment options available for PS 1.7+
     *
     * @param array Hook parameters
     *
     * @return array|null
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        /** @var Cart $cart */
        $cart = $params['cart'];

        // Check if is clever point order
        if (!$this->isCleverPointOrder($cart->id)) {
            return;
        }

        if (!$this->checkCurrency($cart)) {
            return;
        }

        if ($cart->isVirtualCart()) {
            return;
        }

        // Check if selected clever point supports COD
        $cp_delivery_request = AfCleverPointDeliveryRequest::getObject(['id_cart' => $cart->id]);

        if (Validate::isLoadedObject($cp_delivery_request)) {
            $cp_delivery_station = AfCleverPointDeliveryStation::getObject(
                ['id_cleverpoint_delivery_station' => $cp_delivery_request->id_cleverpoint_delivery_station]
            );

            if ($cp_delivery_station->isOperationalForCOD()) {
                $option = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
                $option->setCallToActionText($this->l('Cash on delivery'))
                    ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
                    ->setAdditionalInformation(
                        $this->fetch(
                            'module:afcleverpoint/views/templates/hook/paymentOptions-additionalInformation.tpl'
                        )
                    );

                return [
                    $option,
                ];
            }
        }

        return;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);
        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Install module's overrides
     *
     * @return bool
     */
    public function installOverrides()
    {
        try {
            $io = new CleverPoint\Override\InstallOverride($this);
            $io->processOverride('addOverride');

            return true;
        } catch (Exception $e) {
            $this->_errors[] = $e->getMessage();
            return false;
        }
    }

    /**
     * Uninstall overrides
     *
     * @return bool
     */
    public function uninstallOverrides()
    {
        try {
            $io = new CleverPoint\Override\InstallOverride($this);
            $io->processOverride('removeOverride');

            return true;
        } catch (Exception $e) {
            $this->_errors[] = $e->getMessage();
            return false;
        }
    }

    ## BEGIN thecheckout module functions
    /**
     * Check if thecheckout module is enabled.
     *
     * @link https://addons.prestashop.com/en/express-checkout-process/6841-one-page-checkout-for-prestashop.html
     * @return boolean
     */
    public function tcIsModuleEnabled()
    {
        if (Module::isEnabled('thecheckout')) {
            if (!Configuration::get('TC_test_mode')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get thecheckout versions that are compatible with CleverPoint module
     *
     * @return array
     */
    public function tcVersionCompatibility()
    {
        $compatibility =
            [
                'min' => '3.3.4',
                'max' => '3.3.8'
            ];

        return $compatibility;
    }

    /**
     * Get thecheckout installed version
     *
     * @return array
     */
    public function tcVersion()
    {
        $version = Db::getInstance()->getValue("SELECT `version` FROM `"._DB_PREFIX_."module` WHERE `name` = 'thecheckout'");

        return $version;
    }

    /**
     * Load thecheckout assets
     * @return void
     */
    public function tcLoadAssetsFront()
    {
        $this->context->controller->registerStylesheet(
            $this->name.'-css',
            'modules/'.$this->name.'/views/css/front_opc.css',
            ['priority' => 150]
        );

        // Load javascript
        $opc_version = $this->tcVersion(); // module's version
        // Check if exists js for installed version e.g. /views/js/opc/opc-v3.3.8.js
        if (file_exists(_PS_MODULE_DIR_."/".$this->name."/views/js/opc/opc-v$opc_version.js")) {
            $this->context->controller->registerJavascript(
                $this->name.'-js',
                'modules/'.$this->name."/views/js/opc/opc-v$opc_version.js",
                ['priority' => 150, 'version' => $this->version]
            );
        } else {
            $this->context->controller->registerJavascript(
                $this->name.'-js',
                'modules/'.$this->name."/views/js/opc/opc.js",
                ['priority' => 150, 'version' => $this->version]
            );
        }
    }
    ## END thecheckout module functions
}