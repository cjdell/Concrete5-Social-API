<?php

Loader::library('social_service', 'social_api');

/**
 Provides a helper methods for interacting with the user's profile
 */
class ProfileService {

    protected $userInfo = NULL;

    public function __construct() {
        global $u;

        // Default to the currently logged in user
        if (is_numeric($u->uID)) $this->userInfo = UserInfo::getByID($u->uID);
    }

    public function setUserInfo(UserInfo $userInfo) {
        $this->userInfo = $userInfo;
    }

    /**
     Get a list of the support social network types
     */
    public function getSocialNetworkTypes() {
        return ['facebook', 'twitter'];
    }

    /**
     Get all social network services objects
     */
    public function getSocialNetworkServices() {
        $services = [];

        foreach ($this->getSocialNetworkTypes() as $type) {
            $services[] = SocialService::getService($type);
        }

        return $services;
    }

    /**
     Disconnect the current user from specified social network (if possible)
     */
    public function disconnectSocialNetwork($type) {
        // Needs to check that the user has at least one valid method of signing in
        $services = $this->getSocialNetworkServices();

        $count = 0;     // This will hold the number of alternative sign in methods

        foreach ($services as $service) {
            if ($service->getNetworkType() == $type) continue;

            if ($service->hasIntegration()) $count++;
        }

        // Check the user has a C5 account
        if ($this->hasNativeAccount()) $count++;

        if ($count == 0) throw new Exception('Cannot disconnect your last remaining method of signing in');

        $service = SocialService::getService($type);
        $service->disconnect();
    }

    /**
     Does user has a usable native account?
     */
    public function hasNativeAccount() {


        if ($this->userInfo == NULL) return false;



        // If user has C5 password, it is another valid sign in method
        if ($this->userInfo->getAttribute('has_unknown_password')) return false;

        return true;
    }

    /**
     Return an email address for the user only if known
     */
    public function getKnownEmailAddress() {
        if ($this->userInfo == NULL) throw new Exception('User must be logged in to retrieve the user\'s email address');

        if ($this->userInfo->getAttribute('has_unknown_email')) return NULL;

        return $this->userInfo->getUserEmail();
    }

    /**
     Return an email address for the user only if known
     */
    public function getNames() {
        if ($this->userInfo == NULL) throw new Exception('User must be logged in to retrieve the user\'s names');

        return [ $this->userInfo->getAttribute('first_name'), $this->userInfo->getAttribute('last_name') ];
    }

    /**
     Login with a native C5 account
     */
    public function signIn($email, $password) {
        if (empty($email)) throw new Exception('Email address invalid');

        if (empty($password)) throw new Exception('Password invalid');

        $u = new User($email, $password);

        if ($u->isError()) {
            switch ($u->getError()) {
                case USER_NON_VALIDATED:
                    throw new Exception(t('This account has not yet been validated. Please check the email associated with this account and follow the link it contains.'));
                    break;
                case USER_INVALID:
                    if (USER_REGISTRATION_WITH_EMAIL_ADDRESS) {
                        throw new Exception(t('Invalid email address or password.'));
                    }
                    else {
                        throw new Exception(t('Invalid username or password.'));
                    }
                    break;
                case USER_INACTIVE:
                    throw new Exception(t('This user is inactive. Please contact us regarding this account.'));
                    break;
                default:
                    throw new Exception('An error occured with the login process');
                    break;
            }
        }

        return $u;
    }

    /**
     Begin the sign up procedure for the given email address
     */
    public function signUp($email) {
        $userName = str_replace('.', '_dot_', str_replace('@', '_at_', $email));

        $newPassword = '';
        $salt = "abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ123456789";
        for ($i = 0; $i < 7; $i++) {
            $newPassword .= substr($salt, rand() %strlen($salt), 1);
        }

        $data = array('uName' => $userName, 'uPassword' => $newPassword, 'uEmail' => $email);
        $oUser = UserInfo::add($data);

        //$mh = Loader::helper('mail');
        $message = new stdClass();

        if (USER_REGISTRATION_WITH_EMAIL_ADDRESS) {
            //$mh->addParameter('uName', $oUser->getUserEmail());
            $message->toName = $oUser->getUserEmail();
        }
        else {
            //$mh->addParameter('uName', $oUser->getUserName());
            $message->toName = $oUser->getUserName();
        }

        //$mh->to($oUser->getUserEmail());
        $message->toAddress = $oUser->getUserEmail();

        // Generate hash that'll be used to authenticate user, allowing them to change their password
        $h = Loader::helper('validation/identifier');
        $uHash = $h->generate('UserValidationHashes', 'uHash');

        $db = Loader::db();
        $db->Execute("DELETE FROM UserValidationHashes WHERE uID=?", array( $oUser->uID ) );
        $db->Execute("insert into UserValidationHashes (uID, uHash, uDateGenerated, type) values (?, ?, ?, ?)", array($oUser->uID, $uHash, time(),intval(UVTYPE_CHANGE_PASSWORD)));

        //$mh->addParameter('changePassURL', $this->getChangePasswordUrl($uHash));
        $message->changePassURL = $this->getChangePasswordUrl($uHash);

        if (defined('EMAIL_ADDRESS_FORGOT_PASSWORD')) {
            //$mh->from(EMAIL_ADDRESS_FORGOT_PASSWORD,  t('Account Confirmation'));
            $message->fromAddress = EMAIL_ADDRESS_FORGOT_PASSWORD;
            $message->fromName = t('Account Confirmation');
        }
        else {
            $adminUser = UserInfo::getByID(USER_SUPER_ID);
            if (is_object($adminUser)) {
                //$mh->from($adminUser->getUserEmail(),  t('Account Confirmation'));
                $message->fromAddress = $adminUser->getUserEmail();
                $message->fromName = t('Account Confirmation');
            }
        }

        $this->sendSetPasswordEmail($message);

        $this->setUserInfo($oUser);
    }

    /**
     Change current user password (and email address)
     */
    public function changePassword($password, $email = NULL) {
        if ($this->userInfo == NULL) throw new Exception('User must be logged in to change their password');

        $data = ['uPassword' => $password, 'uPasswordConfirm' => $password];

        if (!empty($email)) $data['uEmail'] = $email;


        $this->userInfo->update($data);

        if (!empty($email)) $this->userInfo->setAttribute('has_unknown_email', false);
        $this->userInfo->setAttribute('has_unknown_password', false);
    }

    public function setEmailAddress($email) {
        if ($this->userInfo == NULL) throw new Exception('User must be logged in to set their email address');

        if ($email == NULL) throw new Exception('Email address must be valid');

        $data = ['uEmail' => $email];

        $this->userInfo->update($data);

        $this->userInfo->setAttribute('has_unknown_email', false);
    }

    /**
     Get the URL where the user should go to set their password
     */
    protected function getChangePasswordUrl($uHash) {
        return BASE_URL . View::url('login', 'change_password', $uHash);
    }

    /**
     Send an email to the user asking them to set their password
     */
    protected function sendSetPasswordEmail(stdClass $message) {
        $mh = Loader::helper('mail');

        $mh->to($message->toAddress);
        $mh->addParameter('uName', $message->toName);
        $mh->addParameter('changePassURL', $message->changePassURL);
        $mh->from($message->fromAddress, $message->fromName);
        $mh->load('account_confirmation', 'social_api');

        @$mh->sendMail();
    }

}