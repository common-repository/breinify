<?php

if (!defined('BREINIFY_PLUGIN')) {
    die('Please initialize the BreinifyPlugIn prior to the usage of this manager.');
}

class BreinifyViewManager {

    /**
     * The singleton instance of BreinifyViewManager.
     *
     * @access private
     * @var BreinifyViewManager
     * @since 1.0.0
     */
    private static $instance;

    private static $UNDEFINED_PAGE = 'undefined';
    private static $PREFIX_PAGE = 'breinify-adminConsole-';

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
     * @var array list of arrays which are combined into one scripts file
     */
    private $combinedScripts = [];

    /**
     * Retrieves the one true instance of BreinifyViewManager. If not
     * done already, the instance is initialized.
     *
     * @since  1.0.0
     * @return object Singleton instance of BreinifyViewManager
     */
    public static function instance() {

        if (!isset(self::$instance)) {
            self::$instance = new BreinifyViewManager;

            // set the plugIn for the instance
            self::$instance->plugIn = BreinifyPlugIn::instance();
            self::$instance->settings = BreinifySettings::instance();
        }

        return self::$instance;
    }

    private $initialized = false;

    /**
     * Sets up constants, which are needed within the tool.
     *
     * @since 1.0.0
     */
    public function load() {

        if ($this->initialized) {
            return;
        } else {
            syslog(LOG_DEBUG, 'Setting up the BreinifyViewManager...');

            /*
             * These hooks have to be registered here, other hooks may be
             * registered after init. It's kind of difficult to tell if
             * an event is executed or not. Some admin_ are only executed
             * if someone is an administrator. Others like admin_notices are
             * executed even if you aren't an admin.
             */
            $this->initPage();

            $this->plugIn->req('classes/GuiException');

            if (!$this->plugIn->isDev()) {
                $this->combinedScripts['breinify-plugin-script'] = [
                    'breinify-plugin-ui-utility-script',
                    'breinify-plugin-highcharts-RealtimeActivities',
                    'breinify-plugin-ui-blocker-script',
                    'breinify-plugin-parsley-script',
                    'breinify-plugin-opentip-script',
                    'breinify-plugin-brein-charts-script',
                    'breinify-plugin-brein-common-script',
                    'breinify-plugin-d3-script',
                    'breinify-plugin-highcharts-script'
                ];
            }

            add_action('admin_init', [$this, 'createAdminMenuSeparator']);
            add_action('admin_init', [$this, 'addPageScriptsAndStyles']);
            add_action('admin_init', [$this, 'activateAdminConsoleHooks']);
            add_action('admin_menu', [$this, 'createAdminMenuEntries']);

            $this->initialized = true;
        }
    }

    /**
     * Adds the hooks needed to create the menu items in the admin-console.
     *
     * @since  1.0.0
     */
    public function activateAdminConsoleHooks() {

        /*
         * If we are showing the admin-console, we want to register some hooks.
         * The is_admin() is a boolean function that will return true if the URL
         * being accessed is in the admin section, or false for a front-end page.
         *
         * See: https://codex.wordpress.org/Function_Reference/is_admin
         */
        if ($this->plugIn->isAdmin()) {
            add_action('admin_notices', [$this, 'showAdminNotice']);
            add_action('admin_enqueue_scripts', [$this, 'addStyleSheet']);
        }
    }

    public function showAdminNotice() {
        $message = null;

        if (self::$UNDEFINED_PAGE == $this->getCurrentPage() && $this->plugIn->isAdmin() && !$this->settings->isInitialized()) {
            $message = 'setupBreinify';
        } else if (('main' !== $this->getCurrentPage() || (empty($_GET['tab']) || $_GET['tab'] !== 'error_log')) && $this->settings->hasErrors()) {
            $message = 'errorBreinify';
        } else {

            // there is no message to show
            return;
        }

        $messageFile = $this->plugIn->resolvePath('views/messages/message-' . $message . '.php');

        if (file_exists($messageFile)) {
            /** @noinspection PhpIncludeInspection */
            @include($messageFile);
        }
    }

