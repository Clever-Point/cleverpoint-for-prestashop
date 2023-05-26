{**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 *
 *  @author    Afternet <info@afternet.gr>
 *  @copyright Afternet
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}

 <div class="af-cleverpoint">
    <input type="hidden" name="af-cleverpoint-selected-point" id="af-cleverpoint-selected-point" value="{if isset($afcp_tpl_vars.delivery_station.StationId)}{$afcp_tpl_vars.delivery_station.StationId}{/if}" >
    <div class="cleverpointBox__new-label">Νέα υπηρεσία!</div>
    <div class="cleverpointBox__upper-title">Παράλαβε όποτε θες εσύ, χωρίς να περιμένεις τον courier!.</div>
    <div class="cleverpointBox">
        <div class="cleverpointBox__inner">
            <label class="cleverpointBox__label" for="afcp_pickup_from_cleverpoint">
                <div class="cleverpointBox__row">
                    <div class="cleverpointBox__col cleverpointBox__col--auto">
                        <input id="afcp_pickup_from_cleverpoint" type="checkbox" {if ($afcp_tpl_vars.deliver_order_with_cp)}checked="checked"{/if} value="1"  name="afcp_pickup_from_cleverpoint" >
                    </div>
                    <div class="cleverpointBox__col cleverpointBox__col--auto">
                        <img src="{$afcp_tpl_vars.module_url}/views/img/cleverpoint-logo.png" alt="Cleverpoint logo" />
                    </div>
                    <div class="cleverpointBox__col cleverpointBox__col-md--col">
                        <div class="cleverpointBox__content">
                            <div class="cleverpointBox__title">Παραλαβή από Clever Point </div>
                            <div class="cleverpointBox__text">Η μεταφορική - courier θα αφήσει το δέμα στο σημείο της επιλογής σου για να το παραλάβεις όποτε σε βολεύει χωρίς άγχος. Ένα έξυπνο σημείο είναι πάντα δίπλα σου.</div>
                        </div>
                    </div>
                    <div class="cleverpointBox__col cleverpointBox__col--auto">
                        <span id="af-cleverpoint-costs" class="cleverpointBox__price">
                            {if !$afcp_tpl_vars.shipping_cost_to_customer}
                                {l s='Free' mod='afcleverpoint'}
                            {/if}
                        </span>
                    </div>
                </div>
            </label>
        </div>
    </div>
    {* About Clever Point *}
    {include file="{$afcp_tpl_vars.tpl_dir}/views/templates/front/clever-point-info.tpl"}    


    <div id="af-cleverpoint-checkout-container" class="af-cleverpoint-wrap mb-1" {if !($afcp_tpl_vars.station_customer_selected)}style="display:none"{/if}>

        <div id="af-cleverpoint-selected-point" class="js-selected-shop-placed pb-1">
            {if $afcp_tpl_vars.station_customer_selected}
                {include file="{$afcp_tpl_vars.tpl_dir}/views/templates/front/clever-point-locker-info.tpl" point=$afcp_tpl_vars.delivery_station}
            {/if}
        </div>

        {if ($afcp_tpl_vars.display_method != 'popup')}
            <div id="af-cleverpoint-checkout-messages" class=""></div>
        {/if}
        
        {* Display map *}
        {include file="{$afcp_tpl_vars.tpl_dir}/views/templates/front/map/{$afcp_tpl_vars.display_method}.tpl"}

{*        {l s='Available carriers:' mod='afcleverpoint'} {', '|implode:$afcp_tpl_vars.carrier_names}*}
    </div>
</div>