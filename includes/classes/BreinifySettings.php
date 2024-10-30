<?php


class BreinifySettings {

    /**
     * The singleton instance of BreinifySettings.
     *
     * @access private
     * @var BreinifySettings
     * @since 1.0.0
     */
    private static $instance;
    private static $dbValuesTable = 'breinify_cookie_store';
    private static $dbErrorTable = 'breinify_error_store';
    private static $dbVersion = 'breinify_db_version';

    /**
     * The singleton instance of BreinifyPlugIn.
     *
     * @access private
     * @var BreinifyPlugIn
     * @since 1.0.0
     */
    private $plugIn;

    /**
     * Retrieves the one true instance of BreinifySettings. If not
     * done already, the instance is initialized.
     *
     * @since  1.0.0
     * @return BreinifySettings Singleton instance of BreinifySettings
     */
    public static function instance() {

        if (!isset(self::$instance)) {
            self::$instance = new BreinifySettings;

            self::$instance->plugIn = BreinifyPlugIn::instance();
        }

        return self::$instance;
    }

    /**
     * @var WP_User
     */
    private $currentUser = null;

    private $propertyPrefix = 'property_';
    private $jsonPrefix = 'json';
    private $prefix = 'breinify_';

    private $property_initialized = null;
    private $property_communicationType = null;
    private $property_category = null;
    private $property_apiKey = null;
    private $property_secret = null;
    private $property_password = null;
    private $property_firstName = null;
    private $property_lastName = null;
    private $property_email = null;
    private $property_jsonActiveActivities = null;

    private $activeActivities = null;

    private $possibleCommunicationTypes = [];
    private $possibleCategories = [];

    public function __construct() {
        $this->possibleCommunicationTypes = [
            null                                                                                      => [
                'title'            => __('automatically select the communication type', 'breinify-text-domain'),
                'description'      => __('The type of communication is picked based on availability.'),
                'signatureSupport' => null
            ],
            BreinifyPlugIn::$SERVER_SIDE_PREFIX . BreinifyPlugIn::$SERVER_SIDE_AJAX_CURL              => [
                'title'            => __('communicate from server-side (using cURL)', 'breinify-text-domain'),
                'description'      => __('The WordPress platform communicates with the Brein Engine using <a href="https://en.wikipedia.org/wiki/CURL" target="_blank">cURL</a>.', 'breinify-text-domain'),
                'signatureSupport' => true
            ],
            BreinifyPlugIn::$SERVER_SIDE_PREFIX . BreinifyPlugIn::$SERVER_SIDE_AJAX_FILE_GET_CONTENTS => [
                'title'            => __('communicate from server-side (using file_get_contents)', 'breinify-text-domain'),
                'description'      => __('The WordPress platform communicates with the Brein Engine based on the PHP method file_get_contents, see <a href="http://php.net/manual/en/function.file-get-contents.php" target="_blank">PHP: file_get_contents - Manual</a> and additionally <a href="http://php.net/manual/en/function.stream-context-create.php" target="_blank">PHP: stream_context_create - Manual</a>. Please also check the informatin at <a href="http://www.php.net/manual/en/filesystem.configuration.php#ini.allow-url-fopen" target="_blank">allow-url-fopen</a>.', 'breinify-text-domain'),
                'signatureSupport' => true
            ],
            BreinifyPlugIn::$CLIENT_SIDE                                                              => [
                'title'            => __('communicate from client-side', 'breinify-text-domain'),
                'description'      => __('The client\'s browser is utilized to communicate with the Brein Engine.'),
                'signatureSupport' => true
            ]
        ];

        $this->possibleCategories = [
            'apparel'   => [
                'title'       => __('Apparel', 'breinify-text-domain'),
                'description' => __('e.g., clothing, shoes, accessories, dress, jewelry, uniforms, costumes, attire, garb', 'breinify-text-domain')
            ],
            'home'      => [
                'title'       => __('Home', 'breinify-text-domain'),
                'description' => __('e.g., real estate, furniture, rent, leasing, property management, kitchen, decor, bath, bedding', 'breinify-text-domain')
            ],
            'education' => [
                'title'       => __('Education', 'breinify-text-domain'),
                'description' => __('e.g., books, tutoring, financial aid, Edtech, academics, research', 'breinify-text-domain')
            ],
            'family'    => [
                'title'       => __('Family', 'breinify-text-domain'),
                'description' => __('e.g., babysitters, children-related, senior-related, events, sports, toys, games', 'breinify-text-domain')
            ],
            'food'      => [
                'title'       => __('Food', 'breinify-text-domain'),
                'description' => __('e.g., grocery, delivery, pickup, gourmet, beer, wine', 'breinify-text-domain')
            ],
            'health'    => [
                'title'       => __('Health', 'breinify-text-domain'),
                'description' => __('e.g., diet, supplements, fitness, sports & outdoors, personal care', 'breinify-text-domain')
            ],
            'job'       => [
                'title'       => __('Job', 'breinify-text-domain'),
                'description' => __('e.g., recruiting, life coach, training', 'breinify-text-domain')
            ],
            'services'  => [
                'title'       => __('Services', 'breinify-text-domain'),
                'description' => __('e.g., Financial services (CPA), freelancer, design, teaching, delivery', 'breinify-text-domain')
            ],
            'other'     => [
                'title'       => __('Other', 'breinify-text-domain'),
                'description' => __('Pick this category, if no other category applies to your business.', 'breinify-text-domain')
            ]
        ];
    }

