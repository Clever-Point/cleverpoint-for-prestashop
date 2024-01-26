/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 *
 *  @author    Afternet <info@afternet.gr>
 *  @copyright Afternet
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

$(document).ready(function () {

    $(document).on('click', '#afcp_pickup_from_cleverpoint', function () {
        afCleverPointHideOrDisplayWrap();
    });

    // Initialize clevermap
    if (!afcp_opc_enabled) {

        afCleverPointHideOrDisplayWrap(false);

        afCleverPointInitializeMap();

        // Comfirm btn listener
        if (afcp_checkout_btn_selector != '') {
            //@ToDo save to Cookie vars
            $(document).on('click', afcp_checkout_btn_selector, function (e) {
                if ($('#afcp_pickup_from_cleverpoint').is(':checked')) {
                    if ($('#af-cleverpoint-selected-point').val() == '') {
                        alert(afcp_text_select_point);
                        return false;
                    } else {
                        return true;
                    }
                }
                return true;
            });
        }
    }
});

/**
 * Clever Point checkout wrapper
 */
function afCleverPointHideOrDisplayWrap(update = true) {
    // Data that will be sent to ajax
    var data = [{name: "id_cart", value: afcp_id_cart}];
    var deliver_order_with_cp = 0;

    if ($('#afcp_pickup_from_cleverpoint').is(':checked')) {
        if ($('#af-cleverpoint-checkout-container').is(':hidden')) {
            $('#af-cleverpoint-checkout-container').show();
        }
        deliver_order_with_cp = 1;
    } else {
        if ($('#af-cleverpoint-checkout-container').is(':visible')) {
            $('#af-cleverpoint-checkout-container').hide();
        }
    }

    data.push({name: "deliver_order_with_cp", value: deliver_order_with_cp});

    if (update) {
        afcpFrontAjaxCall('saveCleverPointDelivery', $.param(data));
        //if (deliver_order_with_cp) {
            afCleverPointRefreshCarrierList();
        //}
    }

    // Calculate service cost
    afcpFrontAjaxCall('calculateCleverPointServiceCost', $.param(data));
}

/**
 * Save Clever Point pickup details
 *
 * @param point
 * @return void
 */
function afcpSaveCartPoint(point) {
    $.ajax({
        method: "POST",
        url: afcp_ajax_url,
        beforeSend: function () {
        },
        cache: false,
        data: {
            ajax: true,
            action: 'saveCartPoint',
            hash: afcp_hash,
            point: point,
            id_cart: afcp_id_cart
        },
    }).done(function (data, textStatus, jqXHR) {
        try {
            var obj = jQuery.parseJSON(data);
            if (obj.status == 'success') {
                afcpAjaxDisplayMessagesCheckout(obj.status, obj.message);
                if (typeof (obj.data) != 'undefined') {
                    if (typeof (obj.data.point_info_html) != 'undefined') {
                        $(".js-selected-shop-placed").html(obj.data.point_info_html);
                    }
                }

                if ($("#clever-point-popup-btn").length > 0) {
                    $("#clever-point-popup-btn").text(afcp_text_popup_btn);
                }

                $('#af-cleverpoint-selected-point').val(point.StationId);

            } else {
                if (typeof (obj.errors) != 'undefined') {
                    afcpAjaxDisplayMessagesCheckout('warning', obj.errors);
                }
            }

            afcpFrontAjaxComplete('saveCartPoint', obj);

        } catch (err) {
            afcpAjaxDisplayMessagesCheckout('warning', err.message);
        }

    }).fail(function (jqXHR, textStatus, errorThrown) {
        afcpAjaxDisplayMessagesCheckout('warning', afcp_msg_error_500);
    }).always(function (jqXHR, textStatus, errorThrown) {
        //afcpAjaxDisplayMessagesCheckout('warning', afcp_msg_error_500);
    });
}

/**
 * Clear Clever Point  details
 *
 * @param id_cart
 * @return void
 */
