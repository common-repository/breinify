<?php
if (!defined('ABSPATH')) {
    exit;
}

$view = BreinifyViewManager::instance();

$message = __('Your personal Brein is just a click away.', 'breinify-text-domain');
$url = esc_url(admin_url('admin.php?page=' . $view->createPageName('setup')));
$button = __('Setup Breinify', 'breinify-text-domain');

echo '<div class="updated message-breinify">';
echo '<p>' . $message . '</p>';
echo '<p class="submit"><a href="' . $url . '" class="button-primary">' . $button . '</a></p>';
echo '</div>';