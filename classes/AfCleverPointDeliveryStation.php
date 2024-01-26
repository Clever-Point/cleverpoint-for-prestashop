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

class AfCleverPointDeliveryStation extends ObjectModel
{
    /**
     * Delivery Station fields. Some fields in map response aren't mentioned in doc
     * @doc https://docs.cleverpoint.gr/cleverpoint-api/delivery-stations/
     */
    /** @var int id_cleverpoint_delivery_station */
    public $id_cleverpoint_delivery_station;
    /** @var string StationId */
    public $StationId;
    /** @var string prefix */
    public $Prefix;
    /** @var string Code */
    public $Code;
    /** @var string Name */
    public $Name;
    /** @var string Category */
    public $Category;
    /** @var string shortName */
    public $ShortName;
    /** @var string addressLine1 */
    public $AddressLine1;
    /** @var string addressLine2 */
    public $AddressLine2;
    /** @var string city */
    public $City;
    /** @var string perfecture */
    public $Perfecture;
    /** @var string zipCode code */
    public $ZipCode;
    /** @var string phones */
    public $Phones;
    /** @var string emails */
    public $Emails;
    /** @var float MaxDimension */
    public $MaxDimension;
    /** @var float MaxWeight */
    public $MaxWeight;
    /** @var string Schedule */
    public $Schedule;
    /** @var string WorkHoursFormattedWithDaysV2 */
    public $WorkHoursFormattedWithDaysV2;
    /** @var JSON $ExtraVars */
    public $ExtraVars;
    /** @var float Latitude */
    public $Lat;
    /** @var float Longitude */
    public $Lng;
    /** @var string Object creation date in mysql format Y-m-d H:i:s */
    public $date_add;
    /** @var string Object last modification date in mysql format Y-m-d H:i:s */
    public $date_upd;

    public static $definition = array(
        'table' => 'af_cleverpoint_delivery_station',
        'primary' => 'id_cleverpoint_delivery_station',
        'multilang' => false,
        'multilang_shop' => false,
        'fields' => array(
            'id_cleverpoint_delivery_station' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'StationId' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHTML'),
            'Prefix' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHTML'),
            'Code' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHTML'),
            'Name' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHTML'),
            'Category' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHTML'),
            'ShortName' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHTML'),
            'AddressLine1' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHTML'),
            'AddressLine2' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHTML'),
            'City' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHTML'),
            'Perfecture' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHTML'),
            'ZipCode' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHTML'),
            'Phones' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHTML'),
            'Emails' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHTML'),
            'MaxDimension' => ['type' => self::TYPE_FLOAT],
            'MaxWeight' => ['type' => self::TYPE_FLOAT],
            'Schedule' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHTML'),
            'WorkHoursFormattedWithDaysV2' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHTML'),
            'ExtraVars' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHTML'),
            'Lat' => ['type' => self::TYPE_FLOAT],
            'Lng' => ['type' => self::TYPE_FLOAT],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ),
    );

    /**
     * Saves current object to database (add or update).
     *
     * @param bool $null_values
     * @param bool $auto_date
     *
     * @return bool Insertion result
     *
     * @throws PrestaShopException
     */
    public function save($null_values = false, $auto_date = true)
    {
        // Format JSON fields before save
        $this->Schedule = json_encode($this->Schedule);
        $this->WorkHoursFormattedWithDaysV2 = json_encode($this->WorkHoursFormattedWithDaysV2, JSON_UNESCAPED_UNICODE);
        return parent::save($null_values, $auto_date);
    }

    /**
     * Get object
     *
     * @param $params
     * @return object
     */
    public static function getObject($params = [])
    {
        $id = null;
        $where_arr = [];

        if (!empty($params)) {
            foreach ($params as $column => $value) {
                $db_value = $value;
                if (self::$definition['fields'][$column]['type'] == self::TYPE_STRING) {
                    $db_value = "'".$value."'";
                }
                $where_arr[] = sprintf(
                    "`%s` = %s",
                    $column,
                    $db_value
                );
            }
            $sql = sprintf(
                "SELECT `%s` FROM `%s%s` WHERE %s",
                self::$definition['primary'],
                _DB_PREFIX_,
                self::$definition['table'],
                implode(' AND ', $where_arr)
            );

            $id = Db::getInstance()->getValue($sql);
        }

        return new AfCleverPointDeliveryStation($id);
    }

    /**
     * Update delivery station data
     *
     * @param array $data
     * @param array $errors
     * @return boolean
     */
    public static function updateDeliveryStationData($data, &$errors = [])
    {
        try {
            if (isset($data['StationId'])) {
                $cp_delivery_station = AfCleverPointDeliveryStation::getObject(['StationId' => $data['StationId']]);
                // Save station's extra vars
                $ExtraVars = [];
                foreach ($data as $key => $value) {
                    if (property_exists($cp_delivery_station, $key)) {
                        $cp_delivery_station->{$key} = $value;
                    } else {
                        $ExtraVars[$key] = $value;
                    }
                }
                // Save extra vars
                $cp_delivery_station->ExtraVars = Tools::jsonEncode($ExtraVars);
                try {
                    $cp_delivery_station->save();
                    return true;
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }

        return false;
    }

    /**
     * Check if currect point is operational for COD
     * @return bool
     */
    public function isOperationalForCOD()
    {
        if (!empty($this->ExtraVars)) {
            $extra_vars = Tools::jsonDecode($this->ExtraVars);
            return (isset($extra_vars->IsOperationalForCOD) && filter_var($extra_vars->IsOperationalForCOD, FILTER_VALIDATE_BOOLEAN));
        }

        return false;
    }
}