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

/**
 * This Controller receives customer after approval on checkout page
 */
class AfCleverPointValidationModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    /**
     * @var PaymentModule
     */
    public $module;

    public function postProcess()
    {
        if (false === $this->checkIfContextIsValid() || false === $this->checkIfPaymentOptionIsAvailable()) {
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                [
                    'step' => 1,
                ]
            ));
        }

        $customer = new Customer($this->context->cart->id_customer);

        if (false === Validate::isLoadedObject($customer)) {
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                [
                    'step' => 1,
                ]
            ));
        }
        // Add service cost to cart total price
        $sql =
            sprintf(
                "SELECT `service_cost` FROM `%s%s` WHERE `id_cart` = %d",
                _DB_PREFIX_,
                AfCleverPointDeliveryRequest::$definition['table'],
                $this->context->cart->id
            );
        $service_cost = Db::getInstance()->getValue($sql);
        if (!is_numeric($service_cost)) {
            $service_cost = 0;
        }

        $this->module->validateOrder(
            (int) $this->context->cart->id,
            (int) Configuration::get('AFCP_CLEVERPOINT_OS'),
            (float)$this->context->cart->getOrderTotal(true, Cart::BOTH),
            $this->module->translate('Cash on delivery'),
            null,
            [],
            (int) $this->context->currency->id,
            false,
            $customer->secure_key
        );

        Tools::redirect($this->context->link->getPageLink(
            'order-confirmation',
            true,
            (int) $this->context->language->id,
            [
                'id_cart' => (int) $this->context->cart->id,
                'id_module' => (int) $this->module->id,
                'id_order' => (int) $this->module->currentOrder,
                'key' => $customer->secure_key,
            ]
        ));
    }

    /**
     * Check if the context is valid
     * - Cart is loaded
     * - Cart has a Customer
     * - Cart has a delivery address
     * - Cart has an invoice address
     * - Cart doesn't contains virtual product
     *
     * @return bool
     */
    private function checkIfContextIsValid()
    {
        return true === Validate::isLoadedObject($this->context->cart)
            && true === Validate::isUnsignedInt($this->context->cart->id_customer)
            && true === Validate::isUnsignedInt($this->context->cart->id_address_delivery)
            && true === Validate::isUnsignedInt($this->context->cart->id_address_invoice)
            && false === $this->context->cart->isVirtualCart();
    }

    /**
     * Check that this payment option is still available in case the customer changed
     * his address just before the end of the checkout process
     *
     * @return bool
     */
    private function checkIfPaymentOptionIsAvailable()
    {
        $modules = Module::getPaymentModules();

        if (empty($modules)) {
            return false;
        }

        foreach ($modules as $module) {
            if (isset($module['name']) && $this->module->name === $module['name']) {
                return true;
            }
        }

        return false;
    }
}
