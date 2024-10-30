<?php

/*
 * See https://codex.wordpress.org/Function_Reference/register_uninstall_hook#uninstall.php
 * for further information regarding the uninstall.php. As the article quotes, it is
 * emphasis to use the uninstall.php instead of the uninstall hook, i.e.,
 * "Emphasis is put on using the 'uninstall.php' way of uninstalling the plugin rather than
 * register_uninstall_hook."
 */
if (!defined('ABSPATH') || !defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

// include whatever is needed
require_once(dirname(__FILE__) . '/includes/classes/BreinifyPlugIn.php');
require_once(dirname(__FILE__) . '/includes/classes/BreinifySettings.php');

// make sure we have a log
openlog("BreinifyPlugIn", LOG_PID | LOG_PERROR, LOG_LOCAL0);

syslog(LOG_DEBUG, 'Uninstalling the Breinify plugins...');
setcookie(BreinifyPlugIn::$COOKIE_SESSIONID, '', time() - 60 * 60);
BreinifySettings::instance()->reset();
syslog(LOG_DEBUG, 'Finished uninstalling the Breinify plugin...');
