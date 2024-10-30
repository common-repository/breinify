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
$plugIn->req('libraries/lib-utility');

$communicationType = $plugIn->getCommunicationType();
$ajaxUrl = $plugIn->getAjaxUrl();
$restUrl = $plugIn->getRestUrl();
?>

<?php if ($settings->isInitialized() && !$settings->isLoggedIn()) {
//
// Section shown when the plug-in is initialized but not logged in.
//
    ?>
    <script>
        jQuery(document).ready(function ($) {
            window.main_breinify.login({
                'communicationType': '<?= $communicationType ?>',
                'ajaxUrl': '<?= $ajaxUrl ?>',
                'restUrl': '<?= $restUrl ?>'
            }, {
                'username': '<?= $settings->getEmail() ?>',
                'password': '<?= $settings->getPassword() ?>'
            }, {
                'breinify-login-error-retry': '<?= __('Retry to Login', 'breinify-text-domain') ?>',
                'breinify-login-error-unbind': '<?= __('Unbind Account', 'breinify-text-domain') ?>',
                'breinify-login-error-message': '<?= sprintf(__('The login to the Breinify server failed. If it keeps failing check %s for information and login validation.<br/><div style="font-weight: 600">Reason:</div>', 'breinify-text-domain'), '<a href="' . $plugIn->consts('BREINIFY_SITE_URL') . '" target="_blank">' . $plugIn->consts('BREINIFY_SITE_URL') . '</a>') ?>',
                'breinify-login-error-default': '<?= sprintf(__('The login to the Breinify server failed, please try again later. If it keeps failing check %s for information and login validation.', 'breinify-text-domain'), '<a href="' . $plugIn->consts('BREINIFY_SITE_URL') . '" target="_blank">' . $plugIn->consts('BREINIFY_SITE_URL') . '</a>') ?>',
                'breinify-login-uiblocker-title': '<?= __('Logging In...', 'breinify-text-domain') ?>',
                'breinify-login-uiblocker-message': '<?= __('Please wait until you are logged in to the Breinify server.', 'breinify-text-domain') ?>'
            });
        });
    </script>

<?php } else if ($settings->isInitialized()) {
//
// Section shown when the plug-in is initialized.
//
    ?>

    <!-- load the script for the page -->
    <script>
        jQuery(document).ready(function ($) {
            window.main_breinify.initUi({
                    sessionId: '<?= $_COOKIE[BreinifyPlugIn::$COOKIE_SESSIONID]; ?>',
                    email: '<?= $settings->getEmail(); ?>'
                }, {
                    'communicationType': '<?= $communicationType; ?>',
                    'ajaxUrl': '<?= $ajaxUrl; ?>',
                    'restUrl': '<?= $restUrl; ?>'
                },
                <?= json_encode($settings->get()); ?>,
                <?= json_encode($settings->getPossibleCommunicationTypes()); ?>,
                <?= json_encode($settings->getPossibleCategories()); ?>,
                <?= json_encode(!empty($_GET['afterSetup']) && Utility::is($_GET['afterSetup'])) ?>,
                <?= json_encode(!empty($_GET['showSecret']) && Utility::is($_GET['showSecret'])) ?>,
                {
                    'breinify-advanced-settings-invalid-communicationType': '<?= __('The selected communication-type is invalid.', 'breinify-text-domain') ?>',
                    'breinify-advanced-settings-invalid-default': '<?= __('The selected value is invalid.', 'breinify-text-domain') ?>',
                    'breinify-advanced-settings-saved': '<?= __('The settings have been saved.', 'breinify-text-domain') ?>',
                    'breinify-advanced-settings-secret-active': '<?= sprintf(__('If a secret is used, please make sure that the verifying signature is activated (see %s).', 'breinify-text-domain'), '<a href="' . $plugIn->consts('BREINIFY_SITE_URL') . '" target="_blank">' . $plugIn->consts('BREINIFY_SITE_URL') . '</a>') ?>',
                    'breinify-advanced-settings-select-business-type': '<?= __('Please choose a \\\'Business type (category)\\\'.', 'breinify-text-domain') ?>',
                    'breinify-overview-email': '<?=  __('Email', 'breinify-text-domain'); ?>',
                    'breinify-overview-firstName': '<?=  __('First name', 'breinify-text-domain'); ?>',
                    'breinify-overview-lastName': '<?=  __('Last name', 'breinify-text-domain'); ?>',
                    'breinify-overview-apiKey': '<?=  __('Api-key', 'breinify-text-domain'); ?>',
                    'breinify-analytics-error-403': '<?=  __('Your session is expired or your credentials changed. Try to refresh!', 'breinify-text-domain'); ?>',
                    'breinify-analytics-error-loading': '<?=  __('Retrieving information from the server!', 'breinify-text-domain'); ?>',
                    'breinify-analytics-error-nodata': '<?=  __('There is currently no data collected!', 'breinify-text-domain'); ?>',
                    'breinify-analytics-error-novisitors': '<?=  __('You did not have any counted visitors!', 'breinify-text-domain'); ?>',
                    'breinify-analytics-error-default': '<?=  __('The data could not be retrieved from the Breinify server, please contact us!', 'breinify-text-domain'); ?>',
                    'breinify-message-default': '<?=  __('This message is a placeholder!', 'breinify-text-domain'); ?>'
                });
        });
    </script>

    <?php
    if (!empty($_GET['info'])) {
        echo UiUtility::createMessage($_GET['info']);
    }

    $utilTabs = UiUtility::createTabs([
        'dashboard'         => __('Dashboard', 'breinify-text-domain'),
        'activity_trackers' => __('Activity-Trackers', 'breinify-text-domain'),
        'advanced_settings' => __('Advanced Settings', 'breinify-text-domain'),
        'error_log'         => __('Error Log', 'breinify-text-domain')
    ]);
    $currentTab = $utilTabs['tab'];
    ?>

    <div class="wrap">
        <?= $utilTabs['html']; ?>
    </div>

    <!--
      DASHBOARD:
      -->
    <?php if ($currentTab === 'dashboard') {
        $utilSubTabs = UiUtility::createSubTabs($currentTab, [
            'current_collectives' => __('Current Collectives', 'breinify-text-domain'),
            'current_activities'  => __('Current Activities', 'breinify-text-domain')
        ]);
        $currentSubTab = $utilSubTabs['tab'];

        echo $utilSubTabs['html'];
        ?>
        <div class="wrap">
            <!--
                CURRENT COLLECTIVES:
              -->
            <?php if ($currentSubTab === 'current_collectives') { ?>

                <div id='breinify-dashboard-current-collectives' class='breinify-dashboard-frame'>
                    <div id='dashboard-message' class='breinify-dashboard-overlay'><span></span></div>
                    <div id='dashboard-content' class='breinify-dashboard-main' data-layout='vertical'
                         data-resize='true'>
                        <div class='breinify-dashboard-content' data-width='350px'>
                            <h1><?= __('Visitors Count', 'breinify-text-domain'); ?></h1>
                            <p><?= sprintf(__('Shows the ratio between <span class="breinify-text-highlight" style="color:%s">your number</span> of visitors<br/>with all the <span class="breinify-text-highlight" style="color:%s">unique visitors</span> in the category <b>%s</b>.', 'breinify-text-domain'), '#9EC1A3', '#B696DD', $settings->get()['category']); ?></p>
                            <div class='breinify-chart-frame'>
                                <div id='barcomperator-current-collectives'></div>
                            </div>
                        </div>
                        <div class='breinify-dashboard-content' data-width='100%'>
                            <h1><?= __('Visitors Distribution', 'breinify-text-domain'); ?></h1>
                            <p><?= sprintf(__('Understand what the visitors in your category <b>%s</b> where also interested in.<br/>The visualization shows the categories your visitors also looked at in the current time-frame.', 'breinify-text-domain'), $settings->get()['category']); ?></p>
                            <div class='breinify-chart-frame'>
                                <div id='treemap-current-collectives'></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--
                    CURRENT ACTIVITIES
                  -->
            <?php } else if ($currentSubTab === 'current_activities') { ?>

                <div id='highcharts-current-activities'></div>

                <!--
                    ERROR - INVALID SELECTION:
                  -->
            <?php } else { ?>
                <script>
                    window.main_breinify.showError('<?= sprintf(__('The selection <b>%s</b> is invalid.', 'breinify-text-domain'), $currentTab); ?>');
                </script>
            <?php } ?>
        </div>
        <!--
          ACTIVITY_TRACKERS:
          -->
    <?php } else if ($currentTab === 'activity_trackers') {
        $activity = 'login'; ?>

        <h3><?= __('Activity Trackers', 'breinify-text-domain') ?></h3>
        <p><?= __('Please specify which activities should be tracked.', 'breinify-text-domain') ?></p>
        <p><?php
            $trackers = [];

            /* @var BreinifyBaseTracker $tracker */
            foreach (BreinifyBaseTracker::getAll($this) as $tracker) {

                /* @var string $action */
                foreach ($tracker->actions() as $action) {
                    array_push($trackers,
                        ['label' => $tracker->label($action),
                         'type'  => 'checkbox',
                         'value' => $settings->isActiveActivity($action),
                         'name'  => $action,
                         'group' => $tracker->group($action)
                        ]
                    );
                }
            }

            usort($trackers, function ($a, $b) {
                $groupCmp = strcmp($a['group'], $b['group']);

                if ($groupCmp === 0) {
                    return strcmp($a['label'], $b['label']);
                } else {
                    return $groupCmp;
                }
            });

            echo UiUtility::createFormTable($trackers, 'main-activity-tracker-breinify', true, 'activities-table-breinify', false)
            ?>
        </p>
        <div class="container-centered-breinify" style="padding-top: 2px">
            <a href='javascript:main_breinify.saveActivityTrackerSettings()' class="button-primary">
                <?= __('Save ActivityTracker Settings', 'breinify-text-domain'); ?>
            </a>
        </div>


        <!--
          ADVANCED SETTINGS:
          -->
    <?php } else if ($currentTab === 'advanced_settings') { ?>
        <h3><?= __('Advanced Settings', 'breinify-text-domain') ?></h3>
        <p><?= __('In this sections additional settings can be modified. In general, a modification should not be necessary. Nevertheless, in some situations it may be handy.', 'breinify-text-domain') ?></p>
        <p>
            <?= UiUtility::createFormTable([
                ['label'            => __('Communication type', 'breinify-text-domain'),
                 'type'             => 'select',
                 'value'            => $settings->getCommunicationType(),
                 'values'           => $settings->getPossibleCommunicationTypes(),
                 'valueSelector'    => 'title',
                 'name'             => 'communicationType',
                 'dashicon'         => 'dashicons-info',
                 'dashicon-tooltip' => __('You can specify the way the plug-in communicates with the Brein engine. In general it is recommended that you communicate with the Brein Engine via your server (instead of utilizing the client\'s browser).', 'breinify-text-domain')
                ],
                ['label'            => __('Secret', 'breinify-text-domain'),
                 'type'             => 'password',
                 'value'            => $settings->getHiddenSecret(),
                 'name'             => 'secret',
                 'required'         => 'false',
                 'dashicon'         => 'dashicons-info',
                 'dashicon-tooltip' => sprintf(__('It is recommended to utilize a secret to enhance the security when communicating with the Brein Engine. The usage of a secret must be enabled, otherwise a communication with a secret is not possible. You can look-up your personal secret under %s using your log-in credentials.', 'breinify-text-domain'), htmlspecialchars('<a href="' . $plugIn->consts('BREINIFY_SITE_URL') . '" target="_blank">' . $plugIn->consts('BREINIFY_SITE_URL') . '</a>'))
                ],
                ['type' => 'separator'],
                ['label'            => __('Business type (category)', 'breinify-text-domain'),
                 'type'             => 'select',
                 'value'            => $settings->getCategory(),
                 'values'           => $settings->getPossibleCategories(),
                 'valueSelector'    => 'title',
                 'name'             => 'category',
                 'dashicon'         => 'dashicons-info',
                 'dashicon-tooltip' => __('Select any category your business belongs to.', 'breinify-text-domain')
                ]
            ], 'main-advanced-settings-breinify', true, 'settings-table-breinify', true);
            ?>
        </p>
        <div class="container-centered-breinify" style="padding-top: 2px">
            <a href='javascript:main_breinify.saveAdvancedSettings()' class="button-primary">
                <?= __('Save Advanced Settings', 'breinify-text-domain'); ?>
            </a>
        </div>

        <h3><?= __('Account Settings', 'breinify-text-domain') ?></h3>
        <p><?= __('Currently the following account is attached to this WordPress installation.', 'breinify-text-domain') ?></p>
        <div id="main-config-overview-breinify">
        </div>

        <h3><?= __('Reset', 'breinify-text-domain') ?></h3>
        <p><?= __('If you wish to reset the plugin or use a different account, you can unbind the current account. <br>After unbinding, the plugin will be reset back to the default settings as if you had just installed it.', 'breinify-text-domain') ?></p>
        <div class="container-centered-breinify" style="padding-top: 0">
            <a href="javascript:main_breinify.unbind();" class="button-primary">
                <?= __('Unbind Account', 'breinify-text-domain'); ?>
            </a>
        </div>

        <!--
          ERROR LOG:
          -->
    <?php } else if ($currentTab === 'error_log') { ?>
        <h3><?= __('Error Log', 'breinify-text-domain') ?></h3>
        <p><?= __('Here are errors shown (if any) regarding the communication with the Breinify server.', 'breinify-text-domain') ?></p>
        <p>
            <?php

            $output = '';
            $output .= '<table class="error-log">';

            $output .= '<tr>';
            $output .= '<th class="date">' . __('Date', 'breinify-text-domain') . '</th>';
            $output .= '<th class="message">' . __('Error', 'breinify-text-domain') . '</th>';
            $output .= '</tr>';

            $errorLog = $settings->getErrorLog();
            if (empty($errorLog)) {
                $output .= '<tr>';
                $output .= '<td class="global-message" colspan="2">' . __('no errors so far', 'breinify-text-domain') . '</td>';
                $output .= '</tr>';
            } else {
                foreach ($errorLog as $entry) {
                    $output .= '<tr>';
                    $output .= '<td class="date">' . $entry['date'] . '</td>';
                    $output .= '<td class="message">' . $entry['message'] . '</td>';
                    $output .= '</tr>';
                }
            }

            $output .= '</table>';

            echo $output;
            ?>
        </p>
        <div class="container-centered-breinify" style="padding-top: 0">
            <a href='javascript:location.reload()' class="button-primary">
                <?= __('Refresh Error Log', 'breinify-text-domain'); ?>
            </a>
            <a href='javascript:main_breinify.deleteErrors()' class="button-primary">
                <?= __('Clear Error Log', 'breinify-text-domain'); ?>
            </a>
        </div>

        <!--
          ERROR - INVALID SELECTION:
          -->
    <?php } else { ?>
        <script>
            window.main_breinify.showError('<?= sprintf(__('The selection <b>%s</b> is invalid.', 'breinify-text-domain'), $currentTab); ?>');
        </script>
    <?php } ?>


<?php } else {
//
// Section shown when the plug-in is not configured.
//

    // check if the plugin was just reseted
    if (!empty($_GET['tab']) && $_GET['tab'] === 'advanced_settings') {
        echo UiUtility::createMessage(__('The plugin was successfully reseted.', 'breinify-text-domain'));
    } ?>

    <h3><?= __('Setup your personal Brein™ analytics', 'breinify-text-domain') ?></h3>
    <p><?= __('Get ready and set up the <span class="text-logo-breinify">Breinify</span> plugin. It\'ll only take you a minute to experience the power of collective intelligence and start understanding your customers\' shopping behaviors in real-time.', 'breinify-text-domain') ?></p>

    <div class="container-centered-breinify" style="padding-top: 0">
        <a href='<?= esc_url(admin_url('admin.php?page=' . $view->createPageName('setup'))); ?>'
           class="button-primary button-large-breinify">
            <?= __('Setup Breinify', 'breinify-text-domain'); ?>
        </a>
    </div>
<?php } ?>