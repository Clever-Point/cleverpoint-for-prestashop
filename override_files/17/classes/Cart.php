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
            'return' => &$return,
        ));

        if ($return) {
            $cart_total = (float)Tools::ps_round(($cart_total + $service_cost), 2);
        }

        return $cart_total;
    }
}
