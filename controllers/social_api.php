<?php

defined('C5_EXECUTE') or die(_("Access Denied."));

/**
 Serves as a sample controller for building an API
 */
class SocialApiController extends Controller {

    public function view() {

    }

    /**
     Post a message to a social network
     */
    public function post_message($socialType, $message) {
        header('Content-Type: application/json');

        Loader::library('social_service', 'social_api');

        $uh = Loader::helper('concrete/urls');

        $response = new stdClass();
        $response->success = false;

        try {
            $socialService = SocialService::getService($socialType);
            $socialService->postMessage($message);

            $response->success  = true;
            $response->message  = 'Success';
        }
        catch (UserNotSignedInException $ex) {
            $response->action   = 'redirect';
            $response->message  = $ex->getMessage();
            $response->redirect = $uh->getToolsURL($socialType . '_login', 'social_api');
        }
        catch (SocialIntegrationRequiredException $ex) {
            $response->action   = 'redirect';
            $response->message  = $ex->getMessage();
            $response->redirect = $uh->getToolsURL($socialType . '_login', 'social_api');
        }
        catch (Exception $ex) {
            $response->message = $ex->getMessage();
        }

        echo json_encode($response);

        exit();
    }

    /**
     Sign up
     */
    public function sign_up() {
        header('Content-Type: application/json');

        Loader::library('profile_service', 'social_api');

        $email = $_POST['email'];

        $response = new stdClass();
        $response->success = false;

        try {
            $profileService = new ProfileService();
            $profileService->signUp($email);

            $response->success  = true;
            $response->message  = 'Success';
        }
        catch (Exception $ex) {
            $response->message  = $ex->getMessage();
        }

        echo json_encode($response);

        exit();
    }

    /**
     Sign in
     */
    public function sign_in() {
        header('Content-Type: application/json');

        Loader::library('profile_service', 'social_api');

        $email      = $_POST['email'];
        $password   = $_POST['password'];

        $response = new stdClass();
        $response->success = false;

        try {
            $profileService = new ProfileService();
            $profileService->signIn($email, $password);

            $response->success  = true;
            $response->message  = 'Success';
        }
        catch (Exception $ex) {
            $response->message  = $ex->getMessage();
        }

        echo json_encode($response);

        exit();
    }

    /**
     Change current user password (and email address)
     */
     public function change_password() {
        header('Content-Type: application/json');

        Loader::library('profile_service', 'social_api');

        $password   = $_POST['password'];
        $email      = array_key_exists('email', $_POST) ? $_POST['email'] : NULL;

        $response = new stdClass();
        $response->success = false;

        try {
            $profileService = new ProfileService($password, $email);
            $profileService->changePassword($type);

            $response->success  = true;
            $response->message  = 'Success';
        }
        catch (Exception $ex) {
            $response->message  = $ex->getMessage();
        }

        echo json_encode($response);

        exit();
    }

    /**
     Disconnect network
     */
     public function disconnect_social_network($type) {
        header('Content-Type: application/json');

        Loader::library('profile_service', 'social_api');

        $response = new stdClass();
        $response->success = false;

        try {
            $profileService = new ProfileService();
            $profileService->disconnectSocialNetwork($type);

            $response->success  = true;
            $response->message  = 'Success';
        }
        catch (Exception $ex) {
            $response->message  = $ex->getMessage();
        }

        echo json_encode($response);

        exit();
    }
}