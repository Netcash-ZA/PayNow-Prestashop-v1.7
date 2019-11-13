{*Copyright 2019 Netcash (Pty) Ltd*}
<div class='netcashPayNowForm'>
<form id='netcashPayNowForm' action="{$data.paynow_url}" method="post">
    <p class="payment_module">
    {foreach $data.info as $k=>$v}
        <input type="hidden" name="{$k}" value="{$v}" />
    {/foreach}
     <a href='#' onclick='document.getElementById("netcashPayNowForm").submit();return false;'>{$data.netcash_paynow_text}
      {if $data.netcash_paynow_logo=='on'} <img align='{$data.netcash_paynow_align}' alt='Pay Now via Sage Pay Now' title='Pay Now' src="{$base_dir}modules/paynow/logo.png">{/if}</a>
       <noscript><input type="image" src="{$base_dir}modules/paynow/logo.png"></noscript>
    </p>
</form>
</div>
<div class="clear"></div>
