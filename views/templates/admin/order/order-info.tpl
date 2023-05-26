{**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 *
 *  @author    Afternet <info@afternet.gr>
 *  @copyright Afternet
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}

<div class="col-12">
    <strong>{l s='Clever Point Station:' mod='afcleverpoint'}</strong><br>
    {if (!empty($tpl_vars.cp_delivery_station->StationId))}
        {$tpl_vars.cp_delivery_station->Name}
        {$tpl_vars.cp_delivery_station->AddressLine1}&nbsp;{$tpl_vars.cp_delivery_station->AddressLine2}
        {$tpl_vars.cp_delivery_station->City}&nbsp;{$tpl_vars.cp_delivery_station->ZipCode}
    {else}
        -
    {/if}
</div>