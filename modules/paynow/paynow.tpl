{*Copyright 2019 Sage Pay (Pty) Ltd*}
<div class='sagePayNowForm'>
<form id='sagePayNowForm' action="{$data.paynow_url}" method="post">
    <p class="payment_module">
    {foreach $data.info as $k=>$v}
        <input type="hidden" name="{$k}" value="{$v}" />
    {/foreach}
     <a href='#' onclick='document.getElementById("sagePayNowForm").submit();return false;'>{$data.sage_paynow_text}
      {if $data.sage_paynow_logo=='on'} <img align='{$data.sage_paynow_align}' alt='Pay Now via Sage Pay Now' title='Pay Now' src="{$base_dir}modules/paynow/logo.png">{/if}</a>
       <noscript><input type="image" src="{$base_dir}modules/paynow/logo.png"></noscript>
    </p>
</form>
</div>
<div class="clear"></div>
