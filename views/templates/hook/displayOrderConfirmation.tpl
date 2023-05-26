{**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 *
 *  @author    Afternet <info@afternet.gr>
 *  @copyright Afternet
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}

<p>{l s='Your order on' mod='afcleverpoint'} {$afcp_tpl_vars.shop_name} {l s='is placed successfully.' mod='afcleverpoint'}</p>

<ul>
    <li>
        {l s='Payment amount.' mod='afcleverpoint'}
        <span class="price"><strong>{$afcp_tpl_vars.total_to_pay}</strong></span>
    </li>

    <li>
        {l s='Shipping cost' mod='afcleverpoint'}
        <span class="price"><strong>{$afcp_tpl_vars.cleverpoint_order_data.shipping_cost_formatted}</strong></span>
    </li>

    <li>
        {l s='Service cost' mod='afcleverpoint'}
        <span class="price"><strong>{$afcp_tpl_vars.cleverpoint_order_data.service_cost_formatted}</strong></span>
    </li>
</ul>

{include file="{$afcp_tpl_vars.tpl_dir}/views/templates/front/clever-point-locker-info.tpl" point=$afcp_tpl_vars.delivery_station}

<p>{l s='An email has been sent to you with this information.' mod='afcleverpoint'}</p>

<p>{l s='For any questions or for further information, please contact our' mod='afcleverpoint'}
    <a href="{$afcp_tpl_vars.contact_url|escape:'html'}">{l s='customer service department.' mod='afcleverpoint'}</a>
</p>

{if isset($afcp_tpl_vars.cleverpoint_order_data)}
    <script>
        document.addEventListener("DOMContentLoaded", function(event) {
            if ($('div.order-confirmation-table tr:last').length > 0) {
                $('div.order-confirmation-table tr:last').prev('tr').prev('tr').clone().insertBefore('div.order-confirmation-table tr:last').addClass('clever-point-line-fee');
                var selector = $('.clever-point-line-fee td');
                selector.first().html('{l s='Clever Point cost' mod='afcleverpoint'}');
                selector.last().html('{$afcp_tpl_vars.cleverpoint_order_data.service_cost_formatted|escape:'htmlall':'UTF-8'}');
                $('.clever-point-line-fee').prev('tr').find('td:last').html('{$afcp_tpl_vars.cleverpoint_order_data.shipping_cost_formatted|escape:'htmlall':'UTF-8'}');
            }
        });
    </script>
{/if}