<?php

// Added this check for when the user cancels Facebook account access and gets redirected back with a failure code
// This check prevents a redirect loop (which Facebook disapprove of)
if ($_GET['error_reason'] == 'user_denied') {
    // TODO: Redirect somewhere more sensible than the homepage
    header("Location: " . '/?error=integration_denied');
    exit();
}

Loader::library('social/facebook/facebook', 'social_api');
Loader::library('social/config/fbconfig',   'social_api');
Loader::library('social_service',           'social_api');

$return_url = urldecode($_GET['return_url']);

// Default back to home page if not specified
if (!$return_url) $return_url = '/';

$facebook = new Facebook(['appId' => APP_ID, 'secret' => APP_SECRET]);

$user = $facebook->getUser();

print_r($user);

if ($user) {
    try {
        // Proceed knowing you have a logged in user who's authenticated.
        $user_profile = $facebook->api('/me');
    }
    catch (FacebookApiException $e) {
        error_log($e);
        $user = null;
    }

    if (!empty($user_profile)) {
        # User info ok? Let's print it (Here we will be adding the login and registering routines)

        try {
            // Prepare the service request
            $req = new stdClass();

            $req->facebook_user_id = $user_profile['id'];
            $req->facebook_user_name = $user_profile['username'];
            $req->facebook_name = $user_profile['name'];
            $req->facebook_email = $user_profile['email'];

            $socialService = SocialService::getService('facebook');
            $socialService->handleSignInResponse($req);

            header('Location: ' . $return_url);
        }
        catch (Exception $ex) {
            echo $ex->getMessage();
            //header('Location: /boom.php');
        }
    }
    else {
        # For testing purposes, if there was an error, let's kill the script
        die("There was an error.");
    }
}
else {
    # There's no active session, let's generate one
    $login_url = $facebook->getLoginUrl(array( 'scope' => ['email', 'publish_stream']));
    //echo $login_url;
    header("Location: " . $login_url);
}
