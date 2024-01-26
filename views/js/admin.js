/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 *
 *  @author    Afternet <info@afternet.gr>
 *  @copyright Afternet
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

$(document).ready(function (e) {
    $(document).on('click', '.afcp-order-form-action', function (e) {
        e.preventDefault();
        afcpAdminOrderAjaxCall($(this).data('action'), $('#clever_point_order_admin_form').serialize());
    });

    $(document).on('click', '#admin-clever-point-save-carriers', function (e) {
        e.preventDefault();
        afcpAdminOrderAjaxCall('saveCleverPointCarriers', $('#af_cleverpoint_carrier_form').serialize());
    });

    $(document).on('click', '#admin-clever-point-refresh-carriers', function (e) {
        e.preventDefault();
        afcpAdminOrderAjaxCall('refreshCleverPointCarriers', $('#af_cleverpoint_carrier_form').serialize());
    });

    $(document).on('click', '#admin-clever-point-save-categories', function (e) {
        e.preventDefault();
        afcpAdminOrderAjaxCall('saveCleverPointCategories', $('#af_cleverpoint_carrier_form_categories').serialize());
    });

    if (afcp_current_controller == 'AdminOrders' && afcp_id_order > 0) {
        // Load shipping information
        afcpAdminOrderAjaxCall('getOrderShippingInformation', $('#clever_point_order_admin_form').serialize());
    }

    // Initialize select2 fields
    if (typeof afcp_select2_fields != 'undefined') {
        $(afcp_select2_fields).each(function (key, field) {
            afcpSelect2(field);
        });
    }
});

/**
 * Ajax call admin
 *
 * @param string request
 * @param object data
 * @return void
 */
function afcpAdminOrderAjaxCall(request, data) {

    $.ajax({
        method: "POST",
        url: afcp_ajax_url,
        beforeSend: function () {
            afcpAjaxBeforeSend(request, data);
        },
        cache: false,
        data: {
            ajax: true,
            action: afcp_action,
            request: request,
            hash: afcp_hash,
            id_order: afcp_id_order,
            data: data
        },
    }).done(function (data, textStatus, jqXHR) {
        var obj = jQuery.parseJSON(data);
        if (obj.status == 'success') {
            if (request != 'getOrderShippingInformation') {
                afcpAjaxDisplayMessagesAdmin(obj.status, obj.message);
            }
        } else {
            if (typeof (obj.errors) != 'undefined') {
                $.each(obj.errors, function (key, value) {
                    afcpAjaxDisplayMessagesAdmin('warning', value);
                });
            }
        }

        afcpAjaxComplete(request, obj);

    }).fail(function (jqXHR, textStatus, errorThrown) {
        console.log(errorThrown);
    }).always(function (jqXHR, textStatus, errorThrown) {
        //console.log(errorThrown);
    });
}

/**
 * Select2 fields
 * @param field
 */
function afcpSelect2(field) {
    var selector = '#' + field.name;
    $(selector).css('width', '100%');
    $(selector).select2({tags: field.values});
    $(selector).on("change", function () {
        var element_val = $(selector).attr('id') + '_val';
        $("#" + element_val).html($(selector).val());
    });

    // $(selector).select2("container").find("ul.select2-choices").sortable({
    //     containment: 'parent',
    //     start: function () {
    //         $(selector).select2("onSortStart");
    //     },
    //     update: function () {
    //         $(selector).select2("onSortEnd");
    //     }
    // });
}