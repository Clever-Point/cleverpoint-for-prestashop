/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 *
 *  @author    Afternet <info@afternet.gr>
 *  @copyright Afternet
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

$(document).ajaxComplete(function (event, xhr, settings) {
    if (typeof settings.url != "undefined" && settings.url == '?selectDeliveryOption') {
        if (typeof afCleverPointInitializeMap !== 'undefined') {
            afCleverPointInitializeMap();
        }
    }
});

// Check if visitor selected a CleverPoint once order is placed
$(document).ready(function() {
    tc_confirmOrderValidations['cleverpoint_validation'] = function () {
        if ($('#afcp_pickup_from_cleverpoint').is(':checked')) {
            if ($('#af-cleverpoint-selected-point').val() == '') {
                alert(afcp_text_select_point);
                return false;
            }
        }
        return true;
    };
});