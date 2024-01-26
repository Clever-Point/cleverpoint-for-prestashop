/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 *
 *  @author    Afternet <info@afternet.gr>
 *  @copyright Afternet
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * Ajax before send
 *
 * @param action
 * @param data
 */
function afcpAjaxBeforeSend(action = '', data = []) {
    $('.afcp-ajax').each(function( index, element ) {
        $( element ).attr('disabled', 'disabled');
    });
}

/**
 * Ajax complete
 * @param action
 * @param data
 */
function afcpAjaxComplete(action = '', data = []) {
    $('.afcp-ajax').each(function( index, element ) {
        $( element ).removeAttr('disabled');
    });

    if (action === 'getOrderShippingInformation') {
        if (typeof (data.html) != 'undefined') {
            $('#cp_order_admin_shipping_info').html(data.html);
        }
    }

    if (action === 'createVoucher' || action === 'cancelVoucher') {
        window.location.reload();
    }

    if (action == 'refreshCleverPointCarriers') {
        if (data.status == 'success') {
            window.location.reload();
        }
    }
}

/**
 * Display ajax message
 *
 * @param type
 * @param message
 */
function afcpAjaxDisplayMessagesCart(type, message) {
    if (type == 'warning') {
        $.growl.error({ title: "", message: message });
    } else if (type == 'info') {
        $.growl({ title: "", message: message });
    } else if (type == 'success') {
        $.growl.notice({ title: "", message: message });
    } else {
        $.growl.notice({ title: "", message: message });
    }
}

/**
 * Display ajax message in admin
 *
 * @param type
 * @param message
 */
function afcpAjaxDisplayMessagesAdmin(type, message) {
    if (type == 'warning') {
        $.growl.error({ title: "", message: message });
    } else if (type == 'info') {
        $.growl({ title: "", message: message });
    } else if (type == 'success') {
        $.growl.notice({ title: "", message: message });
    } else {
        $.growl.notice({ title: "", message: message });
    }
}