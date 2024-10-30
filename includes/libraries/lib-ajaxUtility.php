<?php

/*
 * Do some imports needed by this utility library.
 */
BreinifyPlugIn::instance()->req('classes/GuiException');
BreinifyPlugIn::instance()->req('classes/BreinifySettings');
BreinifyPlugIn::instance()->req('libraries/lib-utility');

class AjaxUtility {

    public static function registerPosts($instance) {
        $class = get_class($instance);

        $publicMethods = [];
        if (!empty(get_object_vars($instance)['publicMethods'])) {
            $publicMethods = get_object_vars($instance)['publicMethods'];
        }

        foreach (get_class_methods($instance) as $method) {
            if ($method !== 'instance' && $method !== '__construct') {

                if (in_array($method, $publicMethods, true)) {
                    $hook = strtolower('wp_ajax_nopriv_' . $class . '::' . $method);
                    self::createHook($hook, $instance, $class, $method);
                }

                // create the hook
                $hook = strtolower('wp_ajax_' . $class . '::' . $method);
                self::createHook($hook, $instance, $class, $method);
            }
        }
    }

    public static function is($action, $clazz) {
        return substr($action, 0, strlen($clazz)) === strtolower($clazz);
    }

    public static function saveApi($endPoint, $data) {
        try {
            return AjaxUtility::api($endPoint, $data);
        } catch (Exception $e) {
            syslog(LOG_ERR, 'Could not send data "' . json_encode($data) . '" handle API call to "' . $endPoint . '" (error: "' . $e->getMessage() . '", number: ' . $e->getCode() . ', file: ' . $e->getFile() . ', line: ' . $e->getLine() . ', ...');
            syslog(LOG_ERR, $e->getTraceAsString());

            BreinifySettings::instance()->writeErrorLog($e->getCode(), self::createPayload($e));

            return null;
        }
    }

    public static function api($endPoint, $data) {
        $url = BreinifyPlugIn::instance()->resolveApiEndPoint($endPoint);
        syslog(LOG_DEBUG, 'Sending post "' . json_encode($data) . '" to api: "' . $url . '"...');

        return AjaxUtility::post($url, $data);
    }

    public static function rest($endPoint, $data) {
        $url = BreinifyPlugIn::instance()->resolveRestEndPoint($endPoint);
        syslog(LOG_DEBUG, 'Sending post "' . json_encode($data) . '" to rest: "' . $url . '"...');

        return AjaxUtility::post($url, $data);
    }

    private static function createHook($hook, $instance, $class, $method) {
        syslog(LOG_DEBUG, 'Hooking "' . $hook . '" to "' . $class . "::" . $method . '()"...');

        /*
         * Add the action: Wrap it so that the concrete implementation can
         * focus on the important stuff.
         */
        add_action($hook, function () use ($instance, $method) {
            openlog("BreinifyPlugIn", LOG_PID | LOG_PERROR, LOG_LOCAL0);

            syslog(LOG_DEBUG, 'Received post command at "' . $method . '"...');

            /*
             * Execute the method and handle known errors vs. unknown
             */
            $response = null;
            $error = null;
            try {
                ob_start();
                $response = call_user_func([$instance, $method]);
                $content = ob_get_clean();

                if (!empty($content) && trim($content) !== '') {
                    throw new GuiException(GuiException::$GENERAL_ERROR, [$content]);
                }
            } catch (GuiException $e) {
                syslog(LOG_ERR, 'Executing ' . $method . ' throw an exception: "' . $e->getMessage() . '" (' . $e->getCode() . ')...');
                $error = GuiException::resolve($e, [$e->getMessage()]);
            } catch (Exception $e) {
                syslog(LOG_ERR, 'Executing ' . $method . ' throw an exception: "' . $e->getMessage() . '"...');
                $error = GuiException::resolve(GuiException::$GENERAL_ERROR, [$e->getMessage()]);
            }

            // clean-up
            if (ob_get_length()) {
                ob_end_clean();
            }

            // if we have an response let's use it
            $output = '';
            if (!empty($response)) {
                try {
                    $output = $response;
                } catch (Exception $e) {
                    $error = GuiException::resolve(GuiException::$JSON_INVALID, [$e->getMessage()]);
                }
            }

            // if we have an error now let's use that
            if (!empty($error)) {
                $output = ['error' => $error];
            }

            // whatever the response is encode it and give it back
            syslog(LOG_DEBUG, 'Sending response "' . json_encode($output) . '" for "' . $method . '"...');
            closelog();

            wp_send_json($output);
        });
    }

