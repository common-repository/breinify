<?php


class BreinifyPlugIn {

    public static $CLIENT_SIDE = 'client-side';
    public static $SERVER_SIDE_PREFIX = 'server-side-';
    public static $SERVER_SIDE_AJAX_CURL = 'curl';
    public static $SERVER_SIDE_AJAX_FILE_GET_CONTENTS = 'fileGetContents';
    public static $COOKIE_SESSIONID = 'breinify-sessionId';

    /**
     * The singleton instance of BreinifyPlugIn.
     *
     * @access private
     * @var BreinifyPlugIn
     * @since 1.0.0
     */
    private static $instance;

    /**
     * Retrieves the one true instance of BreinifyPlugIn. If not
     * done already, the instance is initialized.
     *
     * @since  1.0.0
     * @return BreinifyPlugIn Singleton instance of BreinifyPlugIn
     */
    public static function instance() {

        if (!isset(self::$instance)) {
            self::$instance = new BreinifyPlugIn;
        }

        return self::$instance;
    }

    private $initialized = false;
    private $consts = [];

    /**
     * Sets up constants, which are needed within the tool.
     *
     * @since 1.0.0
     * @param $breinifyPhpLocation string the location of the breinify.php
     */
    public function setUp($breinifyPhpLocation) {

        if ($this->initialized) {
            return;
        } else {
            syslog(LOG_DEBUG, 'Setting up the BreinifyPlugiIn...');

            define('BREINIFY_PLUGIN', true);

            // set the core file path
            $this->consts['BREINIFY_PLUGIN_MAINFILE'] = $breinifyPhpLocation;
            $this->consts['BREINIFY_PLUGIN_PATH'] = dirname($breinifyPhpLocation);

            // define the path to the plugin folder
            $this->consts['BREINIFY_PLUGIN_DIR'] = basename($this->consts['BREINIFY_PLUGIN_PATH']);

            // define the URL to the plugin folder
            $this->consts['BREINIFY_PLUGIN_URL'] = plugins_url('', $breinifyPhpLocation);

            // load the required fails
            $this->loadRequirements();

            /*
             * TODO: modify reading of configuration
             *
             * We have to read the configuration from a file and the default has to be
             * staging/productive environment. To inject the environment variables we
             * normally use for that, we have to modify the nginx configuration and have
             * to be perl in place. Perl is currently not compilable, we have to wait until
             * we found an answer:
             *   - http://stackoverflow link here
             *
             * After that we might be able ot inject it using:
             *   perl_modules perl;
             *     [...]
             *   perl_set $env 'sub { return $ENV{"env"}; }';
             *   perl_set $instanceID 'sub { return $ENV{"instanceID"}; }';
             */
            $env = (isset($_SERVER['ENV']) ? $_SERVER['ENV'] : 'stage');
            $env = file_exists($this->consts['BREINIFY_PLUGIN_PATH'] . '/config/' . $env . '.conf') ? $env : 'stage';
            $confFile = $env . '.conf';

            $confArray = parse_ini_file($this->consts['BREINIFY_PLUGIN_PATH'] . '/config/' . $confFile, true);
            $confBreinEngine = Utility::getFromArray($confArray, 'breinEngine', null);
            $confBreinifySite = Utility::getFromArray($confArray, 'breinifySite', null);

            // define the URL the plug-in has to talk to
            $this->consts['BREINIFY_ENV'] = $env;
            $this->consts['BREINIFY_REST_BASE_URL'] = Utility::getFromArray($confBreinEngine, 'rest_base_url', 'https://wordpress.breinify.com/wp');
            $this->consts['BREINIFY_API_BASE_URL'] = Utility::getFromArray($confBreinEngine, 'api_base_url', 'https://api.breinify.com');
            $this->consts['BREINIFY_SITE_URL'] = Utility::getFromArray($confBreinifySite, 'site_url', 'https://www.breinify.com');

            // set-up WordPress hooks
            $this->setUpHooks();

            // do some logging
            syslog(LOG_DEBUG, 'Using the following settings...');
            syslog(LOG_DEBUG, ' - BREINIFY_PLUGIN_MAINFILE: ' . $this->consts('BREINIFY_PLUGIN_MAINFILE'));
            syslog(LOG_DEBUG, ' - BREINIFY_PLUGIN_PATH    : ' . $this->consts('BREINIFY_PLUGIN_PATH'));
            syslog(LOG_DEBUG, ' - BREINIFY_PLUGIN_DIR     : ' . $this->consts('BREINIFY_PLUGIN_DIR'));
            syslog(LOG_DEBUG, ' - BREINIFY_PLUGIN_URL     : ' . $this->consts('BREINIFY_PLUGIN_URL'));
            syslog(LOG_DEBUG, ' - BREINIFY_REST_BASE_URL  : ' . $this->consts('BREINIFY_REST_BASE_URL'));
            syslog(LOG_DEBUG, ' - BREINIFY_API_BASE_URL   : ' . $this->consts('BREINIFY_API_BASE_URL'));
            syslog(LOG_DEBUG, ' - BREINIFY_SITE_URL       : ' . $this->consts('BREINIFY_SITE_URL'));
            syslog(LOG_DEBUG, ' - BREINIFY_ENV            : ' . $this->consts('BREINIFY_ENV'));

            $this->initialized = true;
        }
    }

