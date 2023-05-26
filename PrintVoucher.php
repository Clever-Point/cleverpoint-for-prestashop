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

try {
    require_once('../../config/config.inc.php');
    require_once('../../init.php');
    if (!defined('_PS_VERSION_')) {
        exit;
    }

    if (Module::isEnabled('afcleverpoint')) {
        $module = Module::getInstanceByName('afcleverpoint');
        $ShipmentAwb = Tools::getValue('ShipmentAwb');
        $print_type = Tools::getValue('print_type');
        $hash = Tools::getValue('hash');
        if (!empty($ShipmentAwb) && !empty($print_type) && !empty($hash)) {
            if ($module->generateHash($ShipmentAwb) == $hash) {
                $cp = $module->cpApiInstance();
                $result = $cp->apiCall("/Vouchers/?awbs=" . $ShipmentAwb."&template=$print_type", null, 'GET');
                if (!$cp->hasError($result)) {
                    $data = base64_decode($result['Content']['Document']);
                    header('Content-type: application/pdf');
                    header('Content-Disposition: inline; filename="' .$ShipmentAwb. '.pdf"');
                    header('Content-transfer-Encoding: binary');
                    header('Accept-Ranges: bytes');
                    echo $data;
                } else {
                    echo '<pre>'.print_r($result, true).'</pre>';
                }
            }
        }

    }

} catch (Exception $e) {
    echo $e->getMessage();
}
