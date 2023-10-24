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

namespace CleverPoint\Override;

use PrestaShopAutoload;
use Tools;
use Module;

class InstallOverride
{
    public $module;
    public $override_dir_module; // Module's override dir
    public $override_dir_ps; // Prestashop override dir

    /**
     * Default constructor
     *
     * @param $module
     */
    public function __construct($module)
    {
        $this->module = $module;
        $module_path = _PS_MODULE_DIR_ . $this->module->name . '/';
        $this->override_dir_module = $module_path . 'override_files/' . ($this->module->is16() ? '16/' : '17/');
        $this->override_dir_ps = $module_path . 'override/';
    }

    /**
     * Process override action
     *
     * @param $action ('addOverride'|'removeOverride')
     * @return void
     */
    public function processOverride($action)
    {
        foreach ($this->getClassData() as $data) {
            $this->process($action, $data['path']);
        }
        if ($this->module->is16()) {
            $overrides_cache_file = _PS_CACHE_DIR_ . 'class_index.php';
            if (file_exists($overrides_cache_file)) {
                unlink($overrides_cache_file);
            }
        }
    }

    /**
     * Process action
     *
     * @param $action
     * @param $file_path
     * @return int|string
     */
    public function process($action, $file_path)
    {
        $result = false;
        $custom_path = $this->override_dir_module . $file_path;
        $tmp_native_path = $this->override_dir_ps . $file_path;
        if (file_exists($custom_path)) {
            if (is_writable(dirname($tmp_native_path))) {
                try {
                    Tools::copy($custom_path, $tmp_native_path); // copy to /override/ for native processing
                    $class_name = basename($custom_path, '.php');
                    $result &= $this->module->$action($class_name);
                } catch (Exception $e) {
                    $result = $e->getMessage();
                }
                unlink($tmp_native_path);
            } else {
                $dir_name = str_replace(_PS_ROOT_DIR_, '', dirname($tmp_native_path)) . '/';
                $result = sprintf('Directory: %s is not writable ', $dir_name);
            }
        }
        
        return $result;
    }

    /**
     * Get class data
     *
     * @param $extended
     * @return array
     */
    public function getClassData($extended = false)
    {
        $data = [];
        $autoload = PrestaShopAutoload::getInstance();
        foreach (Tools::scandir($this->override_dir_module, 'php', '', true) as $file) {
            $class_name = basename($file, '.php');
            if ($class_name != 'index') {
                // Get filename
                $filename = pathinfo($file, PATHINFO_FILENAME);

                if ($filename == 'codfee') {
                    $path = 'modules/codfee/codfee.php';
                } elseif ($filename == 'ps_cashondelivery') {
                    $path = 'modules/ps_cashondelivery/ps_cashondelivery.php';
                } else {
                    $path = $autoload->getClassPath($class_name . 'Core');
                }

                $data[$class_name] = ['path' => $path];
                if ($extended) {
                    $data[$class_name] += [
                        'installed_methods' => $this->installedMethods($path),
                    ];
                }
            }
        }

        return $data;
    }

    /**
     *
     * @param $file_path
     * @return bool|string
     */
    public function installedMethods($file_path)
    {
        $shop_override_path = _PS_OVERRIDE_DIR_ . $file_path;
        $module_override_path = $this->override_dir_module . $file_path;
        $methods_to_override = $already_overriden = [];
        if (file_exists($module_override_path) && !is_dir($module_override_path)) {
            $lines = file($module_override_path);
            foreach ($lines as $line) {
                if (Tools::substr(trim($line), 0, 6) == 'public') {
                    $key = trim(current(explode('(', $line)));
                    $methods_to_override[$key] = 0;
                }
            }
        }
        $name_length = Tools::strlen($this->module->name);
        if (file_exists($shop_override_path) && !is_dir($shop_override_path)) {
            $lines = file($shop_override_path);
            foreach ($lines as $i => $line) {
                if (Tools::substr(trim($line), 0, 6) == 'public') {
                    $key = trim(current(explode('(', $line)));
                    if (isset($methods_to_override[$key])) {
                        unset($methods_to_override[$key]);
                        if (!isset($lines[$i - 4])
                            || Tools::substr(trim($lines[$i - 4]), -$name_length) !== $this->module->name) {
                            $key = explode('function ', $key);
                            if (isset($key[1])) {
                                $already_overriden[] = $key[1] . '()';
                            }
                        }
                    }
                }
            }
        }
        $result = (bool) !$methods_to_override;
        if ($already_overriden) {
            $result = implode(', ', $already_overriden);
        }

        return $result;
    }
}
