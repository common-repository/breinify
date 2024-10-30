<?php

BreinifyPlugIn::instance()->req('/libraries/lib-utility');
BreinifyPlugIn::instance()->req('/classes/tracker/BreinifyBaseTracker');
BreinifyPlugIn::instance()->req('/classes/tracker/WooCommerceConstants');

class BreinifyWooCommerceProductSelection extends BreinifyBaseTracker {

    protected function _actions() {
        return WooCommerceConstants::$SELECT_PRODUCT_HOOK;
    }

    public function func($action) {
        return 'selectProduct';
    }

    public function doTracking($action) {
        return Utility::isActivePlugin(WooCommerceConstants::$PLUGIN_CHECK);
    }

    public function selectProduct() {
        $product = $GLOBALS['product'];

        $activity = new BreinifyActivity();
        $activity->applySettings($this->settings);
        $activity->addActivity('selectProduct', $product->post_title);

        $this->sendActivity($activity->data());
    }

    public function group(
        /** @noinspection PhpUnusedParameterInspection */
        $action) {
        return __('WooCommerce', 'breinify-text-domain');
    }

    public function label($action) {
        return __('Product Selection', 'breinify-text-domain');
    }
}