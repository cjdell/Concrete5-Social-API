<?php

/**
 Provides a common abstraction to talk to social networks
 */
abstract class SocialService {

    protected $type;

    public static function getService($socialType) {
        $className = ucfirst($socialType) . 'Service';

        if (!class_exists($className)) throw new Exception("Invalid social type $socialType");

        return new $className;
    }

    public function __construct() {

    }

    /**
     Get the human readable network name
     */
    public function getNetworkName() {
        return ucfirst($this->type);
    }

    /**
     Get the login URL for this network
     */
    public function getLoginUrl() {
        $uh = Loader::helper('concrete/urls');
        return $uh->getToolsURL($this->type . '_login', 'social_api');
    }

    /**
     Handle the sign in response data from the social network
     */
    public function handleSignInResponse(stdClass $req) {
        global $u; Loader::model('user_list');

        $ul = new UserList();

        $this->applyUserSearchFilter($ul, $req);

        $users = $ul->get(1);

        if (count($users)) {
            // User already exists
            $uo = $users[0];
        }
        else if ($this->hasUser()) {
            // User doesn't already have integration with this social network, so we'll assume the current logged in user is to be attached
            $uo = $this->getUserInfo();
        }
        else {
            // User needs to be created
            $data = $this->getUserAccountData($req);
            $uo = UserInfo::add($data);

            // This is set so that we know that the newly created account cannot be used by itself (i.e. it has no password)
            $uo->setAttribute('has_unknown_password', true);

            // Keep a record of the original network that created this account...
            $uo->setAttribute('created_by_network', $this->type);
        }

        $userID = $uo->getUserID();

        if ($userID == 1) throw new Exception("Cannot log in as the admin account!");

        $groupName = $this->getNetworkName() . ' Users';
        $group = Group::getByName($groupName);

        if (!$group or !$group->getGroupID()) {
            $group = Group::add($groupName, 'This group contains ' . $groupName);
        }

        $user = User::getByUserID($userID);
        $user->enterGroup($group);

        // Append to the list of connected social networks
        $socialNetworks = explode(',', $uo->getAttribute('social_networks'));
        $socialNetworks[] = $this->type;
        $socialNetworks = array_filter(array_unique($socialNetworks));
        $uo->setAttribute('social_networks', implode(',', $socialNetworks));

        $this->attachSocialData($uo, $req);

        $resp = new stdClass();
        $resp->user_id = $userID;

        User::loginByUserID($userID);

        return $resp;
    }

    public function postMessage($message) {
        if (!$this->hasIntegration()) throw new SocialIntegrationRequiredException(get_class($this));

        return $this->doPostMessage($message);
    }

    public abstract function hasIntegration();

    public abstract function getSocialName();

    /**
     * Post a message to the social network. Throws exceptions for errors
     */
    protected abstract function doPostMessage($message);

    protected abstract function applyUserSearchFilter(UserList $ul, stdClass $req);

    protected abstract function getUserAccountData(stdClass $req);

    protected abstract function attachSocialData(UserInfo $uo, stdClass $req);

    protected function getUserInfo() {
        global $u; $user_id = $u->uID; if (!is_numeric($user_id)) return NULL;

        $user_info = UserInfo::getByID($user_id);

        return $user_info;
    }

    protected function hasUser() {
        $user_info = $this->getUserInfo();

        return $user_info instanceof UserInfo;
    }

    /**
     Get the value of the given attribute for the current user
     */
    protected function getUserAttribute($attributeName) {
        $user_info = $this->getUserInfo();

        if (!$user_info) throw new UserNotSignedInException();

        return $user_info->getAttribute($attributeName);
    }
}

/**
 Twitter specific service implementation
 */
class TwitterService extends SocialService {

    public function __construct() {
        $this->type = 'twitter';

        Loader::library('social/twitter/twitteroauth',  'social_api');
        Loader::library('social/config/twconfig',       'social_api');
    }