    public function initPage() {

        /*
         * We can have a $_GET with a page, which tells us that a user just
         * looks at a page. To enable the usage of ajax, we don't have to
         * do anything, because this is done during a post call, i.e.,
         * $_POST must contain the right things. Thus, we check which type
         * of post is required to answer the given query.
         */
        if (!is_admin() || $_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['action'])) {
            return;
        }

        /*
         * Bind the needed ajax handlers when we are dealing with a
         * ajax call.
         */
        $this->plugIn->req('libraries/lib-ajaxUtility');
        if (AjaxUtility::is($_POST['action'], 'AjaxSetup')) {
            $this->plugIn->req('ajax/ajax-setup');
            AjaxUtility::registerPosts(AjaxSetup::instance());
        } else if (AjaxUtility::is($_POST['action'], 'AjaxMain')) {
            $this->plugIn->req('ajax/ajax-main');
            AjaxUtility::registerPosts(AjaxMain::instance());
        } else if (AjaxUtility::is($_POST['action'], 'AjaxBreinEngineApi')) {
            $this->plugIn->req('ajax/ajax-brein-engine-api');
            AjaxUtility::registerPosts(AjaxBreinEngineApi::instance());
        } else if (AjaxUtility::is($_POST['action'], 'AjaxGeneral')) {
            $this->plugIn->req('ajax/ajax-general');
            AjaxUtility::registerPosts(AjaxGeneral::instance());
        }
    }

    public function addPageScriptsAndStyles() {

        if (!is_admin() || empty($_GET['page'])) {
            return;
        } else if ($this->createPageName('setup') === $_GET['page']) {

            /*
             * We hijack the implementation and registered the page invisible from the menu.
             * Thus, it is not shown but it can be called through a button. Now, when such
             * a button is clicked, we have to render the page.
             */
            $this->addStyleSheet();
            wp_enqueue_style('wp-admin');
            wp_enqueue_style('buttons');
            wp_enqueue_style('install');

            // the setup page JavaScript depends on the following libraries
            $dependencies = $this->getDependencies(
                ['jquery',
                    'breinify-plugin-parsley-script',
                    'breinify-plugin-ui-blocker-script',
                    'breinify-plugin-opentip-script',
                    'breinify-plugin-ui-utility-script']);

            $this->addJavaScripts($dependencies);

            $url = $this->plugIn->resolveUrl('views/admin-console/view-setup.js');
            wp_register_script('breinify-plugin-view-setup-script', $url, $dependencies);
            wp_enqueue_script('breinify-plugin-view-setup-script');

            $this->render('setup');
        } else if ($this->createPageName('main') === $_GET['page']) {

            // the main page JavaScript depends on the following libraries
            $dependencies = $this->getDependencies(
                ['jquery',
                    'breinify-plugin-highcharts-script',
                    'breinify-plugin-brein-charts-script',
                    'breinify-plugin-brein-common-script',
                    'breinify-plugin-d3-script',
                    'breinify-plugin-parsley-script',
                    'breinify-plugin-opentip-script',
                    'breinify-plugin-ui-blocker-script',
                    'breinify-plugin-ui-utility-script',
                    'breinify-plugin-highcharts-RealtimeActivities']);

            $this->addJavaScripts($dependencies);

            $url = $this->plugIn->resolveUrl('views/admin-console/view-main.js');
            wp_register_script('breinify-plugin-view-main-script', $url, $dependencies);
            wp_enqueue_script('breinify-plugin-view-main-script');
        }
    }

    private function getDependencies($neededDependencies) {

        $dependencies = [];
        foreach ($neededDependencies as $neededDependency) {
            $addDependency = null;

            // check if the dependency is combined somewhere
            foreach ($this->combinedScripts as $combiningScript => $combinedScripts) {
                if (in_array($neededDependency, $combinedScripts)) {
                    $addDependency = $combiningScript;
                    break;
                }
            }

            // if it is not combined keep it
            if ($addDependency === null) {
                $addDependency = $neededDependency;
            }

            // make sure we don't have duplicates
            if (!in_array($addDependency, $dependencies)) {
                array_push($dependencies, $addDependency);
            }
        }

        return $dependencies;
    }

    public function createAdminMenuSeparator() {

        global $menu;
        if (!is_array($menu)) {
            return;
        } else {
            syslog(LOG_DEBUG, 'Adding separator admin-console...');

            foreach ($menu as $key => $entry) {
                if (count($entry) > 5 && $entry[5] == 'toplevel_page_' . $this->createPageName('main')) {
                    $menu[$key - 1] = [
                        0 => '',
                        1 => 'manage_options',
                        2 => 'separator-breinify',
                        3 => '',
                        4 => 'wp-menu-separator breinify'
                    ];

                    break;
                }
            }
        }
    }

    /**
     * Creates the menu items in the admin-console.
     *
     * @since  1.0.0
     */
    public function createAdminMenuEntries() {
        syslog(LOG_DEBUG, 'Creating menu entries in admin-console...');

        /*
         * We inject the page only if it was asked for, so that the permission
         * management of WordPress can take place. The showAdminSetupPage will do
         * the rest after the loop of WordPress did it job.
         */
        if (!empty($_GET['page']) && $this->createPageName('setup') === $_GET['page']) {
            add_dashboard_page('', '', 'manage_options', $this->createPageName('setup'), '');
        }

        /*
         * create the top-menu entry: Breinify
         */
        add_object_page(
        // page title
            __('Breinify', 'breinify-text-domain'),
            // menu title
            __('Breinify', 'breinify-text-domain'),
            // capability required
            'manage_options',
            // menu slug
            $this->createPageName('main'),
            // function
            [$this, 'render'],
            // icon
            'dashicons-breinify'
        );
    }

    public function render($page = '') {
        $page = empty($page) || $page === '' ? $this->getCurrentPage() : $page;
        $pageFile = $this->plugIn->resolvePath('views/admin-console/view-' . $page . '.php');

        if (file_exists($pageFile)) {
            try {
                syslog(LOG_DEBUG, 'Rendering page "' . $page . '" using "' . $pageFile . '" to console...');

                /** @noinspection PhpIncludeInspection */
                include($pageFile);
            } catch (Exception $e) {
                syslog(LOG_ERR, 'Selected page "' . $page . '" could not be rendered, error: "' . $e->getMessage() . '"...');


                /** @noinspection PhpIncludeInspection */
                @include($this->plugIn->resolvePath('views/errors/view-generalError.php'));
            }
        } else {
            syslog(LOG_ERR, 'Selected page "' . $page . '" could not be rendered, file "' . $pageFile . '" not found...');

            /** @noinspection PhpIncludeInspection */
            @include($this->plugIn->resolvePath('views/errors/view-pageNotFound.php'));
        }
    }

    public function addStyleSheet() {
        $url = $this->plugIn->resolveUrl('css/breinify.css');
        syslog(LOG_DEBUG, 'Adding breinify style sheet from "' . $url . '"...');

        wp_register_style('breinify-plugin-css-style', $url);
        wp_enqueue_style('breinify-plugin-css-style');
    }

    public function addJavaScripts($dependencies) {

        foreach ($dependencies as $dependency) {
            switch ($dependency) {
                case 'jquery':
                    wp_enqueue_script('jquery');
                    break;
                case 'breinify-plugin-script':
                    $url = $this->plugIn->resolveUrl('js/dist/breinify-wordpress-plugin.min.js');
                    syslog(LOG_DEBUG, 'Adding Breinify PlugIn JavaScript from "' . $url . '"...');
                    wp_register_script('breinify-plugin-script', $url, ['jquery']);
                    wp_enqueue_script('breinify-plugin-script');
                    break;
                case 'breinify-plugin-ui-utility-script':
                    $url = $this->plugIn->resolveUrl('js/libraries/lib-uiUtility.js');
                    syslog(LOG_DEBUG, 'Adding breinify JavaScript from "' . $url . '"...');
                    wp_register_script('breinify-plugin-ui-utility-script', $url, ['jquery']);
                    wp_enqueue_script('breinify-plugin-ui-utility-script');
                    break;
                case 'breinify-plugin-highcharts-RealtimeActivities':
                    $url = $this->plugIn->resolveUrl('js/libraries/lib-highcharts-RealtimeActivities.js');
                    syslog(LOG_DEBUG, 'Adding breinify JavaScript from "' . $url . '"...');
                    wp_register_script('breinify-plugin-highcharts-RealtimeActivities', $url, ['jquery', 'breinify-plugin-ui-utility-script']);
                    wp_enqueue_script('breinify-plugin-highcharts-RealtimeActivities');
                    break;
                case 'breinify-plugin-ui-blocker-script':
                    $url = $this->plugIn->resolveUrl('js/externals/blockui-jquery.js');
                    syslog(LOG_DEBUG, 'Adding uiBlocker JavaScript from "' . $url . '"...');
                    wp_register_script('breinify-plugin-ui-blocker-script', $url, ['jquery']);
                    wp_enqueue_script('breinify-plugin-ui-blocker-script');
                    break;
                case 'breinify-plugin-parsley-script':
                    $url = $this->plugIn->resolveUrl('js/externals/parsley.js');
                    syslog(LOG_DEBUG, 'Adding parsley JavaScript from "' . $url . '"...');
                    wp_register_script('breinify-plugin-parsley-script', $url, ['jquery']);
                    wp_enqueue_script('breinify-plugin-parsley-script');
                    break;
                case 'breinify-plugin-opentip-script':
                    $url = $this->plugIn->resolveUrl('js/externals/opentip-jquery.js');
                    syslog(LOG_DEBUG, 'Adding opentip JavaScript from "' . $url . '"...');
                    wp_register_script('breinify-plugin-opentip-script', $url, ['jquery']);
                    wp_enqueue_script('breinify-plugin-opentip-script');
                    break;
                case 'breinify-plugin-highcharts-script':
                    $url = $this->plugIn->resolveUrl('js/externals/highcharts.js');
                    syslog(LOG_DEBUG, 'Adding highcharts JavaScript from "' . $url . '"...');
                    wp_register_script('breinify-plugin-highcharts-script', $url);
                    wp_enqueue_script('breinify-plugin-highcharts-script');
                    break;
                case 'breinify-plugin-brein-charts-script':
                    $url = $this->plugIn->resolveUrl('js/externals/brein-util-charts.js');
                    syslog(LOG_DEBUG, 'Adding brein-charts JavaScript from "' . $url . '"...');
                    wp_register_script('breinify-plugin-brein-charts-script', $url, ['jquery', 'breinify-plugin-highcharts-script', 'breinify-plugin-d3-script', 'breinify-plugin-brein-common-script']);
                    wp_enqueue_script('breinify-plugin-brein-charts-script');
                    break;
                case 'breinify-plugin-brein-common-script':
                    $url = $this->plugIn->resolveUrl('js/externals/brein-util-common.js');
                    syslog(LOG_DEBUG, 'Adding brein-common JavaScript from "' . $url . '"...');
                    wp_register_script('breinify-plugin-brein-common-script', $url);
                    wp_enqueue_script('breinify-plugin-brein-common-script');
                    break;
                case 'breinify-plugin-d3-script':
                    $url = $this->plugIn->resolveUrl('js/externals/d3.js');
                    syslog(LOG_DEBUG, 'Adding d3 JavaScript from "' . $url . '"...');
                    wp_register_script('breinify-plugin-d3-script', $url);
                    wp_enqueue_script('breinify-plugin-d3-script');
                    break;
                default:
                    throw new GuiException(GuiException::$GENERAL_DEPENDENCY, $dependency);
            }
        }
    }

    public function getCurrentPage() {
        $page = self::$UNDEFINED_PAGE;

        if ($this->plugIn->isAdmin()) {
            $id = get_current_screen()->id;

            if (strpos($id, self::$PREFIX_PAGE) !== false) {
                $page = substr($id, strrpos($id, '-') + 1);
            }
        }

        return $page;
    }

    public function createPageName($page) {
        return self::$PREFIX_PAGE . $page;
    }
}