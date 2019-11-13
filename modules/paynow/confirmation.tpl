{*
* Copyright 2019 Netcash (Pty) Ltd
*}

{if $status == 'ok'}
    <p>{l s='Your order on' mod='paynow'} <span class="bold">{$shop_name}</span> {l s='is complete.' mod='paynow'}
        <br /><br /><span class="bold">{l s='Your order will be shipped as soon as possible.' mod='paynow'}</span>
        <br /><br />{l s='For any questions or for further information, please contact our' mod='paynow'} <a href="{$link->getPageLink('contact', true)}">{l s='customer support' mod='paynow'}</a>.
    </p>
{else}
    {if $status == 'pending'}
        <p>{l s='Your order on' mod='paynow'} <span class="bold">{$shop_name}</span> {l s='is pending.' mod='paynow'}
            <br /><br /><span class="bold">{l s='Your order will be shipped as soon as we receive your bankwire.' mod='paynow'}</span>
            <br /><br />{l s='For any questions or for further information, please contact our' mod='paynow'} <a href="{$link->getPageLink('contact', true)}">{l s='customer support' mod='paynow'}</a>.
        </p>
    {else}
        <p class="warning">
            {l s='We noticed a problem with your order. If you think this is an error, you can contact our' mod='paynow'}
            <a href="{$link->getPageLink('contact', true)}">{l s='customer support' mod='paynow'}</a>.
        </p>
    {/if}
{/if}
