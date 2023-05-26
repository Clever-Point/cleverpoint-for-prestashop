<?php
class Cart extends CartCore
{
    public function getOrderTotal(
        $withTaxes = true,
        $type = Cart::BOTH,
        $products = null,
        $id_carrier = null,
        $use_cache = false,
        bool $keepOrderPrices = false
    ) {
        $cart_total = parent::getOrderTotal(
            $withTaxes,
            $type,
            $products,
            $id_carrier,
            $use_cache,
            $keepOrderPrices
        );

        $service_cost = 0;
        $return = false;

        Hook::exec('actionCleverPointCartGetOrderTotal', array(
            'object' => &$this,
            'withTaxes' => $withTaxes,
            'type' => $type,
            'products' => $products,
            'id_carrier' => $id_carrier,
            'use_cache' => $use_cache,
            'keepOrderPrices' => $keepOrderPrices,
            'service_cost' => &$service_cost,
            'return' => &$return
        ));

        if ($return) {
            $cart_total = (float) Tools::ps_round(($cart_total + $service_cost), 2);
        }

        return $cart_total;
    }
    public function getPackageShippingCost(
        $id_carrier = null,
        $use_tax = true,
        Country $default_country = null,
        $product_list = null,
        $id_zone = null,
        bool $keepOrderPrices = false
    ) {
        $total = 0;
        $return = false;

        Hook::exec('actionCleverPointCartGetPackageShippingCost', array(
            'object' => &$this,
            'id_carrier' => &$id_carrier,
            'use_tax' => &$use_tax,
            'default_country' => &$default_country,
            'product_list' => &$product_list,
            'id_zone' => &$id_zone,
            'total' => &$total,
            'return' => &$return
        ));

        if ($return) {
            return ($total !== false ? (float) Tools::ps_round((float) $total, 2) : false);
        }

        $shipping_cost = parent::getPackageShippingCost(
            $id_carrier,
            $use_tax,
            $default_country,
            $product_list,
            $id_zone,
            $keepOrderPrices
        );

        if ($shipping_cost !== false) {
            return $shipping_cost + (float) Tools::ps_round((float) $total, 2);
        }

        return false;
    }
}
