<?php

BreinifyPlugIn::instance()->req('/libraries/lib-utility');
BreinifyPlugIn::instance()->req('/classes/tracker/BreinifyBaseTracker');
BreinifyPlugIn::instance()->req('/classes/tracker/WooCommerceConstants');

class BreinifyWooCommerceAddToCart extends BreinifyBaseTracker {

    protected function _actions() {
        return [WooCommerceConstants::$ADD_TO_CART_HOOK, WooCommerceConstants::$RESTORE_CART_ITEM_HOOK];
    }

    public function func($action) {
        return 'addToCart';
    }

    public function doTracking($action) {
        return Utility::isActivePlugin(WooCommerceConstants::$PLUGIN_CHECK);
    }

    public function addToCart($cartItemKey) {

        /** @noinspection PhpUndefinedFunctionInspection */
        $wooCommerce = WC();
        $productName = null;

        // get some further information about the product added
        if (is_object($wooCommerce->cart)) {
            $items = $wooCommerce->cart->get_cart();
            $product = $items[$cartItemKey]['data'];

            if (is_object($product) && is_object($product->post)) {
                $productName = $product->post->post_title;
            }
        }

        $activity = new BreinifyActivity();
        $activity->applySettings($this->settings);
        $activity->addActivity('addToCart', $productName);

        $this->sendActivity($activity->data());
    }

    public function group(
        /** @noinspection PhpUnusedParameterInspection */
        $action) {
        return __('WooCommerce', 'breinify-text-domain');
    }

    public function label($action) {

        if ($action === WooCommerceConstants::$ADD_TO_CART_HOOK) {
            return __('Add to Cart', 'breinify-text-domain');
        } else {
            return __('Add to Cart (Undo)', 'breinify-text-domain');
        }
    }
}