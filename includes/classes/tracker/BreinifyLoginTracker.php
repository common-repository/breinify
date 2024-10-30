<?php

BreinifyPlugIn::instance()->req('/classes/tracker/BreinifyBaseTracker');

class BreinifyLoginTracker extends BreinifyBaseTracker {

    protected function _actions() {
        return 'wp_login';
    }

    public function func($action) {
        return 'login';
    }

    public function login($userLogin) {
        $user = get_user_by('login', $userLogin);

        if (!$this->plugIn->isAdmin($user)) {
            $activity = new BreinifyActivity();
            $activity->applySettings($this->settings);
            $activity->setUser($user);
            $activity->addActivity('login');

            $this->sendActivity($activity->data());
        }
    }

    public function label($action) {
        return __('Login', 'breinify-text-domain');
    }
}