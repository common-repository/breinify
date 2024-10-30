<?php

class GuiException extends Exception {

    // errors from REST are numbered as in REST
    public static $REST_ERROR_GENERAL = 500;

    // server errors
    public static $REST_ERROR_INVALID_ENDPOINT = 1000;
    public static $REST_ERROR_METHOD_NOT_ALLOWED = 1001;

    public static $REST_ERROR_CREATING_USER = 2001;
    public static $REST_ERROR_LOGIN_FAILURE = 2003;
    public static $REST_ERROR_LOGIN_INACTIVE_ACCOUNT = 2004;
    public static $REST_ERROR_USER_ALREADY_EXISTS = 2006;
    public static $REST_ERROR_INVALID_PASSWORD = 2007;
    public static $REST_ERROR_INVALID_ATTRIBUTE_VALUE = 2017;
    public static $REST_ERROR_MISSING_ATTRIBUTE = 2018;
    public static $REST_ERROR_INVALID_USER = 2025;
    public static $REST_ERROR_SIGN_UP_ALREADY_CONFIRMED = 2029;

    // Service related errors
    public static $REST_ERROR_INVALID_SERVICE = 3000;

    // Permission errors
    public static $REST_ERROR_INSUFFICIENT_PERMISSION = 4004;

    // Api errors
    public static $REST_ERROR_INVALID_JSON = 7000;
    public static $REST_ERROR_INVALID_API_KEY = 7001;

    // general errors start with a 1xxxxx
    public static $GENERAL_ERROR = 100000;
    public static $GENERAL_UNEXPECTED = 100001;
    public static $GENERAL_DEPENDENCY = 100002;
    public static $GENERAL_INVALID_SESSIONID = 100003;

    // REST problems 2xxxxx
    public static $REST_INVALID_CLIENT_QUERY = 200000;
    public static $REST_SERVER_EXCEPTION = 200001;
    public static $REST_UNKNOWN_FAILURE = 200002;
    public static $REST_NO_OR_INVALID_API_KEYS = 200003;
    public static $REST_CONFIRMATION_TIMED_OUT = 200004;
    public static $REST_INVALID_COMMUNICATION_TYPE = 200005;

    // Validation problems 3xxxxx
    public static $COMMUNICATION_TYPE_NOT_SUPPORTED = 300000;

    // API problems 4xxxxx
    public static $API_INVALID_DATA = 400000;
    public static $API_INVALID_ACTIVITY = 400001;
    public static $API_FAILED_TO_SEND_ACTIVITY = 400002;

    // data problems 5xxxxx
    public static $SETUP_INVALID_TYPE = 500000;
    public static $SETUP_ALREADY_SETUP = 500001;

    // miscellaneous errors start with a 9xxxxx
    public static $JSON_INVALID = 900000;

    protected $parameters;

    public function __construct($code, $parameters = [], Exception $previous = null) {
        parent::__construct('', $code, $previous);

        $this->parameters = $parameters;
        $this->message = GuiException::resolve($this);
    }

    public function getParameters() {
        return $this->parameters;
    }

