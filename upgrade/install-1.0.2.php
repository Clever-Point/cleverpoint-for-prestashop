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

function upgrade_module_1_0_2($module_obj)
{
    if (!defined('_PS_VERSION_')) {
        exit;
    }
    
    $sql = "ALTER TABLE `"._DB_PREFIX_."af_cleverpoint_delivery_station` ADD `ExtraVars` TEXT NULL DEFAULT NULL AFTER `WorkHoursFormattedWithDaysV2`;";
    
    Db::getInstance()->execute($sql);
    
    return true;
}