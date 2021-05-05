<div class="mini-cart-container">
    <mini-cart
        is-tax-inclusive="{{ (bool) core()->getConfigData('catalog.products.attribute.price_attribute_tax_inclusive') }}"
        view-cart="{{ route('shop.checkout.cart.index') }}"
        cart-text="{{ __('shop::app.minicart.view-cart') }}"
        checkout-text="{{ __('shop::app.minicart.checkout') }}"
        checkout-url="{{ route('shop.checkout.onepage.index') }}"
        subtotal-text="{{ __('shop::app.checkout.cart.cart-subtotal') }}"
        check-minimum-order-url="{{ route('shop.checkout.check-minimum-order') }}">
    </mini-cart>
</div>