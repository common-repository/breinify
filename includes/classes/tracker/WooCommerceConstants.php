<?php

class WooCommerceConstants {
    public static $PLUGIN_CHECK = 'woocommerce/woocommerce.php';
    public static $ADD_TO_CART_HOOK = 'woocommerce_add_to_cart';
    public static $CHECK_OUT_HOOK = ['woocommerce_payment_successful_result',
        'woocommerce_checkout_no_payment_needed_redirect'];
    public static $RESTORE_CART_ITEM_HOOK = 'woocommerce_cart_item_restored';
    public static $REMOVE_FROM_CART_HOOK = 'woocommerce_remove_cart_item';
    public static $SELECT_PRODUCT_HOOK = 'woocommerce_before_single_product';
}