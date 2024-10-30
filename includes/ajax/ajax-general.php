<?php

BreinifyPlugIn::instance()->req('classes/GuiException');
BreinifyPlugIn::instance()->req('libraries/lib-ajaxUtility');

class AjaxGeneral {

    /**
     * The singleton instance of AjaxGeneral.
     *
     * @access private
     * @var AjaxSetup
     * @since 1.0.0
     */
    private static $instance;

    /**
     * Retrieves the one true instance of AjaxGeneral. If not
     * done already, the instance is initialized.
     *
     * @since  1.0.0
     * @return object Singleton instance of AjaxGeneral
     */
    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new AjaxGeneral;
        }

        return self::$instance;
    }

    public $publicMethods = ['doResultFilter'];

    public function doResultFilter() {
        syslog(LOG_DEBUG, 'Executing do result filter with data: ' . json_encode($_POST['data']) . '...');

        return AjaxUtility::handleResult($_POST['data']);
    }
}