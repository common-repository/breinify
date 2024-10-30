<?php

BreinifyPlugIn::instance()->req('classes/BreinifySettings');
BreinifyPlugIn::instance()->req('classes/GuiException');

class AjaxMain {

    /**
     * The singleton instance of AjaxSetup.
     *
     * @access private
     * @var AjaxSetup
     * @since 1.0.0
     */
    private static $instance;

    /**
     * Retrieves the one true instance of AjaxSetup. If not
     * done already, the instance is initialized.
     *
     * @since  1.0.0
     * @return object Singleton instance of AjaxSetup
     */
    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new AjaxMain;
        }

        return self::$instance;
    }

    public function doUnbind() {
        syslog(LOG_DEBUG, 'Resetting the settings...');
        setcookie(BreinifyPlugIn::$COOKIE_SESSIONID, '', time() - 60 * 60);
        BreinifySettings::instance()->reset();

        return [];
    }

    public function doDeleteErrors() {
        BreinifySettings::instance()->truncateErrorLog();
    }

    public function doSaveAdvancedSettings() {
        BreinifySettings::instance()->setAndStore($_POST['data']);

        return [];
    }

    public function doSaveActivityTrackerSettings() {
        BreinifySettings::instance()->setActiveActivities($_POST['data']);
        BreinifySettings::instance()->store();

        return [];
    }

    public function doUnsetSessionId($sessionId = null) {
        setcookie(BreinifyPlugIn::$COOKIE_SESSIONID, $sessionId, time() - 60 * 60, null, null, false, true);
    }

    public function doSetSessionId($sessionId = null) {
        $data = $_POST['data'];
        $sessionId = empty($sessionId) ? (empty($data['sessionId']) ? null : $data['sessionId']) : $sessionId;

        if (empty($sessionId)) {
            throw new GuiException(GuiException::$GENERAL_INVALID_SESSIONID);
        } else {
            setcookie(BreinifyPlugIn::$COOKIE_SESSIONID, $sessionId, time() + 60 * 60, null, null, false, true);
        }

        return [];
    }

    public function doLogIn_embRest_login() {

        // this is only fired if we go through the server
        $result = AjaxUtility::rest('login', $_POST['data']);

        // mark that the server is informed
        $this->doSetSessionId(empty($result['sessionId']) ? null : $result['sessionId']);
        $result['server'] = true;

        return $result;
    }
}