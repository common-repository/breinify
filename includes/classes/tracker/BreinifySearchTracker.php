<?php

BreinifyPlugIn::instance()->req('/classes/tracker/BreinifyBaseTracker');

class BreinifySearchTracker extends BreinifyBaseTracker {

    protected function _actions() {
        return 'pre_get_posts';
    }

    public function func($action) {
        return 'search';
    }

    /**
     * @param $query WP_Query
     */
    public function search($query) {
        if ($query->is_search() &&
            /*
             * WooCommerce triggers the search twice so we have to make sure that doesn't happen:
             *  - ?s=[...]&wc-ajax=get_refreshed_fragments (POST)
             */
            $_SERVER['REQUEST_METHOD'] === 'GET'
        ) {
            $activity = new BreinifyActivity();
            $activity->applySettings($this->settings);
            $activity->addActivity('search', get_search_query(false));

            $this->sendActivity($activity->data());
        }
    }

    public function label($action) {
        return __('Search', 'breinify-text-domain');
    }
}