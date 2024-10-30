<?php
/*
 * In this part we collect some data which is injected in the side.
 * The front-end should avoid using unnecessary calculations.
 */
$plugIn = BreinifyPlugIn::instance();
$settings = BreinifySettings::instance();
$view = BreinifyViewManager::instance();

if (!defined('ABSPATH') || !$plugIn->isAdmin()) {
    exit;
}

$plugIn->req('libraries/lib-uiUtility');

$logoUrl = $plugIn->resolveUrl('img/breinify_logo_large.png');
$faviconUrl = $plugIn->resolveUrl('img/favicon.ico');
$adminUrl = esc_url(admin_url('admin.php?page=' . $view->createPageName('main') . '&tab=advanced_settings&afterSetup=true'));

$currentUser = $settings->getCurrentUser();
$firstName = $currentUser->user_firstname;
$lastName = $currentUser->user_lastname;
$email = $currentUser->user_email;

$defaultPage = $settings->isInitialized() ? '#setup-success-breinify' : '#setup-main-breinify';
?>

    <!DOCTYPE html>
    <html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
    <head>
        <meta name="viewport" content="width=device-width"/>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title><?= __('Breinify: Set up YOUR personal Brein!', 'breinify-text-domain'); ?></title>
        <?php wp_print_scripts(); ?>
        <?php do_action('admin_print_styles'); ?>
        <?php do_action('admin_head'); ?>
        <link rel="shortcut icon" href="<?= $faviconUrl; ?>" type="image/x-icon">
        <link rel="icon" href="<?= $faviconUrl; ?>" type="image/x-icon">

        <script>
            jQuery(document).ready(function () {
                window.setup_breinify.initUi('<?= $plugIn->getCommunicationType() ?>', '<?= $plugIn->getAjaxUrl() ?>', '<?= $plugIn->getRestUrl() ?>', {
                    'breinify-block-ui-title-dooneclicksetup_embrest_signup': '<?=  __('Creating your Breinify account...', 'breinify-text-domain'); ?>',
                    'breinify-block-ui-message-dooneclicksetup_embrest_signup': '<?=  __('just need a couple seconds.', 'breinify-text-domain'); ?>',
                    'breinify-block-ui-title-dosignup_embrest_signup': '<?=  __('Creating your Breinify account...', 'breinify-text-domain'); ?>',
                    'breinify-block-ui-message-dosignup_embrest_signup': '<?=  __('Just give us a second to create your account.', 'breinify-text-domain'); ?>',
                    'breinify-block-ui-title-confirm': '<?=  __('Please check your email to verify your new account...', 'breinify-text-domain'); ?>',
                    'breinify-block-ui-message-confirm': '<?=  __('Waiting for email confirmation.', 'breinify-text-domain'); ?>',
                    'breinify-block-ui-title-dologin_embrest_login': '<?=  __('Checking your Credentials...', 'breinify-text-domain'); ?>',
                    'breinify-block-ui-title-setup': '<?=  __('Finalizing your WordPress settings...', 'breinify-text-domain'); ?>',
                    'breinify-block-ui-message-setup': '<?=  __('We are currently integrating with your WordPress ', 'breinify-text-domain'); ?>',
                    'breinify-block-ui-title-default': '<?=  __('Please wait...', 'breinify-text-domain'); ?>',
                    'breinify-block-ui-message-default': '<?=  __('... we are processing your information.', 'breinify-text-domain'); ?>',
                    'breinify-overview-email': '<?=  __('Email', 'breinify-text-domain'); ?>',
                    'breinify-overview-firstName': '<?=  __('First name', 'breinify-text-domain'); ?>',
                    'breinify-overview-lastName': '<?=  __('Last name', 'breinify-text-domain'); ?>',
                    'breinify-overview-apiKey': '<?=  __('Api-key', 'breinify-text-domain'); ?>',
                    'breinify-error-configuration': '<?=  __('Unexpected response: Invalid configuration created!', 'breinify-text-domain'); ?>',
                    'breinify-error-apikeys': '<?=  __('Unexpected response: Invalid response of api-keys!', 'breinify-text-domain'); ?>',
                    'breinify-message-default': '<?=  __('This message is a placeholder!', 'breinify-text-domain'); ?>'
                }, '<?= $defaultPage ?>');
            });
        </script>
    </head>
    <body class="wp-core-ui updated container-setup-breinify">

    <!-- LOGO -->
    <div class="container-centered-breinify">
        <img class="logo-breinify" src="<?= $logoUrl; ?>">
    </div>

    <!-- ERROR -->
    <div id="setup-error-breinify" class="error container-centered-breinify">
        <h3><?= __('Error', 'breinify-text-domain'); ?>:</h3>
        <p></p>
    </div>

    <!-- PAGE: MAIN PAGE -->
    <div id="setup-main-breinify" class="setup-page-breinify">

        <!-- CONTENT: 1-CLICK-SETUP -->
        <div id="setup-one-click-breinify" class="setup-content-breinify">
            <p class="text-center" style="margin-bottom:0"><?php __('Setting up your personal <span class="text-logo-breinify">Brein</span> will only take a view seconds.', 'breinify-text-domain'); ?></p>
            <p class="text-center" style="margin-top:0"><?php printf(__('Just use our <span class="bold">1-Click-Setup</span> to create an account for <span class="bold">%s</span><br/>and initialize your configuration.', 'breinify-text-domain'), $email); ?></p>

            <?= UiUtility::createFormTable([
                ['type'  => 'hidden',
                 'name'  => 'firstName',
                 'value' => $firstName],
                ['type'  => 'hidden',
                 'name'  => 'lastName',
                 'value' => $lastName],
                ['type'  => 'hidden',
                 'label' => 'Email',
                 'name'  => 'email',
                 'value' => $email],
                ['type'  => 'hidden',
                 'name'  => 'type',
                 'value' => 'dooneclicksetup_embrest_signup']
            ], 'setup-one-click-breinify-form') ?>

            <!-- BUTTON -->
            <p class="submit container-centered-breinify" style="margin: 20px 0 20px 0">
                <a class="button-primary button-large-breinify"
                   data-content-table-selector="#setup-one-click-breinify table">
                    <?= __('Start 1-Click-Setup', 'breinify-text-domain'); ?>
                </a>
            </p>

            <p>
                <small><?= __('The system will generate a password for you, which will be send to you via email.', 'breinify-text-domain'); ?></small>
                <small><?= __('If you prefer to create the account manually, or if you already have an account, just select one of the corresponding links on the bottom of the page.', 'breinify-text-domain'); ?></small>
            </p>
        </div>

        <!-- CONTENT: MANUAL SIGNUP -->
        <div id="setup-manual-signup-breinify" class="setup-content-breinify">
            <p class="text-center"><?= __('To get your personal <span class="text-logo-breinify">Brein</span> set up, we need some of the following information.', 'breinify-text-domain'); ?></p>

            <?= UiUtility::createFormTable([
                ['label' => __('First name', 'breinify-text-domain'),
                 'name'  => 'firstName',
                 'value' => $firstName],
                ['label' => __('Last name', 'breinify-text-domain'),
                 'name'  => 'lastName',
                 'value' => $lastName],
                ['label'      => 'Email',
                 'name'       => 'email',
                 'validation' => 'email',
                 'value'      => $email],
                ['label' => __('Password', 'breinify-text-domain'),
                 'type'  => 'password',
                 'name'  => 'password',
                 'range' => '[5,50]',
                 'id'    => 'setup-manual-signup-breinify-password',
                 'value' => ''],
                ['label'   => __('Re-enter password', 'breinify-text-domain'),
                 'type'    => 'password',
                 'range'   => '[5,50]',
                 'equalTo' => 'setup-manual-signup-breinify-password',
                 'name'    => 'validationPassword',
                 'value'   => ''],
                ['type'  => 'hidden',
                 'name'  => 'type',
                 'value' => 'dosignup_embrest_signup']
            ], 'setup-manual-signup-breinify-form') ?>

            <!-- BUTTON -->
            <p class="submit container-centered-breinify" style="margin: 20px 0 0 0">
                <a class="button-primary" data-content-table-selector="#setup-manual-signup-breinify table">
                    <?= __('Create and Setup', 'breinify-text-domain'); ?>
                </a>
            </p>
        </div>

        <!-- CONTENT: SETUP WITH LOGIN -->
        <div id="setup-login-breinify" class="setup-content-breinify">
            <p class="text-center"><?= __('Please enter your user credentials.', 'breinify-text-domain'); ?></p>

            <?= UiUtility::createFormTable([
                ['label'      => __('Username', 'breinify-text-domain'),
                 'name'       => 'username',
                 'validation' => 'email',
                 'value'      => $email],
                ['label' => __('Password', 'breinify-text-domain'),
                 'type'  => 'password',
                 'name'  => 'password',
                 'value' => ''],
                ['type'  => 'hidden',
                 'name'  => 'type',
                 'value' => 'dologin_embrest_login']
            ], 'setup-login-breinify-form') ?>

            <!-- BUTTON -->
            <p class="submit container-centered-breinify" style="margin: 20px 0 0 0">
                <a class="button-primary" data-content-table-selector="#setup-login-breinify table">
                    <?= __('Login and Setup', 'breinify-text-domain'); ?>
                </a>
            </p>
        </div>

        <!-- FOOTER -->
        <div class="setup-footer-breinify container-centered-breinify">
            <hr>
            <div>
                <a data-content-selector="#setup-one-click-breinify"><?= __('1-Click-Setup', 'breinify-text-domain'); ?></a>
                |
                <a data-content-selector="#setup-manual-signup-breinify"><?= __('Manual Setup', 'breinify-text-domain'); ?></a>
                |
                <a data-content-selector="#setup-login-breinify"><?= __('Use Existing Account', 'breinify-text-domain'); ?></a>
                |
                <a href="<?= $adminUrl ?>"><?= __('Return to Admin Panel', 'breinify-text-domain'); ?></a>
            </div>
        </div>
    </div>

    <!-- PAGE: SELECT API-KEY -->
    <div id="setup-apikey-breinify" class="setup-page-breinify">
        <p><?= __('We have found <span class="bold">multiple api-keys</span> for the specified account. <br>Please select the one api-key you would like to use with your WordPress site.', 'breinify-text-domain'); ?></p>

        <?= UiUtility::createFormTable([
            ['label' => __('Api-Key', 'breinify-text-domain'),
             'type'  => 'select',
             'name'  => 'apiKey'],
        ], null, false) ?>

        <!-- BUTTON -->
        <p class="submit container-centered-breinify" style="margin: 20px 0 0 0">
            <a class="button-primary" data-page-selector="#setup-success-breinify">
                <?= __('Select Api-Key and Setup', 'breinify-text-domain'); ?>
            </a>
        </p>

        <!-- FOOTER -->
        <div class="setup-footer-breinify container-centered-breinify">
            <hr>
            <div>
                <a href="javascript:location.reload()"><?= __('Start Over', 'breinify-text-domain'); ?></a>
                |
                <a href="<?= $adminUrl ?>"><?= __('Return to Admin Panel', 'breinify-text-domain'); ?></a>
            </div>
        </div>
    </div>

    <!-- PAGE: SUCCESS -->
    <div id="setup-success-breinify" class="setup-page-breinify" data-save-configuration="true">
        <h2 class="container-centered-breinify"><?= __('Your <span class=\"text-logo-breinify\">Brein</span> engine is all set up!', 'breinify-text-domain'); ?></h2>

        <p class="text-center"><?= __('Congratulations! The Breinify plugin is all set up. We\'re a fast moving startup constantly implementing new mind-blowing features on a monthly basis. <span class="bold"><br>So, be sure to keep your plugin updated!</span> <br><br>We look forward to providing you with new insights with our <span class="bold">360° artificial intelligence engine</span>.', 'breinify-text-domain'); ?></p>

        <p id="config-overview-breinify"></p>

        <!-- BUTTON -->
        <p class="submit container-centered-breinify" style="margin: 20px 0 0 0">
            <a href="<?= $adminUrl ?>" class="button-primary">
                <?= __('Return to Admin Panel', 'breinify-text-domain'); ?>
            </a>
        </p>
    </div>

    </body>
    </html>

<?php

// make sure the errors are not printed
exit;
