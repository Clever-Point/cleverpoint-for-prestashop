<?php
class Ps_CashondeliveryOverride extends Ps_Cashondelivery
{
    public function hookPaymentOptions(array $params)
    {
        $hide_method = false;

        Hook::exec('actionCleverPointOverrideCod', array(
            'args' => $params,
            'module' => $this->name,
            'hide_method' => &$hide_method
        ));

        if ($hide_method) {
            return false;
        }

        return parent::hookPaymentOptions($params);
    }
}