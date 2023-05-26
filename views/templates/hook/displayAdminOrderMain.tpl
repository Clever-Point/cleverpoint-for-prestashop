{**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 *
 *  @author    Afternet <info@afternet.gr>
 *  @copyright Afternet
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}

<div class="card mt-2" id="clever_point_order_admin_block">
    <div class="card-header">
        <h3 class="card-header-title">{l s='Clever Point' mod='afcleverpoint'}</h3>
    </div>
    <div class="card-body">
        <div id="cp_order_admin_shipping_info" class="row"></div>
        <form id="clever_point_order_admin_form" class="form-horizontal">
            <input type="hidden" name="afcp_id_order" id="afcp_id_order" value="{$tpl_vars.id_order}"/>
            <input type="hidden" name="id_cleverpoint_delivery_request" id="id_cleverpoint_delivery_request" value="{$tpl_vars.cp_delivery_request->id_cleverpoint_delivery_request}"/>
            {if ($tpl_vars.afcp_voucher_management eq 'prestashop')}
            <div class="form-group row ">
                <label for="parcels" class="form-control-label label-on-top col-12">{l s='Parcels' mod='afcleverpoint'}</label>
                <div class="col-6">
                    <input type="number" name="parcels" id="parcels" class="form-control" value="{$tpl_vars.cp_delivery_request->parcels}">
                </div>
            </div>
            <div class="form-group row ">
                <label for="order_total_weight" class="form-control-label label-on-top col-12">{l s='Weight (in kg)' mod='afcleverpoint'}</label>
                <div class="col-6">
                    <input type="number" name="order_total_weight" id="order_total_weight" class="form-control" value="{$tpl_vars.order_total_weight}">
                </div>
            </div>
            <div class="form-group row ">
                <label for="ShipmentAwb" class="form-control-label label-on-top col-12">{l s='Courier Voucher' mod='afcleverpoint'}</label>
                <div class="col-6">
                    <input type="text" name="ShipmentAwb" id="ShipmentAwb" class="form-control" value="{$tpl_vars.ShipmentAwb}">
                </div>
            </div>
            <div class="form-group row type-choice">
                <label for="ExternalCarrierId" class="form-control-label label-on-top col-12">{l s='Courier' mod='afcleverpoint'}</label>
                <div class="col-12">
                    <select class="custom-select form-control" name="ExternalCarrierId" id="ExternalCarrierId">
                        <option value="0">{l s='Pick the preferred carrier' mod='afcleverpoint'}</option>
                        {foreach $tpl_vars.cp_carriers as $key => $carrier}
                            <option value="{$carrier.Id}" {if $tpl_vars.cp_delivery_request->ExternalCarrierId == $carrier.Id}selected="selected"{/if}>{$carrier.Name}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="form-group row ">
                <label for="PickupComments" class="form-control-label label-on-top col-12">{l s='Comments' mod='afcleverpoint'}</label>
                <div class="col-12">
                    <textarea name="PickupComments" id="PickupComments" class="form-control">{$tpl_vars.cp_delivery_request->PickupComments}</textarea>
                </div>
            </div>
            <div class="text-right">
                <button type="submit" id="afcp-order-form-submit"
                        class="btn btn-primary afcp-order-form-action" data-action="createVoucher" {if !empty($tpl_vars.cp_delivery_request->ShipmentMasterId)}disabled{/if}>
                    <i class="material-icons">local_shipping</i> {l s='Create voucher' mod='afcleverpoint'}
                </button>
                <button type="submit" id="afcp-order-form-submit"
                        class="btn btn-danger afcp-order-form-action" data-action="cancelVoucher" {if empty($tpl_vars.cp_delivery_request->ShipmentMasterId)}disabled{/if}>
                    <i class="material-icons">cancel</i> {l s='Cancel voucher' mod='afcleverpoint'}
                </button>
            </div>
            {/if}
        </form>
        {if ($tpl_vars.afcp_voucher_management eq 'prestashop')}
        <form method="post" {if !empty({$tpl_vars.print_url})}action="{$tpl_vars.print_url}"{/if} target="_blank" enctype="multipart/form-data">
            <input type="hidden" name="hash" id="hash" value="{$tpl_vars.print_hash}">
            <input type="hidden" name="ShipmentAwb" id="ShipmentAwb" value="{$tpl_vars.cp_delivery_request->ShipmentAwb}">
            <hr>
            <div class="form-group row type-choice">
                <label for="print_type" class="form-control-label label-on-top col-12">{l s='Print voucher' mod='afcleverpoint'}</label>
                <div class="col-12">
                    <select name="print_type" id="print_type" class="custom-select form-control">
                        <option value="singlepdf">{l s='Single (A4 - 1 / page)' mod='afcleverpoint'}</option>
                        <option value="singlepdf_a5">{l s='Single (A5 - 1 / page)' mod='afcleverpoint'}</option>
                        <option value="image_double">{l s='Double (A4 - 2 / page)' mod='afcleverpoint'}</option>
                        <option value="image">{l s='Triple (A4 - 3 / page)' mod='afcleverpoint'}</option>
                        <option value="voucher_quad">{l s='Quadruple (A4 - 4 / page)' mod='afcleverpoint'}</option>
                        <option value="image10">{l s='Single (A7 - 1 / page)' mod='afcleverpoint'}</option>
                    </select>
                </div>
            </div>
            <div class="text-right">
                <button type="submit" id="afcp-order-form-print" {if (empty({$tpl_vars.print_url}))}disabled{/if}
                        class="btn btn-primary">
                    <i class="material-icons">print</i> {l s='Print voucher' mod='afcleverpoint'}
                </button>
            </div>
        </form>
        {/if}
    </div>
</div>