CREATE TABLE IF NOT EXISTS `PREFIX_af_cleverpoint_delivery_request` (
    `id_cleverpoint_delivery_request` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `id_cart` int(11) DEFAULT 0,
    `id_address_delivery` int(11) DEFAULT 0,
    `previous_id_address_delivery` int(11) DEFAULT 0,
    `id_order` int(11) DEFAULT 0,
    `id_cleverpoint_delivery_station` int(11) DEFAULT 0,
    `is_cod` int(1) NOT NULL DEFAULT 0,
    `shipping_cost` decimal(20,6) DEFAULT 0.000000,
    `service_cost` decimal(20,6) DEFAULT 0.000000,
    `delivered` int(1) NOT NULL DEFAULT 0,
    `deliver_order_with_cp` int(1) NOT NULL DEFAULT 0,
    `ShipmentMasterId` varchar(100) DEFAULT NULL,
    `ExternalCarrierId` varchar(100) DEFAULT NULL,
    `ExternalCarrierName` varchar(50) DEFAULT NULL,
    `ShipmentAwb` varchar(100) DEFAULT NULL,
    `PickupComments` varchar(255) DEFAULT NULL,
    `parcels` INT (11) DEFAULT 1,
    `json_response` TEXT DEFAULT NULL,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `PREFIX_af_cleverpoint_delivery_station` (
    `id_cleverpoint_delivery_station` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `StationId` varchar(50) DEFAULT NULL,
    `Prefix` varchar(50) DEFAULT NULL,
    `Code` varchar(50) DEFAULT NULL,
    `Name` varchar(255) NOT NULL,
    `Category` varchar(100) DEFAULT NULL,
    `ShortName` varchar(50) DEFAULT NULL,
    `AddressLine1` varchar(255) NOT NULL,
    `AddressLine2` varchar(255) DEFAULT NULL,
    `City` varchar(255) DEFAULT NULL,
    `Perfecture` varchar(50) DEFAULT NULL,
    `ZipCode` varchar(12) NOT NULL,
    `Phones` varchar(255) DEFAULT NULL,
    `Emails` varchar(255) DEFAULT NULL,
    `MaxDimension` decimal(20,6) DEFAULT NULL,
    `MaxWeight` decimal(20,6) DEFAULT NULL,
    `Schedule` varchar(255) DEFAULT NULL,
    `WorkHoursFormattedWithDaysV2` varchar(255) DEFAULT NULL,
    `IsOperationalForCOD` INT(1) NOT NULL DEFAULT 1,
    `Lat` decimal(13,8) DEFAULT NULL,
    `Lng` decimal(13,8) DEFAULT NULL,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;