    /**
     * Resolves a GuiException to a message to be shown on the UI.
     *
     * @param $e mixed an exception which should be forwarded to the UI
     * @param $parameters array the parameters for the array (only recognized if $e is a integer)
     * @return string the text to be shown
     */
    public static function resolve($e, $parameters = null) {

        // figure out the parameters
        $code = null;
        if ($e instanceof GuiException) {
            $code = $e->getCode();
            $parameters = $e->getParameters();
        } else {
            $code = $e;
        }
        $parameters = empty($parameters) || !is_array($parameters) ? [] : $parameters;

        switch ($code) {
            case GuiException::$JSON_INVALID:
                return sprintf(__('The specified JSON could not be parsed: %s', 'breinify-text-domain'), GuiException::get($parameters, 0));
            case GuiException::$SETUP_INVALID_TYPE:
                return sprintf(__('The type "%s" is not supported for setup.', 'breinify-text-domain'), GuiException::get($parameters, 0));
            case GuiException::$SETUP_ALREADY_SETUP:
                return sprintf(__('The setup is already completed.', 'breinify-text-domain'));
            case GuiException::$REST_INVALID_CLIENT_QUERY:
                $status = GuiException::get($parameters, 0);
                if ($status === '403') {
                    return sprintf(__('The access to the server was declined, please make sure that your API key is still valid, you activate or deactivate the verify signature flag correctly, and set your secret <a href="%s">here</a>.', 'breinify-text-domain'), admin_url('admin.php?page=breinify-adminConsole-main&tab=advanced_settings&showSecret=true'));
                } else {
                    return sprintf(__('There seems to be a communication problem, please contact the system administrator (status: %d).', 'breinify-text-domain'), $status);
                }
            case GuiException::$REST_SERVER_EXCEPTION:
            case GuiException::$REST_UNKNOWN_FAILURE:
                return sprintf(__('Our server seems to have a problem, please contact the system administrator (status: %d).', 'breinify-text-domain'), GuiException::get($parameters, 0));
            case GuiException::$REST_ERROR_INVALID_ATTRIBUTE_VALUE:
                return sprintf(__('Please validate the following values: %s.', 'breinify-text-domain'), implode(', ', $parameters));
            case GuiException::$REST_ERROR_INVALID_USER:
            case GuiException::$REST_ERROR_INVALID_PASSWORD:
                return sprintf(__('Your credentials are invalid.', 'breinify-text-domain'));
            case GuiException::$REST_ERROR_MISSING_ATTRIBUTE:
                return sprintf(__('The value "%s" is not specified.', 'breinify-text-domain'), GuiException::get($parameters, 0));
            case GuiException::$REST_CONFIRMATION_TIMED_OUT:
                return sprintf(__('The confirmation timed out, please check your emails, confirm and bind the account by using the " Use Existing Account" option.', 'breinify-text-domain'));
            case GuiException::$REST_ERROR_SIGN_UP_ALREADY_CONFIRMED:
                return sprintf(__('We have a Breinify account with this email address. Please login with existing account.', 'breinify-text-domain'));
            case GuiException::$COMMUNICATION_TYPE_NOT_SUPPORTED:
                return sprintf(__('The selected communication-type is not supported by the system, please validate that all requirements for the type are met and consult your system administrator or the Breinify support if necessary.', 'breinify-text-domain'));
            case GuiException::$API_INVALID_DATA:
                return __('Invalid data send, please check the log-files for details.', 'breinify-text-domain');
            case GuiException::$API_INVALID_ACTIVITY:
                return __('Invalid activity send, please check the log-files for details.', 'breinify-text-domain');
            case GuiException::$API_FAILED_TO_SEND_ACTIVITY:
                return __('Unable to send the activity to the Breinify server.', 'breinify-text-domain');
            case GuiException::$GENERAL_DEPENDENCY:
                return sprintf(__('The needed depdency "%s" could not be resolved by the server.', 'breinify-text-domain'), GuiException::get($parameters, 0));
            case GuiException::$GENERAL_INVALID_SESSIONID:
                return sprintf(__('Cannot find a valid session identifier.', 'breinify-text-domain'));
            case GuiException::$GENERAL_ERROR:
            case GuiException::$GENERAL_UNEXPECTED:
            case GuiException::$REST_ERROR_GENERAL:
            case GuiException::$REST_INVALID_COMMUNICATION_TYPE:
            case GuiException::$REST_ERROR_INVALID_ENDPOINT:
            default:
                return sprintf(__('Our plugin seems to have a problem, please contact the provider (status: %d, message: %s)', 'breinify-text-domain'), $code, GuiException::get($parameters, 0));
        }
    }

    private static function get($parameters, $idx) {
        if ($idx < count($parameters)) {
            return htmlspecialchars($parameters[$idx]);
        } else {
            return '';
        }
    }
}