    /**
     Does the current user have twitter integration?
     */
    public function hasIntegration() {
        return $this->hasUser() and is_numeric($this->getUserAttribute('twitter_user_id'));
    }

    public function getSocialName() {
        return $this->getUserAttribute('twitter_name');
    }

    /**
     Post a tweet
     */
    protected function doPostMessage($message) {
        if (strlen($message) > 140) throw new MessageTooLongException(140);

        $token = $this->getTwitterToken();

        $twitteroauth = new TwitterOAuth(YOUR_CONSUMER_KEY, YOUR_CONSUMER_SECRET, $token['oauth_token'], $token['oauth_token_secret']);
        $twitteroauth->post('statuses/update', array('status' => $message));
    }

    protected function applyUserSearchFilter(UserList $ul, stdClass $req) {
        $ul->filterByAttribute('twitter_user_id', $req->twitter_user_id);
    }

    protected function getUserAccountData(stdClass $req) {
        return ['uName' => $req->twitter_screen_name, 'uPassword' => 'P@55W0RD', 'uEmail' => $req->twitter_screen_name . '@twitter.com'];
    }

    protected function attachSocialData(UserInfo $uo, stdClass $req) {
        $uo->setAttribute('twitter_user_id',        $req->twitter_user_id);
        $uo->setAttribute('twitter_screen_name',    $req->twitter_screen_name);
        $uo->setAttribute('twitter_name',           $req->twitter_name);
        $uo->setAttribute('twitter_otoken',         $req->twitter_otoken);
        $uo->setAttribute('twitter_otoken_secret',  $req->twitter_otoken_secret);
        $uo->setAttribute('has_unknown_email',      true);
    }

    private function getTwitterToken() {
        return ['oauth_token' => $this->getUserAttribute('twitter_otoken'), 'oauth_token_secret' => $this->getUserAttribute('twitter_otoken_secret')];
    }

}

/**
 Facebook specific service implementation
 */
class FacebookService extends SocialService {

    public function __construct() {
        $this->type = 'facebook';

        Loader::library('social/facebook/facebook',     'social_api');
        Loader::library('social/config/fbconfig',       'social_api');
    }

    public function hasIntegration() {
        return $this->hasUser() and is_numeric($this->getUserAttribute('facebook_user_id'));
    }

    public function getSocialName() {
        return $this->getUserAttribute('facebook_name');
    }

    protected function doPostMessage($message) {
        $msg = [ 'message' => $message ];

        $facebook = new Facebook(['appId' => APP_ID, 'secret' => APP_SECRET]);
        $result = $facebook->api('/' . $this->getUserAttribute('facebook_user_id') . '/feed', 'POST', $msg);

        // print_r($result);
        return $result['id'];   //100007191792311_1383624225220624
    }

    protected function applyUserSearchFilter(UserList $ul, stdClass $req) {
        $ul->filterByAttribute('facebook_user_id', $req->facebook_user_id);
    }

    protected function getUserAccountData(stdClass $req) {
        return ['uName' => $req->facebook_email, 'uPassword' => 'P@55W0RD', 'uEmail' => $req->facebook_email];
    }

    protected function attachSocialData(UserInfo $uo, stdClass $req) {
        $uo->setAttribute('facebook_user_id',       $req->facebook_user_id);
        $uo->setAttribute('facebook_user_name',     $req->facebook_user_name);
        $uo->setAttribute('facebook_name',          $req->facebook_name);
    }

}

class UserNotSignedInException extends Exception {

    public function __construct() {
        parent::__construct("User must be signed in to retrieve attribute values");
    }

}

class SocialIntegrationRequiredException extends Exception {

    public function __construct($socialType) {
        parent::__construct("User must have $socialType integration to tweet");
    }

}

class MessageTooLongException extends Exception {

    public function __construct($maxLength) {
        parent::__construct("Message extends max length of $maxLength");
    }

}