    public function load() {
        $this->plugIn->req('libraries/lib-utility');

        /*
         * First load all the properties so that the other functions can utilize the
         * loaded properties to determine correct actions.
         */
        $jsonPrefix = $this->propertyPrefix . $this->jsonPrefix;
        $this->doProps(function ($option, $prop) use ($jsonPrefix) {
            $value = get_option($option, null);

            if (substr($prop, 0, strlen($jsonPrefix)) === $jsonPrefix) {
                $jsonProperty = lcfirst(substr($prop, strlen($jsonPrefix)));
                if (property_exists($this, $jsonProperty)) {
                    $this->$jsonProperty = json_decode($value, true);
                }
            }

            $this->$prop = $value;
        });
        syslog(LOG_DEBUG, 'Loading settings as : ' . json_encode($this->get()) . '...');

        /*
         * Also keep the currentUser available for all systems.
         */
        $this->currentUser = is_user_logged_in() ? wp_get_current_user() : null;
    }

    public function isLoggedIn() {
        return empty($_COOKIE['breinify-sessionId']) ? false : true;
    }

    public function isInitialized() {
        if (isset($this->property_initialized)) {
            return $this->property_initialized === true ||
            $this->property_initialized === 1 ||
            $this->property_initialized === '1';
        } else {
            return false;
        }
    }

    public function createDbStructure() {
        global $breinify_db_version;

        /* @var wpdb $wpdb */
        $wpdb = $GLOBALS['wpdb'];

        /** @noinspection PhpIncludeInspection */
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        /** @noinspection SqlDialectInspection */
        /** @noinspection SqlNoDataSourceInspection */
        $sql = "CREATE TABLE " . $wpdb->prefix . self::$dbValuesTable . " (
            id TINYTEXT NOT NULL,
            cookie TINYTEXT NOT NULL,
            userEmail TINYTEXT NOT NULL,
            json TEXT NOT NULL,
            UNIQUE KEY idx_breinify_id (id(40)),
            KEY idx_breinify_userEmail (userEmail(100)),
            KEY idx_breinify_cookie_userEmail (cookie(100), userEmail(100))
            )" . $wpdb->get_charset_collate() . ";";
        dbDelta($sql);

