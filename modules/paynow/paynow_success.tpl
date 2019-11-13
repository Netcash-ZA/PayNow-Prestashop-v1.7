{*Copyright 2019 Netcash (Pty) Ltd*}
<p>{l s='Your order on' mod='paynow'} <span class="bold">{$shop_name}</span> {l s='is complete.' mod='paynow'}
    <br /><br />
    {l s='You paid via Netcash Pay Now.' mod='paynow'}
    <br /><br /><span class="bold">{l s='Your order will be sent shortly.' mod='paynow'}</span>
    <br /><br />{l s='For any questions or for further information, please contact our' mod='paynow'} <a href="{$link->getPageLink('contact-form.php', true)}">{l s='customer support' mod='paynow'}</a>.
</p>