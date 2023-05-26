{**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 *
 *  @author    Afternet <info@afternet.gr>
 *  @copyright Afternet
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}

{if isset( $confirmation )}
    <div class="alert alert-success">
        {l s='Settings Updated' mod='afcleverpoint'}
    </div>
{/if}

{if isset( $errors )}
    <div class="alert alert-warning">
        <ul>
            {foreach from=$errors item=err}
                <li>{$err|escape:'htmlall':'UTF-8'}</li>
            {/foreach}
        </ul>
    </div>
{/if}

<form class="defaultForm form-horizontal">
    <div class="panel" id="fieldset_top">
        <div class="panel-heading">
            <i class="icon-info"></i> {l s='Documentation' mod='afcleverpoint'}
        </div>
        <div class="form-wrapper">
            <div class="form-group">
                <label class="control-label col-lg-4">
                    {l s='User manual' mod='afcleverpoint'}
                </label>
                <div class="col-lg-6">
                    <a href="{$afcp_module_url|escape:'htmlall':'UTF-8'}/readme_el.pdf" target="_blank" class="btn btn-primary">{l s='View' mod='afcleverpoint'}</a>
                </div>
            </div>
        </div>
        <div class="panel-footer">
        </div>
    </div>
</form>