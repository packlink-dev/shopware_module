{namespace name=frontend/packlink/dropoff}

{extends file="parent:frontend/checkout/cart_footer.tpl"}

{block name='frontend_checkout_cart_footer_field_labels_shipping'}
    {$smarty.block.parent}

    {if isset($sBasket.sCODSurcharge) && $sBasket.sCODSurcharge > 0}
        <li class="list--entry block-group entry--surcharge">
            <div class="entry--label block">
                Cash on Delivery Fee:
            </div>
            <div class="entry--value block">
                {$sBasket.sCODSurcharge|currency}
            </div>
        </li>
    {/if}
{/block}