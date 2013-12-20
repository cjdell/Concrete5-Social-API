<?php

/**
 Provides a helper methods for interacting with the user's profile
 */
class ProfileService {

    /**
     Get a list of the support social network types
     */
    public function getSocialNetworkTypes() {
        return ['facebook', 'twitter'];
    }

    /**
     Disconnect the current user from specified social network (if possible)
     */
    public function disconnectSocialNetwork($type) {
        // TODO: Needs to check that the user has at least one valid method of signing in
    }

    /**
     Login with a native C5 account
     */
    public function signIn($email, $password) {
        if (empty($email)) throw new Exception('Email address invalid');

        if (empty($password)) throw new Exception('Password address invalid');

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

        $mh = Loader::helper('mail');

        if (USER_REGISTRATION_WITH_EMAIL_ADDRESS) {
            $mh->addParameter('uName', $oUser->getUserEmail());
        }
        else {
            $mh->addParameter('uName', $oUser->getUserName());
        }

        $mh->to($oUser->getUserEmail());

        // Generate hash that'll be used to authenticate user, allowing them to change their password
        $h = Loader::helper('validation/identifier');
        $uHash = $h->generate('UserValidationHashes', 'uHash');

        $db = Loader::db();
        $db->Execute("DELETE FROM UserValidationHashes WHERE uID=?", array( $oUser->uID ) );
        $db->Execute("insert into UserValidationHashes (uID, uHash, uDateGenerated, type) values (?, ?, ?, ?)", array($oUser->uID, $uHash, time(),intval(UVTYPE_CHANGE_PASSWORD)));

        $mh->addParameter('changePassURL', $this->getChangePasswordUrl($uHash));

        if (defined('EMAIL_ADDRESS_FORGOT_PASSWORD')) {
            $mh->from(EMAIL_ADDRESS_FORGOT_PASSWORD,  t('Account Confirmation'));
        }
        else {
            $adminUser = UserInfo::getByID(USER_SUPER_ID);
            if (is_object($adminUser)) {
                $mh->from($adminUser->getUserEmail(),  t('Account Confirmation'));
            }
        }

        $this->sendEmail($mh);
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
    protected function sendEmail($mh) {
        $mh->load('account_confirmation', 'social_api');
        @$mh->sendMail();
    }

}