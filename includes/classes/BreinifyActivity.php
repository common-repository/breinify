<?php

/*
 * {
 *    // The engine supports different information about the
 *    // user as well, e.g., date of birth, or device identifiers.
 *    // For a full list, please refer to the documentation.
 *    "user": {
 *      "email":     "philipp@meisen.net",
 *      "firstName": "Philipp",
        "lastName":  "Meisen",
 *      "sessionId": "Rg3vHJZnehYLjVg7qi3bZjzg"
 *    },
 *    "activity": {
 *      "type":        "search",
 *      "description": "brownies recipe",
 *      "tags":        "food, recipe, valid customer"
 *    },
 *
 *    // the following attributes are added by the provided library
 *    "apiKey":        "D8A6-993E-0DC7-489A-84EC-B469-96A8-5947",
 *    "unixTimestamp": 1451962516,
 *    "signature":     "HUEHOrd1P26m26EAJc5auFSJWW1Zn0E0w8dAk2URep4="
 * }
 */

class BreinifyActivity {

    private $apiKey = null;
    private $secret = null;
    private $unixTimestamp = null;
    private $user = null;
    private $category = null;
    private $activities = [];

    public function __construct() {
        $this->setUnixTimestamp(null);
    }

    /**
     * @param $user WP_User a WordPress user;
     */
    public function setUser($user) {
        $sessionId = session_id();

        if (empty($user) && empty($sessionId)) {
            $this->user = null;
        } else if (empty($user)) {
            $this->user = [
                'sessionId' => $sessionId
            ];
        } else if (empty($sessionId)) {
            $this->user = [
                'email'     => $user->user_email,
                'firstName' => $user->user_firstname,
                'lastName'  => $user->user_lastname
            ];
        } else {
            $this->user = [
                'email'     => $user->user_email,
                'firstName' => $user->user_firstname,
                'lastName'  => $user->user_lastname,
                'sessionId' => $sessionId
            ];
        }
    }

    public function setUnixTimestamp($unixTimestamp) {
        $this->unixTimestamp = $unixTimestamp == null ? time() : $unixTimestamp;
    }

    /**
     * An activity has the fields type, description, and tags.
     *
     * @param $type
     * @param null $description the description of the activity
     * @param null $tags comma-separated list of tags
     * @return array $activity an array containing the activity to be added
     */
    public function addActivity($type, $description = null, $tags = null) {
        array_push($this->activities, [
            'type'        => $type,
            'description' => $description,
            'category'    => $this->category,
            'tags'        => (is_array($tags) ? implode(',', $tags) : $tags)
        ]);
    }

    /**
     * @param $settings BreinifySettings the settings to be applied
     */
    public function applySettings($settings) {

        // set the user
        $this->setUser($settings->getCurrentUser());

        // set the apiKey
        $this->setApiKey($settings->getApiKey());

        // set the category
        $this->setCategory($settings->getCategory());

        // set the secret
        $this->setSecret($settings->getSecret());
    }

    public function setApiKey($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function getApiKey() {
        return $this->apiKey;
    }

    public function setCategory($category) {
        $this->category = $category;
    }

    public function getCategory() {
        return $this->category;
    }

    public function getUserEmail() {
        return $this->user['email'];
    }

    public function getUnixTimestamp() {
        return $this->unixTimestamp;
    }

    public function setSecret($secret) {
        $this->secret = $secret;
    }

    public function data() {
        return [
            'user'          => $this->user,
            'activities'    => $this->activities,
            'apiKey'        => $this->apiKey,
            'unixTimestamp' => $this->unixTimestamp,
            'signature'     => $this->createSignature()
        ];
    }

    public function setData($data) {

        // validate the data first
        if (is_array($data) &&
            (!empty($data['activities']) || !empty($data['activity'])) &&
            !empty($data['apiKey']) &&
            !empty($data['user'])
        ) {
            $this->user = $data['user'];
            $this->activities = (!empty($data['activities']) ? $data['activities'] : [$data['activity']]);
            $this->apiKey = $data['apiKey'];
            $this->unixTimestamp = empty($data['unixTimestamp']) ? $this->unixTimestamp : $data['unixTimestamp'];


            return true;
        } else {
            return false;
        }
    }

    public function json() {
        return json_encode($this->data());
    }

    public function isValid() {
        return !empty($this->apiKey) && $this->validateActivities() &&
        !empty($this->user) && is_array($this->user) && count($this->user) > 0;
    }

    private function createSignature() {

        if (empty($this->secret)) {
            return null;
        } else {
            $activityLength = count($this->activities);
            $activity = count($this->activities) === 0 ? null : $this->activities[0];

            $message = sprintf("%s%d%d",
                empty($activity['type']) ? '' : $activity['type'],
                $this->unixTimestamp,
                $activityLength);

            return base64_encode(hash_hmac('sha256', $message, $this->secret, true));
        }
    }

    private function validateActivities() {

        if (!empty($this->activities) && is_array($this->activities) && count($this->activities) > 0) {
            foreach ($this->activities as $activity) {
                if (empty($activity['type']) || empty($activity['category'])) {
                    return false;
                }
            }

            return true;
        } else {
            return false;
        }
    }
}