<?php

/** @noinspection PhpIncludeInspection */
require_once(explode('wp-content', __FILE__)[0] . 'wp-load.php');

$plugIn = BreinifyPlugIn::instance();
$plugIn->req('classes/BreinifyCookieManager');
$plugIn->req('classes/BreinifySettings');
$settings = BreinifySettings::instance();
$uiUtilityLibUrl = $plugIn->resolveUrl($plugIn->isDev() ? 'js/libraries/lib-uiUtility.js' : 'js/dist/breinify-wordpress-plugin.min.js');
$cookieName = BreinifyCookieManager::getCookieName(BreinifyCookieManager::$ACTIVITY_COOKIE);

// set cookie values if there are any cached
BreinifyCookieManager::setDelayedValues();

// first we should make sure we have the right file-type
header('Content-Type: text/javascript');

// make sure the file is not cached at all
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>

jQuery(document).ready(function ($) {

    // lets make sure there is only one instance
    if (window.injector_breinify_active === true) {
        return;
    } else {
        window.injector_breinify_active = true;
    }

    // set some values we need
    var ajax = {
        'communicationType': '<?= $settings->determineCommunicationType(); ?>',
        'ajaxUrl': '<?= $plugIn->getAjaxUrl(); ?>',
        'restUrl': '<?= $plugIn->getApiUrl(); ?>'
    };
    var handleCookies = function () {
        uiUtility_breinify.handleCookies(ajax, '<?= $cookieName; ?>');
    };
    var start = function () {
        handleCookies();
        setInterval(handleCookies, 1000);
    };

    // run the cookie handling
    if (typeof uiUtility_breinify === 'undefined') {
        $.getScript('<?= $uiUtilityLibUrl; ?>', function () {
            start();
        });
    } else {
        start();
    }
});