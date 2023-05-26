{**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 *
 *  @author    Afternet <info@afternet.gr>
 *  @copyright Afternet
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}

<div class="af-cleverpoint-wrap">
    {* About Clever Point *}
    {include file="{$afcp_tpl_vars.tpl_dir}/views/templates/front/clever-point-info.tpl"}

    {if ($afcp_tpl_vars.display_method != 'popup')}
    <div id="af-cleverpoint-checkout-messages" class=""></div>
    {/if}

    {if $afcp_tpl_vars.shipping_cost_to_customer}
    {* Cost analysis & courier cost *}
    <div id="af-cleverpoint-costs" class="af-cleverpoint-costs pb-1">
        <div class="af-cleverpoint-costs__row">
            <div class="af-cleverpoint-costs__label">{l s='Service cost' mod='afcleverpoint'}:</div>
            <div class="af-cleverpoint-costs__value">{$afcp_tpl_vars.service_cost_formatted}</div>
        </div>
        <div class="af-cleverpoint-costs__row">
            <div class="af-cleverpoint-costs__label">{l s='Courier cost' mod='afcleverpoint'}:</div>
            <div class="af-cleverpoint-costs__value">{$afcp_tpl_vars.shipping_cost_formatted}</div>
        </div>
    </div>
    {/if}
    <div id="af-cleverpoint-selected-point js-selected-shop-placed" class="pb-1">
        {if $afcp_tpl_vars.station_customer_selected}
            {include file="{$afcp_tpl_vars.tpl_dir}/views/templates/front/clever-point-locker-info.tpl" point=$afcp_tpl_vars.delivery_station}
        {/if}
    </div>

    {* Display map *}
    {include file="{$afcp_tpl_vars.tpl_dir}/views/templates/front/map/{$afcp_tpl_vars.display_method}.tpl"}
</div>
