<?php
class CodFeeOverride extends CodFee
{
    public function hookPaymentOptions($params)
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