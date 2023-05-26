<?php
/**
 * 2019-2020 Afternet
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 *
 * @author    Afternet <info@afternet.gr>
 * @copyright 2019-2020 Afternet
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class AfCleverPointAjaxModuleFrontController extends ModuleFrontController
{
    public $module;
    // Ajax response array
    private $_ajax_response = [];
    // Ajax errors array
    private $_ajax_errors = [];
    // Ajax extra data array
    private $_data = [];

    /**
     * Constructor
     *
     * @param void
     *
     * @return mixed
     */
    public function __construct()
    {
        parent::__construct();
        if (!$this->module->active) {
            die();
        }
    }

    public function initContent()
    {
        parent::initContent();

        // Handle Ajax Requests
        $this->ajaxHandler();
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
     * Handle Ajax requests
     *
     * @param void
     *
     * @return mixed
     */
    public function ajaxHandler()
    {
        // Check if is ajax call
        if (
            Tools::isSubmit('ajax') &&
            Tools::isSubmit('action') &&
            Tools::isSubmit('hash')
        ) {

            if (
                Tools::getValue('hash') ===
                $this->module->generateHash()
            ) {
                // Ajax action
                $action_method = 'ajax'.ucfirst(Tools::getValue('action'));

                try {

                    if (method_exists($this, $action_method)) {
                        $this->$action_method();
                    } else {
                        $this->_ajax_errors[] =
                            $this->module->translate('Method does not exist.');
                    }

                } catch (Exception $e) {
                    $this->_ajax_errors[] =
                        $e->getMessage();
                }

            } else {
                $this->setAjaxReponseVar('message', 'Invalid method.');
            }

            if (empty($this->_ajax_errors)) {
                $this->setAjaxReponseVar('status', 'success');
            } else {
                $this->setAjaxReponseVar('status', 'warning');
                $this->setAjaxReponseVar('errors', $this->_ajax_errors);
            }

            $this->setAjaxReponseVar('data', $this->_data);

            echo json_encode(
                $this->getAjaxResponse()
            );
        }

        die();
    }

    /**
     * Save cart Point
     *
     * @return bool
     */
    public function ajaxSaveCartPoint()
    {
        // In case you serialize form from ajax
        $point = Tools::getValue('point');
        $id_cart = (int)Tools::getValue('id_cart');
        if (!empty($id_cart) && $id_cart == $this->context->cart->id) {
            if (!empty($point) && is_array($point)) {
                // Check locker's required fields
                $required_fields = [
                    'StationId',
                    'Lat',
                    'Lng',
                    'ShortName',
                    'ZipCode',
                    'City',
                    'AddressLine1',
                    'AddressLine2',
                ];
                $result = array_diff($required_fields, array_keys($point));
                if (empty($result)) {
                    // Update Delivery Station information DB first
                    if (AfCleverPointDeliveryStation::updateDeliveryStationData($point)) {
                        // Get station id
                        $sql =
                            sprintf(
                                "SELECT `%s` FROM `%s%s` WHERE `StationId` = '%s'",
                                AfCleverPointDeliveryStation::$definition['primary'],
                                _DB_PREFIX_,
                                AfCleverPointDeliveryStation::$definition['table'],
                                $point['StationId']
                            );
                        $id_cleverpoint_delivery_station = Db::getInstance()->getValue($sql);
                        if (!empty($id_cleverpoint_delivery_station)) {
                            // Update delivery request
                            $cp_delivery_request = AfCleverPointDeliveryRequest::getObject(['id_cart' => $id_cart]);
                            if (empty($cp_delivery_request->id_order)) {
                                $cp_delivery_request->id_cart = $id_cart;
                                // Hold current id_address_delivery in case we need to restore it
                                if (empty($cp_delivery_request->previous_id_address_delivery)) {
                                    $cp_delivery_request->previous_id_address_delivery = $this->context->cart->id_address_delivery;
                                }
                                $cp_delivery_request->id_order = 0;
                                $cp_delivery_request->id_cleverpoint_delivery_station = $id_cleverpoint_delivery_station;
                                // Create delivery address for this point and assign it as cart's delivery address
                                $id_address_delivery = $this->module->updateCartAddressFromStationDelivery(
                                    $this->context->cart,
                                    $point,
                                    $this->_ajax_errors
                                );
                                // Save id_address_delivery to object
                                $cp_delivery_request->id_address_delivery = $id_address_delivery;
                                $cp_delivery_request->PickupComments = $point['Name'];
                                if ($cp_delivery_request->save() && !empty($id_address_delivery)) {

                                    $this->setAjaxReponseVar(
                                        'message',
                                        $this->module->translate(
                                            'Clever point details updated successfully.',
                                            [],
                                            'Modules.Afcleverpoint.Ajax'
                                        )
                                    );

                                    $this->context->smarty->assign(
                                        'point',
                                        $point
                                    );
                                    $this->context->smarty->assign(
                                        'afcp_tpl_vars',
                                        [
                                            'module_url' => $this->context->link->getBaseLink(
                                                ).'modules/'.$this->module->name,
                                        ]
                                    );
                                    $this->_data['point_info_html'] =
                                        $this->context->smarty->fetch(
                                            'module:'.$this->module->name.'/views/templates/front/clever-point-locker-info.tpl'
                                        );
                                } else {
                                    $this->_ajax_errors[] =
                                        $this->module->translate(
                                            'Unable to complete request. Please try again later.',
                                            [],
                                            'Modules.Afcleverpoint.Ajax'
                                        );
                                }
                            }
                        } else {
                            $this->_ajax_errors[] =
                                $this->module->translate(
                                    'Unable to get Clever Point details. Please try again later.',
                                    [],
                                    'Modules.Afcleverpoint.Ajax'
                                );
                        }

                    } else {
                        $this->_ajax_errors[] =
                            $this->module->translate(
                                'Unable to save Clever Point details. Please try again later.',
                                [],
                                'Modules.Afcleverpoint.Ajax'
                            );
                    }
                } else {
                    $this->_ajax_errors [] =
                        $this->module->translate(
                            'Please select a clever point in order to proceed.',
                            [],
                            'Modules.Afcleverpoint.Ajax'
                        );
                }
            }
        } else {
            $this->_ajax_errors[] = $this->module->translate(
                'Invalid request',
                [],
                'Modules.Afcleverpoint.Ajax'
            );
        }

        return true;
    }

    /**
     * Save cart Point
     *
     * @return bool
     */
    public function ajaxClearCartPoint()
    {
        // In case you serialize form from ajax
        $id_cart = (int)Tools::getValue('id_cart');
        if (!empty($id_cart) && $id_cart == $this->context->cart->id) {
            $cp_delivery_request = AfCleverPointDeliveryRequest::getObject(['id_cart' => $id_cart]);
            if (empty($cp_delivery_request->id_order)) {
                $cp_delivery_request->id_cleverpoint_delivery_station = 0;
                if ($cp_delivery_request->save()) {
                    $this->setAjaxReponseVar(
                        'message',
                        $this->module->translate(
                            'Clever point details cleared successfully.'
                        )
                    );
                } else {
                    $this->_ajax_errors[] = $this->module->translate(
                        'Unable to clear clever point details please try again.'
                    );
                }
            } else {
                $this->_ajax_errors[] = $this->module->translate(
                    'There is already an order for this cart.'
                );
            }
        }

        return true;
    }

    /**
     * Save deliver_order_with_cp db field
     *
     * @return true
     */
    public function ajaxSaveCleverPointDelivery()
    {
        $data = [];
        parse_str(Tools::getValue('data'), $data);
        // In case you serialize form from ajax
        $id_cart = (isset($data['id_cart']) ? (int)$data['id_cart'] : 0);
        $deliver_order_with_cp = (isset($data['deliver_order_with_cp']) ? (int)$data['deliver_order_with_cp'] : 1);
        if (!empty($id_cart) && $id_cart == $this->context->cart->id) {
            $cp_delivery_request = AfCleverPointDeliveryRequest::getObject(['id_cart' => $id_cart]);
            if (empty($cp_delivery_request->id_order)) {
                $cp_delivery_request->deliver_order_with_cp = $deliver_order_with_cp;
                $cp_delivery_request->id_cart = $id_cart;
                if (!Configuration::get('AFCP_COSTTOCUST')) {
                    $cp_delivery_request->service_cost = 0;
                }
                if ($cp_delivery_request->save()) {
                    $this->assignCartTotals($cp_delivery_request);
                    $this->setAjaxReponseVar(
                        'message',
                        $this->module->translate(
                            'Option saved successfully.'
                        )
                    );
                } else {
                    $this->_ajax_errors[] = $this->module->translate(
                        'Unable to save option please try again.'
                    );
                }
            } else {
                $this->_ajax_errors[] = $this->module->translate(
                    'There is already an order for this cart.'
                );
            }
        } else {
            $this->_ajax_errors[] = $this->module->translate(
                'Invalid parameters.'
            );
        }

        return true;
    }

    /**
     * Calculate service cost
     *
     * @return true
     */
    public function ajaxCalculateCleverPointServiceCost()
    {
        $data = [];
        parse_str(Tools::getValue('data'), $data);
        // In case you serialize form from ajax
        $id_cart = (isset($data['id_cart']) ? (int)$data['id_cart'] : 0);
        $deliver_order_with_cp = (isset($data['deliver_order_with_cp']) ? (int)$data['deliver_order_with_cp'] : 0);
        if (!empty($id_cart) && $id_cart == $this->context->cart->id) {
            $cp_delivery_request = AfCleverPointDeliveryRequest::getObject(['id_cart' => $id_cart]);
            if (empty($cp_delivery_request->id_order)) {

                $cp_price = 0;
                if (Configuration::get('AFCP_COSTTOCUST')) {
                    // Calculate Clever Point prices
                    $cp = $this->module->cpApiInstance();
                    $cp_price = $cp->getPrices();
                    $cp_price = is_numeric($cp_price) ? (float)$cp_price : 0;
                }

                // Update Delivery's request costs
                $cp_delivery_request = AfCleverPointDeliveryRequest::getObject(
                    ['id_cart' => $id_cart]
                );
                $cp_delivery_request->id_cart = $id_cart;
                $cp_delivery_request->service_cost = $cp_price;
                $cp_delivery_request->deliver_order_with_cp = $deliver_order_with_cp;
                if ($cp_delivery_request->save()) {
                    // Return cart totals
                    $this->assignCartTotals($cp_delivery_request);
                    $this->setAjaxReponseVar(
                        'message',
                        $this->module->translate(
                            'Cost saved successfully.'
                        )
                    );

                } else {
                    $this->_ajax_errors[] = $this->module->translate(
                        'Unable to save cost please try again.'
                    );
                }
            } else {
                $this->_ajax_errors[] = $this->module->translate(
                    'There is already an order for this cart.'
                );
            }
        } else {
            $this->_ajax_errors[] = $this->module->translate(
                'Invalid parameters.'
            );
        }

        return true;
    }

    /**
     * Get cart totals
     *
     * @param $cp_delivery_request
     * @return void
     */
    public function assignCartTotals($cp_delivery_request)
    {
        ## BEGIN Return JS variables to handle costs
        if (Configuration::get('AFCP_COSTTOCUST')) {
            // Return delivery request details
            $this->_data['cp_delivery_request'] = (array)$cp_delivery_request;
            $this->_data['service_cost_text'] = $this->module->translate('Clever Point cost');

            // Service cost formatted
            $this->_data['service_cost_html'] =
                Tools::getContextLocale($this->context)->formatPrice(
                    $cp_delivery_request->service_cost,
                    $this->context->currency->iso_code
                );

            $cart_total =
                    $this->context->cart->getOrderTotal(true, Cart::BOTH);

            $this->_data['cart_total_with_service_formatted'] =
                Tools::getContextLocale($this->context)->formatPrice(
                    $cart_total,
                    $this->context->currency->iso_code
                );

            // Return service cost cart summary line
            $this->context->smarty->assign(
                'tpl_vars',
                [
                    'service_cost_formatted' => $this->_data['service_cost_html'],
                    'service_cost_text' => $this->module->translate('Clever Point cost'),
                ]
            );

            $html = $this->context->smarty->fetch(
                'module:'.$this->module->name.'/views/templates/front/checkout/_partials/cart-summary-service-cost.tpl'
            );
            $this->_data['service_cost_summary_html'] = $html;

            // Return checkout summary line as well
            $html = $this->context->smarty->fetch(
                'module:'.$this->module->name.'/views/templates/front/checkout/_partials/checkout-summary-service-cost.tpl'
            );
            $this->_data['service_cost_checkout_summary_html'] = $html;
        }
        ## END Return JS variables to handle costs
    }
}
