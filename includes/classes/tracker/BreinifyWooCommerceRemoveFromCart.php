<?php

BreinifyPlugIn::instance()->req('/libraries/lib-utility');
BreinifyPlugIn::instance()->req('/classes/tracker/BreinifyBaseTracker');
BreinifyPlugIn::instance()->req('/classes/tracker/WooCommerceConstants');

class BreinifyWooCommerceRemoveFromCart extends BreinifyBaseTracker {

    protected function _actions() {
        return WooCommerceConstants::$REMOVE_FROM_CART_HOOK;
    }

    public function func($action) {
        return 'removeFromCart';
    }

    public function doTracking($action) {
        return Utility::isActivePlugin(WooCommerceConstants::$PLUGIN_CHECK);
    }

    public function removeFromCart($cartItemKey) {

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
        $activity->addActivity('removeFromCart', $productName);

        $this->sendActivity($activity->data());
    }

    public function group(
        /** @noinspection PhpUnusedParameterInspection */
        $action) {
        return __('WooCommerce', 'breinify-text-domain');
    }

    public function label($action) {
        return __('Remove From Cart', 'breinify-text-domain');
    }
}