{**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 *
 *  @author    Afternet <info@afternet.gr>
 *  @copyright Afternet
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}

<form class="defaultForm form-horizontal" id="af_cleverpoint_carrier_form">
    <div class="panel" id="fieldset_3_3">
        <div class="panel-heading">
            <i class="icon-cogs"></i> {l s='Carrier settings' mod='afcleverpoint'}
        </div>
        <div class="form-wrapper">
            <div class="form-group">
                <label class="control-label col-lg-1"></label>
                <div class="col-lg-9">
                    {if (!empty($tpl_vars.ps_carriers))}
                        <table class="table">
                            <thead>
                            <tr>
                                <th>{l s='Carrier' mod='afcleverpoint'}</th>
                                <th>{l s='Clever Point Carrier' mod='afcleverpoint'}</th>
                            </tr>
                            </thead>
                            <tbody>
                            {assign var='carrier_mapping' value=$tpl_vars.carrier_mapping}
                            {foreach from=$tpl_vars.ps_carriers item=carrier}
                                {assign var='id_reference_carrier' value=$carrier.id_reference}
                                <tr>
                                    <td>{$carrier.name}</td>
                                    <td>
                                        <select name="carrier[{$id_reference_carrier}]">
                                            <option value="">{l s='-- Select --' mod='afcleverpoint'}</option>
                                            {foreach from=$tpl_vars.cp_carriers key=k item=cp_carrier}
                                                <option value="{$cp_carrier.Id}" {if (isset($carrier_mapping[$id_reference_carrier]) && $carrier_mapping[$id_reference_carrier] == $cp_carrier.Id)}selected="selected"{/if}>
                                                    {$cp_carrier.Name}
                                                </option>
                                            {/foreach}
                                        </select>
                                    </td>
                                </tr>
                            {/foreach}
                            </tbody>
                        </table>
                    {else}
                        <div class="alert alert-warning">
                            {l s='Please make sure you define Prestashop carriers and you have selected which carriers will use Clever Point.' mod='afcleverpoint'}
                        </div>
                    {/if}
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Refresh carriers list' mod='afcleverpoint'}</label>
                <div class="col-lg-9">
                    <a href="#" class="btn btn-primary afcp-ajax" id="admin-clever-point-refresh-carriers">{l s='Refresh' mod='afcleverpoint'}</a>
                    <p class="help-block">
                        {l s='Refresh Clever Point carrier list.' mod='afcleverpoint'}
                    </p>
                </div>
            </div>
        </div><!-- /.form-wrapper -->

        <div class="panel-footer">
            <button type="submit" value="1" id="admin-clever-point-save-carriers"
                    name="admin-clever-point-save-carriers"
                    class="btn btn-default pull-right">
                <i class="process-icon-save"></i> {l s='Save' mod='afcleverpoint'}
            </button>
        </div>
    </div>
</form>
{if ($tpl_vars.opc_is_enabled)}
<form class="defaultForm form-horizontal" id="af_cleverpoint_opc_form">
    <div class="panel" id="fieldset_3_3">
        <div class="panel-heading">
            <i class="icon-cogs"></i> {l s='One page checkout for Prestashop' mod='afcleverpoint'}
        </div>
        <div class="form-wrapper">
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Version compatibility' mod='afcleverpoint'}</label>
                <div class="col-lg-9">
                    {if ($tpl_vars.opc_compatibility)}
                        {l s='Module is tested with the following versions:' mod='afcleverpoint'} <code>{$tpl_vars.opc_compatibility.min}</code> {l s='to' mod='afcleverpoint'} <code>{$tpl_vars.opc_compatibility.max}</code>
                    {/if}
                    <p class="help-block">
                        {l s='Make sure you are have a compatible version of module' mod='afcleverpoint'} <a href="https://addons.prestashop.com/en/express-checkout-process/6841-one-page-checkout-for-prestashop.html" target="_blank">One page checkout for Prestashop</a>
                    </p>
                </div>
            </div>
        </div><!-- /.form-wrapper -->
        <div class="panel-footer">
        </div>
    </div>
</form>
{/if}