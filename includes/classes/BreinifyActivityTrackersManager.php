<?php

class BreinifyActivityTrackersManager {

    /**
     * The singleton instance of BreinifyActivityTrackersManager.
     *
     * @access private
     * @var BreinifyActivityTrackersManager
     * @since 1.0.0
     */
    private static $instance;

    /**
     * The singleton instance of BreinifyPlugIn.
     *
     * @access private
     * @var BreinifyPlugIn
     * @since 1.0.0
     */
    private $plugIn;
    /**
     * The singleton instance of BreinifySettings.
     *
     * @access private
     * @var BreinifySettings
     * @since 1.0.0
     */
    private $settings;

    /**
     * Flag to measure if the activities where submitted or not
     */
    private $submittedActivities = false;

    /**
     * Retrieves the one true instance of BreinifyActivityTrackersManager. If not
     * done already, the instance is initialized.
     *
     * @since  1.0.0
     * @return object Singleton instance of BreinifyActivityTrackersManager
     */
    public static function instance() {

        if (!isset(self::$instance)) {
            self::$instance = new BreinifyActivityTrackersManager;

            // set the plugIn for the instance
            self::$instance->plugIn = BreinifyPlugIn::instance();
            self::$instance->settings = BreinifySettings::instance();
        }

        return self::$instance;
    }

    public function load() {
        $this->plugIn->req('classes/BreinifyActivity');
        $this->plugIn->req('classes/BreinifyCookieManager');
        $this->plugIn->req('classes/tracker/BreinifyBaseTracker');
        $this->plugIn->req('classes/GuiException');
        $this->plugIn->req('libraries/lib-ajaxUtility');

        if ($this->plugIn->isAdmin()) {
            syslog(LOG_DEBUG, 'The BreinifyActivityTrackersManager is deactivated, because an administrator is logged in.');
        } else {
            syslog(LOG_DEBUG, 'The BreinifyActivityTrackersManager is loading...');

            /*
             * Do not inject any script other then GET
             */
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {

                // add the needed scripts
                add_action('login_enqueue_scripts', [$this, 'addJavaScripts']);
                add_action('wp_enqueue_scripts', [$this, 'addJavaScripts']);
                add_action('admin_enqueue_scripts', [$this, 'addJavaScripts']);
            }

            /* @var BreinifyBaseTracker $tracker */
            foreach (BreinifyBaseTracker::getAll($this) as $tracker) {
                foreach ($tracker->actions() as $action) {
                    if ($this->settings->isActiveActivity($action) && $tracker->doTracking($action)) {
                        add_action($action, [$tracker, $tracker->func($action)], $tracker->priority($action), $tracker->amountOfArgs($action));
                    }
                }
            }

            // safer if something was not send, it is saved here
            add_action('wp_footer', [$this, 'submitActivities']);
            add_action('login_footer', [$this, 'submitActivities']);
            add_action('admin_footer', [$this, 'submitActivities']);
            add_action('shutdown', [$this, 'submitActivities']);
        }
    }

    public function addJavaScripts() {

        if ($this->plugIn->isDev()) {
            $url = $this->plugIn->resolveUrl('js/libraries/lib-uiUtility.js');
            syslog(LOG_DEBUG, 'Adding breinify JavaScript from "' . $url . '"...');
            wp_register_script('breinify-plugin-ui-utility-script', $url, ['jquery']);
            wp_enqueue_script('breinify-plugin-ui-utility-script');
        } else {
            $url = $this->plugIn->resolveUrl('js/dist/breinify-wordpress-plugin.min.js');
            syslog(LOG_DEBUG, 'Adding breinify JavaScript from "' . $url . '"...');
            wp_register_script('breinify-plugin-script', $url, ['jquery']);
            wp_enqueue_script('breinify-plugin-script');
        }
    }

    public function submitActivities() {
        if ($this->submittedActivities === true) {
            syslog(LOG_DEBUG, 'Activities are already in submitting mode (' . ($this->submittedActivities === true ? 'true' : 'false') . ')...');

            return;
        } else {
            $this->submittedActivities = true;
        }

        // check if we have a redirection defined
        $headers = headers_list();
        $isHtmlPage = false;
        $isRedirecting = false;
        foreach ($headers as $header) {
            $lowerHeader = strtolower($header);
            if (substr($lowerHeader, 0, 13) == "content-type:") {
                /** @noinspection PhpUnusedLocalVariableInspection */
                list($contentType, $charset) = explode(";", trim(substr($header, 14), 2));
                $isHtmlPage = (trim($contentType) == "text/html");
            } else if (substr($lowerHeader, 0, 9) == "location:") {
                $isRedirecting = true;
            }
        }

        // inject the script
        if (!$isRedirecting && $isHtmlPage) {
            syslog(LOG_DEBUG, 'Injecting script for handling of buffered activities...');
            echo '<script src="' . BreinifyPlugIn::instance()->resolveUrl('js/libraries/lib-injector.js.php') . '"></script>';
            echo '<script src="' . BreinifyPlugIn::instance()->resolveUrl('js/libraries/lib-injector.js.php') . '"></script>';
        }
    }

    /**
     * @param $data array the data of the activity to be send
     * @return bool if the activity was send successfully
     */
    public function sendActivity($data) {
        $activity = new BreinifyActivity();
        $activity->setSecret($this->settings->getSecret());

        if (empty($data['user'])) {
            /*
             * Ignore there is no user information, the user is not logged in
             * and no session is available.
             */
        } else if (!$activity->setData($data)) {
            syslog(LOG_ERR, 'Received invalid data "' . json_encode($data) . '"...');
            $this->settings->writeErrorLog(GuiException::$API_INVALID_DATA, json_encode($data));
        } else if (!$activity->isValid()) {
            syslog(LOG_ERR, 'The received activity is not valid "' . $activity->json() . '"...');
            $this->settings->writeErrorLog(GuiException::$API_INVALID_ACTIVITY, $activity->json());
        } else if ($this->settings->isServerCommunicationType()) {
            $result = AjaxUtility::saveApi('activity', $activity->data());

            if (empty($result) && !is_array($result)) {
                syslog(LOG_ERR, 'Could not send activity "' . $activity->json() . '"...');
            } else {
                return $result;
            }
        } else {
            syslog(LOG_DEBUG, 'Sending activity to client side with cookie: "' . $activity->json() . '"...');
            BreinifyCookieManager::setCookie(BreinifyCookieManager::$ACTIVITY_COOKIE, $activity->data());
        }

        return [];
    }
}