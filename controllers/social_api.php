<?php

defined('C5_EXECUTE') or die(_("Access Denied."));

/**
 Serves as a sample controller for building an API
 */
class SocialApiController extends Controller {

    public function view() {
        exit();
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
}