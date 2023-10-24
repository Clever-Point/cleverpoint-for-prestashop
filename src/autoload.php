<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 *
 *  @author    Afternet <info@afternet.gr>
 *  @copyright Afternet
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

$folders = [
    __DIR__ . '/Api/*.php',
    __DIR__ . '/Override/*.php'
];

foreach ($folders as $folder) {
    $classes = glob($folder);
    if (!empty($classes)) {
        foreach ($classes as $class) {
            if (!preg_match('/index.php/', $class)) {
                try {
                    require_once($class);
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
            }
        }
    }
}