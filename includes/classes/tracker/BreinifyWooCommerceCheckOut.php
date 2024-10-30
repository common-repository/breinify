<?php

BreinifyPlugIn::instance()->req('/libraries/lib-utility');
BreinifyPlugIn::instance()->req('/classes/tracker/BreinifyBaseTracker');
BreinifyPlugIn::instance()->req('/classes/tracker/WooCommerceConstants');

class BreinifyWooCommerceCheckOut extends BreinifyBaseTracker {

    protected function _actions() {
        return WooCommerceConstants::$CHECK_OUT_HOOK;
    }

    public function func($action) {
        return 'checkOut';
    }

    public function doTracking($action) {
        return Utility::isActivePlugin(WooCommerceConstants::$PLUGIN_CHECK);
    }

    public function checkOut() {

        /** @noinspection PhpUndefinedFunctionInspection */
        $wooCommerce = WC();

        var_dump($GLOBALS['woocommerce']->cart->get_cart_total());
        var_dump($wooCommerce->cart->get_cart_total());

        $activity = new BreinifyActivity();
        $activity->applySettings($this->settings);
        $activity->addActivity('checkOut');

        $this->sendActivity($activity->data());
    }

    public function group(
        /** @noinspection PhpUnusedParameterInspection */
        $action) {
        return __('WooCommerce', 'breinify-text-domain');
    }

    public function label($action) {

        if ($action == 'woocommerce_payment_successful_result') {
            return __('Checkout (payment needed)', 'breinify-text-domain');
        } else if ($action == 'woocommerce_checkout_no_payment_needed_redirect') {
            return __('Checkout (payment not needed)', 'breinify-text-domain');
        } else {
            return __('Checkout', 'breinify-text-domain');
        }
    }
}