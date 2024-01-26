{**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 *
 *  @author    Afternet <info@afternet.gr>
 *  @copyright Afternet
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}

<div class="af-cleverpoint-popup">
    <div class="modal fade in" id="cleverpointmodal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">

                    <h4 class="modal-title h6 text-sm-center"
                        id="myModalLabel">{l s='Pick a Clever point' mod='afcleverpoint'}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Κλείσιμο">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
                            <path d="M11.25 1.88828L10.2875 0.925781L5.75 5.46328L1.2125 0.925781L0.25 1.88828L4.7875 6.42578L0.25 10.9633L1.2125 11.9258L5.75 7.38828L10.2875 11.9258L11.25 10.9633L6.7125 6.42578L11.25 1.88828Z" fill="currentColor"/>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">                    
                    <div class="js-selected-shop-placed">
                        {if $afcp_tpl_vars.station_customer_selected}
                            {include file="{$afcp_tpl_vars.tpl_dir}/views/templates/front/clever-point-locker-info.tpl" point=$afcp_tpl_vars.delivery_station}
                        {/if}
                    </div>
                    <div id="af-cleverpoint-checkout-messages" class=""></div>
                    <div id="clevermap"
                         style="width:100%; height:{if isset($afcp_tpl_vars.map_height)}{$afcp_tpl_vars.map_height}{/if}px;"></div>
                </div>
            </div>
        </div>
    </div>
    <button type="button" id="clever-point-popup-btn" class="btn btn-primary clever-point-btn" data-toggle="modal"
            data-target="#cleverpointmodal">
        {if !$afcp_tpl_vars.station_customer_selected}
            {l s='Pick a Clever point' mod='afcleverpoint'}
        {else}
            {l s='Change Clever point' mod='afcleverpoint'}
        {/if}
    </button>
  
</div>