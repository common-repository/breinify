<?php

/**
 * Include all the once we need.
 */
BreinifyPlugIn::instance()->req('/classes/tracker/BreinifyLoginTracker');
BreinifyPlugIn::instance()->req('/classes/tracker/BreinifyLogoutTracker');
BreinifyPlugIn::instance()->req('/classes/tracker/BreinifySearchTracker');
BreinifyPlugIn::instance()->req('/classes/tracker/BreinifyWooCommerceAddToCart');
BreinifyPlugIn::instance()->req('/classes/tracker/BreinifyWooCommerceRemoveFromCart');
BreinifyPlugIn::instance()->req('/classes/tracker/BreinifyWooCommerceProductSelection');
BreinifyPlugIn::instance()->req('/classes/tracker/BreinifyWooCommerceCheckOut');

abstract class BreinifyBaseTracker {

    /**
     * Gets all the trackers available.
     *
     * @param $trackerManager BreinifyActivityTrackersManager the manager to be used, can be null if no sending of activities is needed
     * @return array[BreinifyBaseTracker] the available instances
     */
    public static function getAll($trackerManager) {
        $instances = [];

        foreach (get_declared_classes() as $class) {
            if (is_subclass_of($class, 'BreinifyBaseTracker')) {

                /* @var BreinifyBaseTracker $instance */
                $instance = new $class;
                $instance->init($trackerManager);

                array_push($instances, $instance);
            }
        }

        return $instances;
    }

    /**
     * The singleton instance of BreinifyPlugIn.
     *
     * @access private
     * @var BreinifyPlugIn
     * @since 1.0.0
     */
    protected $plugIn;
    /**
     * The singleton instance of BreinifySettings.
     *
     * @access private
     * @var BreinifySettings
     * @since 1.0.0
     */
    protected $settings;
    /**
     * The singleton instance of BreinifyActivityTrackersManager.
     *
     * @access private
     * @var BreinifyActivityTrackersManager
     * @since 1.0.0
     */
    protected $trackerManager;

    public function init($trackerManager) {
        $this->plugIn = BreinifyPlugIn::instance();
        $this->settings = BreinifySettings::instance();
        $this->trackerManager = $trackerManager;
    }

    public function sendActivity($data) {

        /** @noinspection PhpUndefinedMethodInspection */
        $this->trackerManager->sendActivity($data);
    }

    /**
     * @param $action string the action to determine if tracking is possible
     * @return bool true if the
     * action should be tracked, otherwise false
     */
    public function doTracking(
        /** @noinspection PhpUnusedParameterInspection */
        $action) {
        return true;
    }

    /**
     * @param $action string the action to retrieve the priority for
     * @return int the priority of the specified action
     */
    public function priority(
        /** @noinspection PhpUnusedParameterInspection */
        $action) {
        return 1;
    }

    public function actions() {
        $actions = $this->_actions();

        if (!is_array($actions)) {
            return [$actions];
        } else {
            return $actions;
        }
    }

    public function group(
        /** @noinspection PhpUnusedParameterInspection */
        $action) {
        return __('General', 'breinify-text-domain');
    }

    public function amountOfArgs(
        /** @noinspection PhpUnusedParameterInspection */
        $action) {
        return 1;
    }

    public abstract function label($action);

    /**
     * @return mixed the actions to assign to, can be an array or a single string
     */
    abstract protected function _actions();

    /**
     * @param $action string the action to retrieve the function for
     * @return string the function to be called
     */
    abstract public function func($action);
}