<?php

BreinifyPlugIn::instance()->req('/classes/tracker/BreinifyBaseTracker');

class BreinifyLogoutTracker extends BreinifyBaseTracker {

    protected function _actions() {
        return 'wp_logout';
    }

    public function func($action) {
        return 'logout';
    }

    public function logout($userLogin) {
        syslog(LOG_DEBUG, 'BreinifyActivityTrackersManager: Logout detected on ...' . $userLogin . ' ' . $this->settings->getCurrentUser()->user_firstname);

        $activity = new BreinifyActivity();
        $activity->applySettings($this->settings);
        $activity->addActivity('logout');

        $this->sendActivity($activity->data());
    }

    public function label($action) {
        return __('Logout', 'breinify-text-domain');
    }
}