function afcpClearCartPoint() {
    $.ajax({
        method: "POST",
        url: afcp_ajax_url,
        beforeSend: function () {

        },
        cache: false,
        data: {
            ajax: true,
            action: 'clearCartPoint',
            hash: afcp_hash,
            id_cart: afcp_id_cart
        },
    }).done(function (data, textStatus, jqXHR) {
        try {
            var obj = jQuery.parseJSON(data);
            if (obj.status == 'success') {
                afcpAjaxDisplayMessagesCheckout(obj.status, obj.message);
                $(".js-selected-shop-placed").html('');
            } else {
                if (typeof (obj.errors) != 'undefined') {
                    afcpAjaxDisplayMessagesCheckout('warning', obj.errors);
                }
            }

            afcpFrontAjaxComplete('clearCartPoint', obj);
        } catch (err) {
            afcpAjaxDisplayMessagesCheckout('warning', err.message);
        }


    }).fail(function (jqXHR, textStatus, errorThrown) {
        console.log(errorThrown);
    }).always(function (jqXHR, textStatus, errorThrown) {
        console.log(errorThrown);
    });
}

/**
 * Ajax call admin
 *
 * @param action
 * @param data
 * @return void
 */
function afcpFrontAjaxCall(action, data) {

    $.ajax({
        method: "POST",
        url: afcp_ajax_url,
        beforeSend: function () {
            afcpFrontAjaxBeforeSend(action, data);
        },
        cache: false,
        data: {
            ajax: true,
            action: action,
            hash: afcp_hash,
            data: data
        },
    }).done(function (data, textStatus, jqXHR) {
        var obj = jQuery.parseJSON(data);
        if (obj.status == 'success') {
            if (action != 'saveCleverPointDelivery' && action != 'calculateCleverPointServiceCost') {
                afcpAjaxDisplayMessagesCheckout(obj.status, obj.message);
            }
        } else {
            if (typeof (obj.errors) != 'undefined') {
                $.each(obj.errors, function (key, value) {
                    afcpAjaxDisplayMessagesCheckout('warning', value);
                });
            }
        }

        afcpFrontAjaxComplete(action, obj);

    }).fail(function (jqXHR, textStatus, errorThrown) {
        console.log(errorThrown);
    }).always(function (jqXHR, textStatus, errorThrown) {
        //console.log(errorThrown);
    });
}

/**
 * Before Ajax handler
 * @param action
 * @param data
 */
function afcpFrontAjaxBeforeSend(action, data) {
    $('.afcp-ajax').each(function (index, element) {
        $(element).attr('disabled', 'disabled');
    });
}

/**
 * Ajax complete handler
 * @param action
 * @param result
 */
function afcpFrontAjaxComplete(action, result) {
    $('.afcp-ajax').each(function (index, element) {
        $(element).removeAttr('disabled');
    });

    if (action === 'calculateCleverPointServiceCost') {
        // Refresh cart totals
        afCleverPointRefreshTotals(result);
    }

    if (action === 'clearCartPoint') {
        $('#af-cleverpoint-selected-point').val('');
    }
}

/**
 * Refresh carrier list
 */
function afCleverPointRefreshCarrierList() {
    if ($('#afcp_pickup_from_cleverpoint').is(':checked')) {
        // Disable carriers that don't support Clever Point
        $("div.delivery-options input[type='radio']").each(function (index, element) {
            var id_carrier = $(element).val().replace(",", "");
            if (afcp_carriers.length) {
                if (!afcp_carriers.includes(id_carrier)) {
                    if ($(element).is(':checked')) {
                        $(element).prop('checked', false);
                        $(element).removeAttr('checked');
                    }
                    $(element).attr('disabled', 'disabled');
                }
            }
        });

        // Check if there is any delivery option checked else click default
        var selected_carrier = afGetSelectedCarrier();
        var selected_carrier_value = afGetSelectedCarrierId(selected_carrier);

        if (
            parseInt(selected_carrier_value) > 0 &&
            afcp_default_cp_carrier > 0 &&
            selected_carrier.is(':disabled') &&
            !afcp_carriers.includes(selected_carrier)
        ) {
            // Click on default carrier that supports pickup from CleverPoint
            $("div.delivery-options #delivery_option_" + afcp_default_cp_carrier).click();
        }
    } else {
        // Enable all carriers again
        $("div.delivery-options input[type='radio']").each(function (index, element) {
            $(element).removeAttr('disabled');
        });
    }
}

/**
 * Refresh totals
 * @param result
 */
