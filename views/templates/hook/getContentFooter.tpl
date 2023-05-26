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