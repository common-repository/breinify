<?php

BreinifyPlugIn::instance()->req('classes/BreinifySettings');
BreinifyPlugIn::instance()->req('classes/BreinifyActivityTrackersManager');

class AjaxBreinEngineApi {

    /**
     * The singleton instance of AjaxActivityTracker.
     *
     * @access private
     * @var AjaxBreinEngineApi
     * @since 1.0.0
     */
    private static $instance;

    /**
     * Retrieves the one true instance of AjaxBreinEngineApi. If not
     * done already, the instance is initialized.
     *
     * @since  1.0.0
     * @return object Singleton instance of AjaxBreinEngineApi
     */
    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new AjaxBreinEngineApi;
        }

        return self::$instance;
    }

    public $publicMethods = ['doActivityTracking_embrest_activity'];

    public function doActivityTracking_embrest_activity() {
        /*
         * This method should never be called on server side, because client mode is enabled.
         * It may be executed during a switch, so let's send the data anyways.
         */
        syslog(LOG_ERR, 'Trying to execute activity tracking from client to server...');

        return BreinifyActivityTrackersManager::instance()->sendActivity($_POST['data']);
    }

    public function doCurrentActivities_embRest_currentactivities() {
        return AjaxUtility::rest('currentactivities', $_POST['data']);
    }

    public function doCurrentCollectives_embRest_currentcollectives() {
        return AjaxUtility::rest('currentcollectives', $_POST['data']);
    }
}