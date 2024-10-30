<?php

BreinifyPlugIn::instance()->req('classes/BreinifySettings');

class BreinifyCookieManager {
    private static $PREFIX = 'breinify_';

    public static $ACTIVITY_COOKIE = 'activityCookie';

    public static function setDelayedValues() {
        $values = BreinifySettings::instance()->getBufferedValues();

        if (is_array($values) && count($values) > 0) {
            foreach ($values as $value) {
                $cookie = $value['cookie'];
                $json = $value['json'];
                $storedValue = empty($json) ? [] : json_decode($json);
                $combinedValue = array_merge($storedValue, self::_getCookieValue($cookie));

                if (self::_setCookie($cookie, $combinedValue)) {
                    BreinifySettings::instance()->removeBufferedValue($value['id']);
                }
            }
        }
    }

    public static function setCookie($key, $value) {
        $cookie = self::getCookieName($key);
        $currentValue = self::_getCookieValue($cookie);
        array_push($currentValue, $value);

        if (!self::_setCookie($cookie, $currentValue)) {
            $dbValue = BreinifySettings::instance()->getBufferedValue($cookie);
            $dbValue = empty($dbValue) ? [] : $dbValue;
            array_push($dbValue, $value);

            BreinifySettings::instance()->bufferValue($cookie, json_encode($dbValue));
        }
    }

    public static function getCookieName($key) {
        return self::$PREFIX . $key;
    }

    private static function _getCookieValue($cookie) {
        $currentValue = empty($_COOKIE[$cookie]) ? [] : json_decode(stripslashes($_COOKIE[$cookie]));

        return empty($currentValue) ? [] : $currentValue;
    }

    private static function _setCookie($cookie, $value) {
        $value = json_encode($value);

        if (@setcookie($cookie, $value, time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN)) {
            $_COOKIE[$cookie] = $value;

            return true;
        } else {
            return false;
        }
    }
}