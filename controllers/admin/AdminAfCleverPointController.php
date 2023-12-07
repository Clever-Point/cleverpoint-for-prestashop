<?php
/**
 * Afternet
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 *
 * @author    Afternet <info@afternet.gr>
 * @copyright Afternet
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class AdminAfCleverPointController extends ModuleAdminController
{
    public $module;

    // Controller's object model
    public $object_model = 'Order';

    protected $statuses_array = array();

    protected $position_identifier = 'id_order';

    // For Ajax handlers
    private $_ajax_response = array();

    private $_ajax_errors = array();

    public function __construct()
    {
        // Set variables
        $this->table = 'order';
        $this->className = 'Order';
        $this->bootstrap = true;
        $this->addRowAction('view');
        $this->_orderWay = 'DESC';
        $this->_defaultOrderBy = 'date_add';
        $this->explicitSelect = true;
        $this->allow_export = true;
        $this->deleted = false;
        parent::__construct();
        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
        }

        $this->_select = '
		a.*,
		a.`reference`,
		osl.`name` AS `osname`,
		CONCAT(LEFT(c.`firstname`, 1), \'. \', c.`lastname`) AS `customer`';

        $this->_join = "
		INNER JOIN `"._DB_PREFIX_.AfCleverPointDeliveryRequest::$definition['table']."` AS afcp ON (afcp.`".AfCleverPointDeliveryRequest::$definition['primary']."` = a.`id_order`)
		LEFT JOIN `" . _DB_PREFIX_ . "customer` c ON (c.`id_customer` = a.`id_customer`)
		LEFT JOIN `" . _DB_PREFIX_ . "order_state` os ON (os.`id_order_state` = a.`current_state`)
		LEFT JOIN `" . _DB_PREFIX_ . "order_state_lang` osl ON (os.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = " . (int) $this->context->language->id . ")";

        $statuses = OrderState::getOrderStates((int) $this->context->language->id);
        foreach ($statuses as $status) {
            $this->statuses_array[$status['id_order_state']] = $status['name'];
        }

        $this->fields_list = array(
            'id_order' => array(
                'title' => $this->trans('ID', array(), 'Admin.Global'),
                'class' => 'fixed-width-xs',
            ),
            'reference' => array(
                'title' => $this->trans('Reference', array(), 'Admin.Global'),
            ),
            'customer' => array(
                'title' => $this->trans('Customer', array(), 'Admin.Global'),
                'havingFilter' => true,
            ),
            'total_paid_tax_incl' => array(
                'title' => $this->trans('Total', array(), 'Admin.Global'),
                'align' => 'text-right',
                'type' => 'price',
                'currency' => true,
                'callback' => 'setOrderCurrency',
                'badge_success' => true,
            ),
            'payment' => array(
                'title' => $this->trans('Payment', array(), 'Admin.Global'),
            ),
            'osname' => array(
                'title' => $this->trans('Status', array(), 'Admin.Global'),
                'type' => 'select',
                'color' => 'color',
                'list' => $this->statuses_array,
                'filter_key' => 'os!id_order_state',
                'filter_type' => 'int',
                'order_key' => 'osname',
            ),
            'date_add' => array(
                'title' => $this->trans('Date', array(), 'Admin.Global'),
                'align' => 'text-right',
                'type' => 'datetime',
                'filter_key' => 'a!date_add',
            ),
        );
    }

    public static function setOrderCurrency($echo, $tr)
    {
        if (!empty($tr['id_currency'])) {
            $idCurrency = (int) $tr['id_currency'];
        } else {
            $order = new Order($tr['id_order']);
            $idCurrency = (int) $order->id_currency;
        }

        return Tools::displayPrice($echo, $idCurrency);
    }

    /**
     * Check if is ajax request
     *
     * @return bool
     */
    public function isAjaxRequest()
    {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower(
                $_SERVER['HTTP_X_REQUESTED_WITH']
            ) == 'xmlhttprequest') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Set Ajax response variables
     *
     * @param $variable
     * @param $value
     *
     * @return void
     */
    public function setAjaxReponseVar($variable, $value = null)
    {
        if (is_array($variable)) {
            foreach ($variable as $field => $field_value) {
                $this->_ajax_response[$field] = $field_value;
            }
        } else {
            $this->_ajax_response[$variable] = $value;
        }
    }

    /**
     * Get ajax response data
     *
     * @param void
     *
     * @return array
     */
    public function getAjaxResponse()
    {
        return $this->_ajax_response;
    }

    /**
     * Ajax requests for category fields
     *
     */
    public function ajaxProcessAfCleverPointAdmin()
    {
        $this->setAjaxReponseVar('status', 'warning');
        $this->setAjaxReponseVar(
            'message',
            $this->module->translate('Invalid request.')
        );

        $this->setAjaxReponseVar('html', '');

        $hash = Tools::getValue('hash');

        // Ajax request
        $request = Tools::getValue('request');

        if (
            ($hash === $this->module->generateHash($this->context->employee->id)) &&
            !empty($request)
        ) {

            try {

                if (method_exists($this, $request)) {
                    call_user_func(array($this, $request));
                } else {
                    $this->_ajax_errors[] =
                        $this->module->translate('Method does not exist.', [], 'Modules.Afcleverpoint.Admin');
                }

            } catch (Exception $e) {
                $this->_ajax_errors[] =
                    $e->getMessage();
            }

        }

        if (empty($this->_ajax_errors)) {
            $this->setAjaxReponseVar('status', 'success');
        } else {
            $this->setAjaxReponseVar('errors', $this->_ajax_errors);
        }

        echo json_encode(
            $this->getAjaxResponse()
        );

        exit;
    }

    /**
     * Sample function for admin
     *
     */
    public function createVoucher()
    {
        $data = array();
        parse_str(Tools::getValue('data'), $data);

        // Check delivery request first
        if (isset($data['id_cleverpoint_delivery_request']) && !empty($data['id_cleverpoint_delivery_request'])) {
            if (isset($data['afcp_id_order']) && !empty($data['afcp_id_order'])) {
                $cp_delivery_request = new AfCleverPointDeliveryRequest((int)$data['id_cleverpoint_delivery_request']);
                if ($cp_delivery_request->id_order == (int)$data['afcp_id_order']) {
                    // Update ObjectModel data
                    $object_modified = false;
                    if (isset($data['ShipmentAwb'])) {
                        $cp_delivery_request->ShipmentAwb = $data['ShipmentAwb'];
                        $object_modified = true;
                    }
                    if (isset($data['ExternalCarrierId'])) {
                        $cp_delivery_request->ExternalCarrierId = $data['ExternalCarrierId'];
                        $object_modified = true;
                    }
                    if (isset($data['PickupComments'])) {
                        $cp_delivery_request->PickupComments = $data['PickupComments'];
                        $object_modified = true;
                    }

                    if (empty($cp_delivery_request->ExternalCarrierId)) {
                        $this->_ajax_errors[] =
                            $this->module->translate('Please select courier.');
                        return false;
                    }

                    if (empty($cp_delivery_request->ShipmentAwb)) {
                        $this->_ajax_errors[] =
                            $this->module->translate('Please enter ShipmentAwb.');
                        return false;
                    }

                    if ($object_modified) {
                        try {
                            if (!$cp_delivery_request->save()) {
                                $this->_ajax_errors[] = $this->module->translate(
                                    'Unable to save Delivery Request Data.'
                                );
                            }
                        } catch (Exception $e) {
                            $this->_ajax_errors[] =
                                sprintf(
                                    $this->module->translate('Unable to save Delivery Request Data: %s.'),
                                    $e->getMessage()
                                );
                        }
                    }

                    if (empty($this->_ajax_errors)) {
                        // Get order data for voucher
                        $voucher_data = $this->module->getShippingOrderData(
                            (int)$data['afcp_id_order'],
                            $cp_delivery_request
                        );

                        if (!empty($voucher_data)) {
                            $cp = $this->module->cpApiInstance();
                            $result = $cp->apiCall('/Shipping', $voucher_data, 'POST');

                            if (!$cp->hasError($result)) {
                                $cp_delivery_request->ShipmentMasterId = $result['Content']['ShipmentMasterId'];
                                $cp_delivery_request->json_response = json_encode($result);
                                $cp_delivery_request->save();
                                $this->setAjaxReponseVar(
                                    'message',
                                    $this->module->translate('Voucher created successfully.')
                                );
                            } else {
                                if (isset($result['Messages']) && !empty($result['Messages'])) {
                                    foreach ($result['Messages'] as $message) {
                                        $this->_ajax_errors[] = sprintf(
                                            '%s %s %s',
                                            $message['Code'],
                                            $message['MessageType'],
                                            $message['Description']
                                        );
                                    }
                                }
                                if (!empty($cp->get('errors'))) {
                                    foreach ($cp->get('errors') as $error) {
                                        $this->_ajax_errors[] = $error;
                                    }
                                }
                            }

                        } else {
                            $this->_ajax_errors [] =
                                $this->module->translate('Unable to load order shipping data.');
                        }
                    }
                } else {
                    $this->_ajax_errors[] = $this->module->translate(
                        'This order is not assigned to specified Delivery Request.'
                    );
                }
            } else {
                $this->_ajax_errors[] = $this->module->translate('Invalid order.');
            }
        } else {
            $this->_ajax_errors[] = $this->module->translate('Invalid delivery request.');
        }

        return true;
    }

    /**
     * Cancel voucher
     * @return void
     */
    public function cancelVoucher()
    {
        $data = array();
        parse_str(Tools::getValue('data'), $data);

        // Check delivery request first
        if (isset($data['id_cleverpoint_delivery_request']) && !empty($data['id_cleverpoint_delivery_request'])) {
            if (isset($data['afcp_id_order']) && !empty($data['afcp_id_order'])) {
                $cp_delivery_request = new AfCleverPointDeliveryRequest((int)$data['id_cleverpoint_delivery_request']);
                if ($cp_delivery_request->id_order == (int)$data['afcp_id_order']) {
                    if (!empty($cp_delivery_request->ShipmentAwb)) {
                        $cp = $this->module->cpApiInstance();
                        $ShipmentAwb = $cp_delivery_request->ShipmentAwb;
                        $result = $cp->apiCall("/Shipping/$ShipmentAwb/Cancel", null, 'POST');
                        if (!$cp->hasError($result)) {
                            $cp_delivery_request->ShipmentMasterId = null;
                            $cp_delivery_request->json_response = json_encode($result);
                            $cp_delivery_request->save();
                            $this->setAjaxReponseVar(
                                'message',
                                $this->module->translate('Voucher cancelled successfully.')
                            );
                        } else {
                            if (isset($result['Messages']) && !empty($result['Messages'])) {
                                foreach ($result['Messages'] as $message) {
                                    $this->_ajax_errors[] = sprintf(
                                        '%s %s %s',
                                        $message['Code'],
                                        $message['MessageType'],
                                        $message['Description']
                                    );
                                }
                            }
                            if (!empty($cp->get('errors'))) {
                                foreach ($cp->get('errors') as $error) {
                                    $this->_ajax_errors[] = $error;
                                }
                            }
                        }
                    } else {
                        $this->_ajax_errors[] = $this->module->translate(
                            'Empty ShipmentAwb.'
                        );
                    }
                } else {
                    $this->_ajax_errors[] = $this->module->translate(
                        'This order is not assigned to specified Delivery Request.'
                    );
                }
            } else {
                $this->_ajax_errors[] = $this->module->translate('Invalid order.');
            }
        } else {
            $this->_ajax_errors[] = $this->module->translate('Invalid delivery request.');
        }

        return true;
    }

    /**
     * Get order's shipping information
     *
     * @return bool
     */
    public function getOrderShippingInformation()
    {
        $data = array();
        parse_str(Tools::getValue('data'), $data);

        // Check delivery request first
        if (isset($data['id_cleverpoint_delivery_request']) && !empty($data['id_cleverpoint_delivery_request'])) {
            if (isset($data['afcp_id_order']) && !empty($data['afcp_id_order'])) {
                $cp_delivery_request = new AfCleverPointDeliveryRequest((int)$data['id_cleverpoint_delivery_request']);
                if ($cp_delivery_request->id_order == (int)$data['afcp_id_order']) {

                    $this->context->smarty->assign(
                        'tpl_vars',
                        [
                            'cp_delivery_request' => $cp_delivery_request,
                            'cp_delivery_station' => new AfCleverPointDeliveryStation(
                                $cp_delivery_request->id_cleverpoint_delivery_station
                            ),
                        ]
                    );

                    $html = $this->context->smarty->fetch(
                        'module:'.$this->module->name.'/views/templates/admin/order/order-info.tpl'
                    );

                    $this->setAjaxReponseVar(
                        'html',
                        $html
                    );

                } else {
                    $this->_ajax_errors[] = $this->module->translate(
                        'This order is not assigned to specified Delivery Request.'
                    );
                }
            } else {
                $this->_ajax_errors[] = $this->module->translate('Invalid order.');
            }
        } else {
            $this->_ajax_errors[] = $this->module->translate('Invalid delivery request.');
        }

        return true;
    }

    /**
     * Save carrier data
     *
     * @return true
     */
    public function saveCleverPointCarriers()
    {
        $data = array();
        parse_str(Tools::getValue('data'), $data);

        if (isset($data['carrier']) && !empty($data['carrier'])) {
            if (
                Configuration::updateValue(
                    'AFCP_CARRIER_MAPPING',
                    AfCleverPoint::jsonEncode($data['carrier'])
                )
            ) {

                $this->setAjaxReponseVar('status', 'success');
                $this->setAjaxReponseVar(
                    'message',
                    $this->module->translate('Carriers saved successfully.')
                );

            } else {

                $this->setAjaxReponseVar(
                    'message',
                    $this->module->translate('Unable to save data.')
                );
            }

        } else {

            $this->setAjaxReponseVar(
                'message',
                $this->module->translate('Invalid data.')
            );

        }

        return true;
    }
}