        /** @noinspection SqlDialectInspection */
        /** @noinspection SqlNoDataSourceInspection */
        $sql = "CREATE TABLE " . $wpdb->prefix . self::$dbErrorTable . " (
            timestamp INT NOT NULL,
            error INT NOT NULL,
            payload TEXT,
            KEY idx_breinify_timestamp (timestamp)
            )" . $wpdb->get_charset_collate() . ";";
        dbDelta($sql);

        add_option(self::$dbVersion, $breinify_db_version);
    }

    /**
     * @param $error int the error number to be logged
     * @param $payload string payload which might be attached
     */
    public function writeErrorLog($error, $payload = null) {

        /* @var wpdb $wpdb */
        $wpdb = $GLOBALS['wpdb'];

        $wpdb->insert($wpdb->prefix . self::$dbErrorTable, [
            'error'     => $error,
            'payload'   => $payload,
            'timestamp' => time()
        ], ['%s', '%s', '%d']);
    }

    public function getErrorLog() {
        /* @var wpdb $wpdb */
        $wpdb = $GLOBALS['wpdb'];

        /** @noinspection SqlNoDataSourceInspection */
        /** @noinspection SqlDialectInspection */
        $results = $wpdb->get_results('SELECT timestamp, error, payload FROM ' . $wpdb->prefix . self::$dbErrorTable . ' ORDER BY timestamp DESC LIMIT 100', "ARRAY_A");

        foreach ($results as $idx => $result) {
            $payload = json_decode($result['payload'], true);

            // format the data correctly
            $result['date'] = Utility::format($result['timestamp']);
            $result['message'] = GuiException::resolve(new GuiException(intval($result['error']), empty($payload['parameters']) ? [] : $payload['parameters']));

            $results[$idx] = $result;
        }

        return $results;
    }

    public function hasErrors() {
        $results = null;

        /* @var wpdb $wpdb */
        $wpdb = $GLOBALS['wpdb'];

        $table = $wpdb->prefix . self::$dbErrorTable;
        if ($wpdb->get_var('SHOW TABLES LIKE "' . $table . '"') == $table) {
            /** @noinspection SqlNoDataSourceInspection */
            /** @noinspection SqlDialectInspection */
            $results = @$wpdb->get_results('SELECT count(1) as cnt FROM ' . $table . ' LIMIT 1', "ARRAY_A");
        }

        return count($results) > 0 && !empty($results[0]['cnt']) && $results[0]['cnt'] > 0;
    }

    public function truncateErrorLog() {
        /* @var wpdb $wpdb */
        $wpdb = $GLOBALS['wpdb'];

        $wpdb->query("TRUNCATE TABLE " . $wpdb->prefix . self::$dbErrorTable);
    }

    public function deleteDbStructure() {
        /* @var wpdb $wpdb */
        $wpdb = $GLOBALS['wpdb'];

        /** @noinspection SqlDialectInspection */
        /** @noinspection SqlNoDataSourceInspection */
        $sql = "DROP TABLE IF EXISTS " . ($wpdb->prefix) . self::$dbValuesTable . ";";
        /** @noinspection PhpUndefinedMethodInspection */
        $wpdb->query($sql);

        /** @noinspection SqlDialectInspection */
        /** @noinspection SqlNoDataSourceInspection */
        $sql = "DROP TABLE IF EXISTS " . ($wpdb->prefix) . self::$dbErrorTable . ";";
        /** @noinspection PhpUndefinedMethodInspection */
        $wpdb->query($sql);

        delete_option(self::$dbVersion);
    }

    /**
     * Writes an activity to the database to be executed later.
     *
     * @param $cookie string the name of the cookie
     * @param $value string the value to be stored
     *
     * @return bool returns true if the value was buffered, otherwise false
     */
    public function bufferValue($cookie, $value) {
        $user = $this->getCurrentUser();
        $userEmail = empty($user) ? null : $user->user_email;

        if (empty($userEmail)) {
            return false;
        }

        /* @var wpdb $wpdb */
        $wpdb = $GLOBALS['wpdb'];

        // update the existing cookie
        $updatedRows = $wpdb->update($wpdb->prefix . self::$dbValuesTable, [
            'json' => $value
        ], ['cookie' => $cookie, 'userEmail' => $userEmail], ['%s']);

        // insert the row if nothing was updated
        if ($updatedRows === 0) {
            $wpdb->insert($wpdb->prefix . self::$dbValuesTable, [
                'id'        => uniqid(crc32($userEmail), true),
                'cookie'    => $cookie,
                'userEmail' => $userEmail,
                'json'      => $value
            ], ['%s', '%s', '%s', '%s']);
        }

        return true;
    }

    public function getBufferedValue($cookie) {
        $entry = $this->readBufferedValues($cookie);

        if (empty($entry)) {
            return null;
        } else {
            return json_decode($entry['json']);
        }
    }

    public function getBufferedValues() {
        return $this->readBufferedValues();
    }

    public function removeBufferedValue($id) {

        /* @var wpdb $wpdb */
        $wpdb = $GLOBALS['wpdb'];

        /** @noinspection PhpUndefinedMethodInspection */
        $wpdb->delete($wpdb->prefix . self::$dbValuesTable, ['id' => $id], ['%s']);
    }

    public function getFullName() {
        return $this->property_firstName . ' ' . $this->property_lastName;
    }

    public function getEmail() {
        return $this->property_email;
    }

    public function getPassword() {
        return $this->property_password;
    }

    public function getApiKey() {
        return $this->property_apiKey;
    }

    public function getSecret() {
        return $this->property_secret;
    }

    public function getHiddenSecret() {
        $secret = $this->getSecret();

        return $secret == null ? null : str_repeat('X', strlen($secret));
    }

    public function getCommunicationType() {
        return $this->property_communicationType;
    }

    public function getCategory() {
        return (empty($this->property_category) ? key(array_slice($this->possibleCategories, -1, 1, true)) : $this->property_category);
    }

    public function determineCommunicationType() {

        /*
         * We currently do not support:
         *   - http_post_data (function)
         *   - HTTPRequest (class)
         * In the future it might be necessary to add these supports for other systems.
         * So far we need some sample systems having this shit up and running :).
         *
         * TODO: add additional features in the future
         */
        $communicationType = $this->getCommunicationType();

        if (!$this->validateCommunicationType($communicationType)) {
            foreach ($this->possibleCommunicationTypes as $possibleCommunicationType => $key) {
                if ($this->validateCommunicationType($possibleCommunicationType)) {
                    $communicationType = $possibleCommunicationType;
                    break;
                }
            }
        }

        return $communicationType;
    }

    public function isServerCommunicationType() {
        $communicationType = $this->determineCommunicationType();
        $prefix = BreinifyPlugIn::$SERVER_SIDE_PREFIX;

        return substr($communicationType, 0, strlen($prefix)) === $prefix;
    }

    public function setAndStore($data) {
        $this->set($data);
        $this->store();
    }

    public function set($data) {
        syslog(LOG_DEBUG, 'Set settings with: ' . json_encode($data));

        /** @noinspection PhpUnusedParameterInspection */
        $this->doProps(function ($option, $prop, $attribute) use ($data) {
            if (isset($data[$attribute])) {
                $value = $data[$attribute];

                if ($attribute === 'secret' && $this->getHiddenSecret() === $value) {
                    // we don't want to set the hidden secret, so skip it
                } else {

                    // validate if we have one
                    $validator = '_validate' . ucfirst($attribute);
                    syslog(LOG_DEBUG, $validator);
                    $newValue = empty($value) ? null : $value;
                    if (method_exists($this, $validator)) {
                        call_user_func([$this, $validator], $newValue, $data);
                    }

                    // everything is fine
                    $this->$prop = $newValue;
                }
            }
        });
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    /**
     * @param $value string the type to be validated
     * @throws GuiException if the type is not supported
     */
    private function _validateCommunicationType($value) {
        syslog(LOG_DEBUG, 'Validating CommunicationType: ' . $value);

        if (!empty($value) && !$this->validateCommunicationType($value)) {
            throw new GuiException(GuiException::$COMMUNICATION_TYPE_NOT_SUPPORTED);
        }
    }

    public function get($list = null) {
        $list = empty($list) ? null : (is_array($list) ? $list : []);

        /** @noinspection PhpUnusedParameterInspection */
        return $this->doProps(function ($option, $prop, $attribute, $result) use ($list) {
            $result = empty($result) ? [] : $result;
            if ($attribute !== 'secret' && $attribute !== 'password' &&
                ($list === null || in_array($attribute, $list, true))
            ) {
                $result[$attribute] = $this->$prop;
            }

            return $result;
        });
    }

    public function store() {
        $this->doProps(function ($option, $prop) {
            update_option($option, $this->$prop, true);
        });
    }

    public function reset() {
        $this->doProps(function ($option) {
            delete_option($option);
        });

        $this->deleteDbStructure();
    }

    public function getPossibleCommunicationTypes() {
        return $this->possibleCommunicationTypes;
    }

    public function getPossibleCategories() {
        return $this->possibleCategories;
    }

    public function getCurrentUser() {
        return $this->currentUser;
    }

    public function getActiveActivities() {
        return $this->activeActivities;
    }

    /**
     * Checks if an activity is activated for tracking.
     *
     * @param $activity string the activity to be checked
     * @return bool true if the activity is active, otherwise false
     */
    public function isActiveActivity($activity) {

        if ($this->activeActivities === null || !isset($this->activeActivities[$activity])) {
            return true;
        } else {
            // we want null to be true, so new activities are activated by default
            return $this->activeActivities[$activity] !== false;
        }
    }

    /**
     * Specifies if an activity should be activated or not.
     *
     * @param $activity string the name of the activity
     * @param $value bool the status, i.e., true or false
     */
    public function setActiveActivity($activity, $value) {
        $this->activeActivities[$activity] = $value === null ? null : Utility::is($value);
        $this->property_jsonActiveActivities = json_encode($this->activeActivities);
    }

    public function setActiveActivities($activities) {
        foreach ($activities as $activity => $value) {
            $this->activeActivities[$activity] = $value === null ? null : Utility::is($value);
        }

        $this->property_jsonActiveActivities = json_encode($this->activeActivities);
    }

    private function validateCommunicationType($communicationType) {

        if ($communicationType === null) {
            return false;
        } else if ($communicationType === BreinifyPlugIn::$SERVER_SIDE_PREFIX . BreinifyPlugIn::$SERVER_SIDE_AJAX_CURL) {
            return function_exists('curl_init');
        } else if ($communicationType === BreinifyPlugIn::$SERVER_SIDE_PREFIX . BreinifyPlugIn::$SERVER_SIDE_AJAX_FILE_GET_CONTENTS) {
            return function_exists('stream_context_create') && function_exists('file_get_contents') && Utility::isIniSet('allow_url_fopen');
        } else if ($communicationType === BreinifyPlugIn::$CLIENT_SIDE) {
            return true;
        } else {
            return false;
        }
    }

    private function doProps($fn) {
        $result = null;
        $props = get_object_vars($this);

        foreach ($props as $prop => $val) {
            $len = strlen($this->propertyPrefix);

            if (substr($prop, 0, $len) === $this->propertyPrefix) {
                $attribute = substr($prop, $len);
                $option = $this->prefix . substr($prop, $len);
                $result = $fn($option, $prop, $attribute, $result);
            }
        }

        return $result;
    }

    private function readBufferedValues($cookie = null) {
        $user = $this->getCurrentUser();

        // make sure we have a user and an apiKey
        if (empty($user)) {
            syslog(LOG_DEBUG, 'Could not get any activities missing user...');

            return [];
        }

        // make sure we have a user-email
        $userEmail = $user->user_email;
        if (empty($userEmail)) {
            syslog(LOG_DEBUG, 'Could not get any activities missing userEmail...');

            return [];
        }

        syslog(LOG_DEBUG, 'Retrieving activities for user ' . $userEmail . ' and current apiKey...');

        /* @var wpdb $wpdb */
        $wpdb = $GLOBALS['wpdb'];

        /** @noinspection SqlNoDataSourceInspection */
        /** @noinspection SqlDialectInspection */
        return $wpdb->get_results('SELECT id, cookie, json FROM ' . ($wpdb->prefix . self::$dbValuesTable) . ' WHERE userEmail="' . $userEmail . '"' . (empty($cookie) ? '' : ' AND cookie="' . $cookie . '"'), "ARRAY_A");
    }
}