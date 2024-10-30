<?php
if (!defined('ABSPATH')) {
    exit;
}

$view = BreinifyViewManager::instance();

$message = __('There seems to be a problem with the Breinify plug-in, please verify and clear the error log.', 'breinify-text-domain');
$url = esc_url(admin_url('admin.php?page=' . $view->createPageName('main') . '&tab=error_log'));
$button = __('Check Error Log', 'breinify-text-domain');

echo '<div class="error">';
echo '<p>' . $message . '</p>';
echo '<p class="submit"><a href="' . $url . '" class="button-primary">' . $button . '</a></p>';
echo '</div>';