    public function loadTextDomain() {
        syslog(LOG_DEBUG, 'Loading text-domain...');

        load_plugin_textdomain('breinify-text-domain', false, $this->consts('BREINIFY_PLUGIN_DIR') . '/languages/');
    }

    public function req($file) {
        /** @noinspection PhpIncludeInspection */
        require_once($this->resolvePath('includes/' . $file . '.php'));
    }

    /**
     * Resolves the location of a file on the local file system.
     *
     * @param $file string the relative (to the plug-in directory) path to the file
     * @return string the resolved full path
     */
    public function resolvePath($file) {
        return $this->consts('BREINIFY_PLUGIN_PATH') . '/' . $file;
    }

    /**
     * Resolves the location of a file based on the plug-in url.
     *
     * @param $file string the relative (to the plug-in url) path to the file
     * @return string the resolved full url
     */
    public function resolveUrl($file) {
        return $this->consts('BREINIFY_PLUGIN_URL') . '/' . $file;
    }

    public function resolveRestEndPoint($endPoint) {
        return $this->consts('BREINIFY_REST_BASE_URL') . '/' . $endPoint;
    }

    public function resolveApiEndPoint($endPoint) {
        return $this->consts('BREINIFY_API_BASE_URL') . '/' . $endPoint;
    }

    /**
     * @param $name string gets the value of a constant
     * @return mixed the value of the constants or null if not defined
     */
    public function consts($name) {
        return isset($this->consts[$name]) ? $this->consts[$name] : null;
    }

    public function showsAdminPage() {
        return is_admin();
    }

    /**
     * @param WP_User $user the user to be checked, can be null in that case the current user is checked
     * @return bool true if the user is an admin, otherwise false
     */
    public function isAdmin($user = null) {
        $user = empty($user) ? BreinifySettings::instance()->getCurrentUser() : $user;

        if (empty($user)) {
            return false;
        } else {
            return in_array('administrator', $user->roles);
        }
    }

    public function getCommunicationType() {
        return BreinifySettings::instance()->determineCommunicationType();
    }

    public function getAjaxUrl() {
        return admin_url('admin-ajax.php');
    }

    public function getApiUrl() {
        return $this->consts('BREINIFY_API_BASE_URL');
    }

    public function getRestUrl() {
        return $this->consts('BREINIFY_REST_BASE_URL');
    }

    public function isDev() {
        $value = $this->consts('BREINIFY_ENV');
        return empty($value) ? false : $value === 'dev';
    }

    private function loadRequirements() {
        $this->req('libraries/lib-utility');

        $this->req('classes/BreinifyViewManager');
        $this->req('classes/BreinifySettings');
        $this->req('classes/BreinifyActivityTrackersManager');
    }

    private function setUpHooks() {
        add_action('plugins_loaded', [$this, 'loadTextDomain']);
        add_action('init', [BreinifySettings::instance(), 'load']);
        add_action('init', [BreinifyViewManager::instance(), 'load']);
        add_action('init', [BreinifyActivityTrackersManager::instance(), 'load']);
    }
}