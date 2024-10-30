<?php

/*
 * Plugin Name: Breinify
 * Plugin URI:  http://www.github.com/breinify/brein-wordpress
 * Description: Artificial intelligence engine powered by collective intelligence for your website. Learn some interesting insights about your visitors!
 * Version:     1.2
 *
 * Author:      Breinify, Inc.
 * Author URI:  https://www.breinify.com
 *
 * License:     GPL2
 * License URI: https://github.com/Breinify/brein-wordpress-release/blob/master/LICENSE
 *
 * Domain Path: /languages
 * Text Domain: breinify-text-domain
 */

// include whatever is needed
require_once(dirname(__FILE__) . '/includes/classes/BreinifyPlugIn.php');

// make sure we have a log
openlog("BreinifyPlugIn", LOG_PID | LOG_PERROR, LOG_LOCAL0);

syslog(LOG_DEBUG, 'Handling (' . $_SERVER['REQUEST_METHOD'] . '): ' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

// set up the plugIn
BreinifyPlugIn::instance()->setUp(__FILE__);

// close the log, after execution
//closelog();