{**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 *
 *  @author    Afternet <info@afternet.gr>
 *  @copyright Afternet
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}

<dv class="cleverpointShop">
    <div class="cleverpointShop__row">
        <div class="cleverpointShop__col cleverpointShop__col--auto">
           <div class="cleverpointShop__align">
               <span class="cleverpointShop__label">Επιλεγμένο σημείο παραλαβής:</span>
               <span><img src="{$afcp_tpl_vars.module_url}/views/img/pin.png" width="46px" height="56px" alt="pin" /></span>              
           </div>
        </div>
        <div class="cleverpointShop__col cleverpointShop__col--col">
            <div class="cleverpointShop__content">
                <div class="cleverpointShop__title">{$point.Name}</div>
                <div class="cleverpointShop__text">{$point.AddressLine1}&nbsp;{$point.AddressLine2}</div>
                <div class="cleverpointShop__text">{$point.City}&nbsp;{$point.ZipCode}</div>
            </div>
        </div>
    </div>
</dv>
    
    