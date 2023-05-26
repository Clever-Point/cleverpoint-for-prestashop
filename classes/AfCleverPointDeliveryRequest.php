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

class AfCleverPointDeliveryRequest extends ObjectModel
{

    /* @doc https://docs.cleverpoint.gr/cleverpoint-api/shipping/ */

    /** @var int database id */
    public $id_cleverpoint_delivery_request;
    /** @var int cart ID */
    public $id_cart;
    /** @var int $id_address_delivery Clever point address created for the selected point */
    public $id_address_delivery;
    /** @var int $previous_id_address_delivery */
    public $previous_id_address_delivery; // In case customer doesn't want pick-up from Clever Point we need to restore cart's id_address_delivery
    /** @var int order ID */
    public $id_order;
    /** @var string AfCleverPointDeliveryStation::$id_af_cleverpoint_delivery_station */
    public $id_cleverpoint_delivery_station;
    /** @var bool is_cod */
    public $is_cod;
    /** @var float Shipping cost */
    public $shipping_cost;
    /** @var float Clever Point cost */
    public $service_cost;
    /** @var bool $delivered */
    public $delivered;
    /** @var bool $deliver_order_with_cp true/false if order will be delivered with Clever Point */
    public $deliver_order_with_cp;
    /** @var string ShipmentMasterId */
    public $ShipmentMasterId;
    /** @var string ExternalCarrierId */
    public $ExternalCarrierId;
    /** @var string ExternalCarrierName */
    public $ExternalCarrierName;
    /** @var string ShipmentAwb */
    public $ShipmentAwb;
    /** @var string PickupComments */
    public $PickupComments;
    /** @var int parcels */
    public $parcels;
    /** @var string json_response */
    public $json_response;
    /** @var string Object creation date in mysql format Y-m-d H:i:s */
    public $date_add;
    /** @var string Object last modification date in mysql format Y-m-d H:i:s */
    public $date_upd;

    public static $definition = array(
        'table' => 'af_cleverpoint_delivery_request',
        'primary' => 'id_cleverpoint_delivery_request',
        'multilang' => false,
        'multilang_shop' => false,
        'fields' => array(
            'id_cleverpoint_delivery_request' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_cart' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_address_delivery' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'previous_id_address_delivery' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_order' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_cleverpoint_delivery_station' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'is_cod' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'shipping_cost' => ['type' => self::TYPE_FLOAT,'validate' => 'isPrice'],
            'service_cost' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'delivered' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'deliver_order_with_cp' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'ShipmentMasterId' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHTML'),
            'ExternalCarrierId' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHTML'),
            'ExternalCarrierName' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHTML'),
            'ShipmentAwb' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHTML'),
            'PickupComments' => array('type' => self::TYPE_STRING, 'validate' => 'isMessage', 'size' => 255),
            'parcels' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'default' => 1),
            'json_response' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHTML'),
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate']
        ),
    );

    /**
     * constructor.
     *
     * @param null $id_cleverpoint_delivery_request
     * @param null $idLang
     * @param null $idShop
     */
    public function __construct($id_cleverpoint_delivery_request = null, $idLang = null, $idShop = null)
    {
        parent::__construct($id_cleverpoint_delivery_request, $idLang, $idShop);
        if (empty($id_cleverpoint_delivery_request)) {
            $this->shipping_cost = 0;
            $this->service_cost = 0;
        }
    }

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
        return parent::save($null_values, $auto_date);
    }

    /**
     * Get object
     *
     * @param $params
     * @return mixed
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

        return new AfCleverPointDeliveryRequest($id);
    }
}