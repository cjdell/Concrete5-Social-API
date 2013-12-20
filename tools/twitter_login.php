<?php

Loader::library('social/twitter/twitteroauth',  'social_api');
Loader::library('social/config/twconfig',       'social_api');
$uh = Loader::helper('concrete/urls');

$return_url = urldecode($_GET['return_url']);

// Default back to home page if not specified
if (!$return_url) $return_url = '/';

$oauth_callback_url = 'http://' . $_SERVER['SERVER_NAME'] . $uh->getToolsURL('twitter_oauth_callback', 'social_api') . '?return_url=' . urlencode($return_url);

$twitteroauth = new TwitterOAuth(YOUR_CONSUMER_KEY, YOUR_CONSUMER_SECRET);

// Requesting authentication tokens, the parameter is the URL we will be redirected to
$request_token = $twitteroauth->getRequestToken($oauth_callback_url);

// Saving them into the session
$_SESSION['oauth_token'] = $request_token['oauth_token'];
$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

// If everything goes well..
if ($twitteroauth->http_code == 200) {
    // Let's generate the URL and redirect
    $url = $twitteroauth->getAuthorizeURL($request_token['oauth_token']);

    header('Location: ' . $url);
    //echo $url;
}
else {
    var_dump($twitteroauth);

    // It's a bad idea to kill the script, but we've got to know when there's an error.
    die('Something wrong happened.');
}