    private static function post($url, $data) {

        /*
         * We currently do not support:
         *   - http_post_data (function)
         *   - HTTPRequest (class)
         * In the future it might be necessary to add these supports for other systems.
         * So far we need some sample systems having this shit up and running :).
         *
         * TODO: add additional features in the future
         */
        $result = null;
        $communicationType = BreinifyPlugIn::instance()->getCommunicationType();
        if (substr($communicationType, -strlen(BreinifyPlugIn::$SERVER_SIDE_AJAX_CURL)) == BreinifyPlugIn::$SERVER_SIDE_AJAX_CURL) {
            $result = AjaxUtility::doCurl($url, $data);
        } else if (substr($communicationType, -strlen(BreinifyPlugIn::$SERVER_SIDE_AJAX_FILE_GET_CONTENTS)) == BreinifyPlugIn::$SERVER_SIDE_AJAX_FILE_GET_CONTENTS) {
            $result = AjaxUtility::doFileGetContents($url, $data);
        } else {
            throw new GuiException(GuiException::$REST_INVALID_COMMUNICATION_TYPE, [$communicationType]);
        }

        return AjaxUtility::handleResult($result);
    }

    public static function doCurl($url, $data) {
        syslog(LOG_DEBUG, 'Using curl to retrieve data per POST with "' . json_encode($data) . '"...');

        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-type: application/json"]);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));

        $response = json_decode(curl_exec($curl), true);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        return ['status' => $status, 'response' => $response];
    }

    public static function doFileGetContents($url, $data) {
        syslog(LOG_DEBUG, 'Using file-get-contents to retrieve data per POST with "' . json_encode($data) . '"...');

        // use key 'http' even if you send the request to https://...
        $options = [
            'http' => [
                'header'  => 'Content-type: application/json',
                'method'  => 'POST',
                'content' => json_encode($data),
            ],
        ];
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        $status = $result === false ? AjaxUtility::getResponseCode($http_response_header)['response_code'] : 200;

        return ['status' => $status, 'response' => json_decode($result, true)];
    }

    private static function getResponseCode($http_response_header) {

        $head = [];
        foreach ($http_response_header as $key => $value) {
            $entries = explode(':', $value, 2);
            if (isset($entries[1]))
                $head[trim($entries[0])] = trim($entries[1]);
            else {
                $head[] = $value;
                if (preg_match("#HTTP/[0-9\\.]+\\s+([0-9]+)#", $value, $out))
                    $head['response_code'] = intval($out[1]);
            }
        }

        return $head;
    }

    public static function handleResult($result) {
        $status = intval($result['status']);
        $logError = empty($result['logError']) ? false : Utility::is($result['logError']);
        $jsonResponse = empty($result['jsonResponse']) ? false : Utility::is($result['jsonResponse']);

        // resolve the response
        $response = null;
        $responseJson = null;
        if (empty($result['response'])) {
            $response = [];
            $responseJson = json_encode([]);
        } else if ($jsonResponse) {
            $responseJson = $result['response'];
            $response = json_decode(stripslashes($responseJson), true);
        } else {
            $response = $result['response'];
            $responseJson = json_encode($response);
        }

        syslog(LOG_DEBUG, 'Received reply "' . $responseJson . '" with status "' . $status . '"...');

        $result = null;
        if ($status === 200) {
            $responseCode = empty($response['responseCode']) ? null : intval($response['responseCode']);

            if (empty($responseCode) || $responseCode === 200) {
                $result = empty($response['payload']) ? $response : $response['payload'];
            } else {
                $result = new GuiException($responseCode, $response['errorParameters']);
            }
        } else if ($status >= 400 && $status < 500) {
            $result = new GuiException(GuiException::$REST_INVALID_CLIENT_QUERY, [$status]);
        } else if ($status >= 500 && $status < 600) {
            $result = new GuiException(GuiException::$REST_SERVER_EXCEPTION, [$status]);
        } else {
            $result = new GuiException(GuiException::$REST_UNKNOWN_FAILURE, [$status]);
        }

        if ($result instanceof GuiException) {
            if ($logError) {
                BreinifySettings::instance()->writeErrorLog($result->getCode(), self::createPayload($result));
            }

            throw $result;
        } else {
            return $result;
        }
    }

    private static function createPayload($e) {
        return json_encode([
            'parameters' => $e instanceof GuiException ? $e->getParameters() : null,
            'message'    => $e->getMessage(),
            'code'       => $e->getCode()
        ]);
    }
}