<?php

class Utility {

    public static function getFromArray($array, $field, $def) {
        if (empty($array)) {
            return $def;
        } else {
            return empty($array[$field]) ? $def : $array[$field];
        }
    }

    public static function isIniSet($setting) {
        $value = ini_get($setting);

        if ((int)$value > 0) {
            return true;
        } else {
            return self::is($value);
        }
    }

    public static function is($value) {

        if (is_bool($value)) {
            return $value;
        } else if (is_string($value)) {
            $lowerValue = strtolower($value);

            return ($lowerValue === 'true' || $lowerValue === 'on' || $lowerValue === 'yes');
        } else {
            return false;
        }
    }

    public static function format($unixTimestamp) {
        $timezone = get_option('timezone_string');
        $dateFormat = get_option('date_format');
        $dateFormat = empty($dateFormat) ? 'm-d-Y' : $dateFormat;
        $timeFormat = get_option('time_format');
        $timeFormat = empty($timeFormat) ? 'h:i:s A' : $timeFormat;

        $fullFormat = $dateFormat . ' ' . $timeFormat;

        $dt = new DateTime('@' . $unixTimestamp);
        if (empty($timezone)) {
            $strOffset = get_option('gmt_offset');
            $offset = empty($strOffset) || !is_numeric($strOffset) ? 0 : intval($strOffset);
            $timezone = timezone_name_from_abbr('', $offset * 3600, false);
        }
        $dt->setTimeZone(new DateTimeZone($timezone));

        return $dt->format($fullFormat);
    }

    public static function isActivePlugin($plugin) {
        return in_array($plugin, get_option('active_plugins', []));
    }
}