function afCleverPointRefreshTotals(result) {
    // Service cost to customer?
    if (afcp_costtocust) {
        // Check there is summary table in bottom of the page
        var has_checkout_summary = false;
        if ($('#order-summary-content').length > 0) {
            has_checkout_summary = true;
        }
        if (typeof (result.data) != 'undefined') {
            if (typeof (result.data.cp_delivery_request) != 'undefined') {
                var cp_delivery_request = result.data.cp_delivery_request;
            }
            var deliver_order_with_cp = 0;
            if (typeof (cp_delivery_request) != 'undefined') {
                deliver_order_with_cp = (cp_delivery_request.deliver_order_with_cp === 1);
            }
            if (deliver_order_with_cp) {
                if (typeof (result.data.service_cost_summary_html) != 'undefined' && $('#cart-subtotal-clever-point').length == 0) {
                    if (afcp_opc_enabled) {
                        $('div.cart-summary #cart-subtotal-shipping').after(result.data.service_cost_summary_html);
                    } else {
                        $('section#js-checkout-summary div.cart-summary-subtotals-container').append(result.data.service_cost_summary_html);
                    }
                }
                $('#af-cleverpoint-costs').html(result.data.service_cost_html);
                if (has_checkout_summary) {
                    $('#order-summary-content .order-confirmation-table table tr.total-value').before(result.data.service_cost_checkout_summary_html);
                }
            } else {
                // Hide Clever Point costs
                $('.clever-point-service-cost-line').each(function (index, element) {
                    $(element).remove();
                });
            }
            // Update cart total
            if (typeof (result.data.cart_total_with_service_formatted) != 'undefined') {
                if (afcp_opc_enabled) {
                    $('div.cart-summary div.cart-total span.value').html(result.data.cart_total_with_service_formatted);
                } else {
                    $('section#js-checkout-summary div.cart-summary-totals .cart-total span.value').html(result.data.cart_total_with_service_formatted);
                }
            }
        }
    }
}

/**
 * Display ajax message
 *
 * @param type
 * @param message
 */
function afcpAjaxDisplayMessagesCheckout(type, message) {
    var _html_message = '';
    if (typeof message === 'object') {
        $.each(message, function (key, value) {
            _html_message = _html_message + '<li>' + value + '</li>';
        });
    } else {
        _html_message = '<li>' + message + '</li>';
    }
    if ($('#af-cleverpoint-checkout-messages').is(':hidden')) {
        $('#af-cleverpoint-checkout-messages').show();
    }
    $('#af-cleverpoint-checkout-messages').removeClass();
    $('#af-cleverpoint-checkout-messages').addClass('alert');
    $('#af-cleverpoint-checkout-messages').addClass('alert-' + type);
    $('#af-cleverpoint-checkout-messages').html('<ul>' + _html_message + '</ul>');
}

$( document ).on( "ajaxComplete", function( event, xhr, settings ) {
    if (typeof (settings.url) != 'undefined') {
        // Check if customer selects a carrier
        if (settings.url.includes("selectDeliveryOption")) {
            afCleverPointHideOrDisplayWrap(true);
        }
    }
} );

/**
 * Function to initialize CleverMap
 */
function afCleverPointInitializeMap()
{
    if ($('#af-cleverpoint-initialize-map').val() != 1) {
        clevermap({
            selector: '#clevermap',
            cleverPointKey: afcp_cleverpoint_api_key,
            arcgisMapKey: afcp_arcgis_api_key,       // 'YOUR ARCGIS API KEY'
            googleMapKey: afcp_googlemaps_api_key,       // 'YOUR GOOGLE MAP KEY'
            header: afcp_header,
            defaultAddress: afcp_address_delivery,
            defaultCoordinates: null,
            defaultCleverPoint: false,
            singleSelect: afcp_single_select,
            display: {
                addressBar: afcp_address_bar,
                pointList: afcp_point_list,
                pointInfoType: afcp_info_type
            },
            filters: {
                codAmount: 0
            },
            onclear: () => {
                afcpClearCartPoint();
            },
            onselect: (point) => {
                afcpSaveCartPoint(point);
            },
            oninitialized: () => {
            }
        });

        // Set hidden value when map is initialized
        $('#af-cleverpoint-initialize-map').val(1);
    }
}

/**
 * Get selected carrier element
 * @return object
 */
function afGetSelectedCarrier()
{
    var selected_carrier = '';

    if (!afcp_opc_enabled) {
        selected_carrier = $("div.delivery-options input[type='radio']");
    } else {
        selected_carrier = $("div.delivery-options div.delivery-option-row.active input[type='radio']");
    }

    return selected_carrier;
}

/**
 * Get selected carrier element
 * @return int
 */
function afGetSelectedCarrierId(selected_carrier)
{
    return parseInt(selected_carrier.val().replace(",", ""));
}