<?php

BreinifyPlugIn::instance()->req('classes/BreinifySettings');
BreinifyPlugIn::instance()->req('classes/GuiException');
BreinifyPlugIn::instance()->req('libraries/lib-ajaxUtility');
BreinifyPlugIn::instance()->req('libraries/lib-uiUtility');

class AjaxSetup {

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
            self::$instance = new AjaxSetup;
        }

        return self::$instance;
    }

    public function doGetConfiguration() {
        $result = BreinifySettings::instance()->get(['apiKey', 'firstName', 'lastName', 'email']);
        syslog(LOG_DEBUG, 'Sending result of save configuration: ' . json_encode($result) . '...');

        return $result;
    }

    public function doSaveConfiguration() {
        if (!BreinifySettings::instance()->isInitialized()) {

            // first try to create the database if it fails
            BreinifySettings::instance()->createDbStructure();

            // make sure the configuration stores the value as initialized
            $configuration = $_POST['data'];
            $configuration['initialized'] = true;

            // set the values and get the once of interest
            BreinifySettings::instance()->setAndStore($configuration);
        }

        return $this->doGetConfiguration();
    }

    public function doConfirmCheck_embRest_checkhandler() {
        if (BreinifySettings::instance()->isInitialized()) {
            throw new GuiException(GuiException::$SETUP_ALREADY_SETUP);
        }

        $data = $_POST['data'];

        return AjaxUtility::rest('checkhandler', $data);
    }

    public function doOneClickSetup_embRest_signup() {
        if (BreinifySettings::instance()->isInitialized()) {
            throw new GuiException(GuiException::$SETUP_ALREADY_SETUP);
        }

        $data = $_POST['data'];

        return AjaxUtility::rest('signup', $data);
    }

    public function doSignUp_embRest_signup() {
        if (BreinifySettings::instance()->isInitialized()) {
            throw new GuiException(GuiException::$SETUP_ALREADY_SETUP);
        }

        $data = $_POST['data'];

        return AjaxUtility::rest('signup', $data);
    }

    public function doLogIn_embRest_login() {
        if (BreinifySettings::instance()->isInitialized()) {
            throw new GuiException(GuiException::$SETUP_ALREADY_SETUP);
        }

        return AjaxUtility::rest('login', $_POST['data']);
    }
}