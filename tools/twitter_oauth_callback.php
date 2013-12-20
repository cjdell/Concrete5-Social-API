<?php

Loader::library('social/twitter/twitteroauth',  'social_api');
Loader::library('social/config/twconfig',       'social_api');
//Loader::library('user_service',                 'social_api');
Loader::library('social_service',                 'social_api');

$return_url = urldecode($_GET['return_url']);

if (!empty($_GET['oauth_verifier']) && !empty($_SESSION['oauth_token']) && !empty($_SESSION['oauth_token_secret'])) {
    // We've got everything we need
    $twitteroauth = new TwitterOAuth(YOUR_CONSUMER_KEY, YOUR_CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

    // Let's request the access token
    $access_token = $twitteroauth->getAccessToken($_GET['oauth_verifier']);

    // Save it in a session var
    $_SESSION['access_token'] = $access_token;

    // Let's get the user's info
    $user_info = $twitteroauth->get('account/verify_credentials');

    // Print user's info
    // echo '<pre>';
    // print_r($user_info);
    // echo '</pre><br/>';

    if (isset($user_info->error)) {
        // Something's wrong, go back to square 1
        //header('Location: login-twitter.php');
        echo 'login-twitter.php';
    }
    else {
        try {
            // Prepare the service request
            $req = new stdClass();

            $req->twitter_user_id = $user_info->id;
            $req->twitter_screen_name = $user_info->screen_name;
            $req->twitter_name = $user_info->name;
            $req->twitter_otoken = $access_token['oauth_token'];
            $req->twitter_otoken_secret = $access_token['oauth_token_secret'];

            // $us = new UserService();
            // $us->socialLogin($req);

            $socialService = SocialService::getService('twitter');
            $socialService->handleSignInResponse($req);

            header('Location: ' . $return_url);
        }
        catch (Exception $ex) {
            echo $ex->getMessage();
            //header('Location: /boom.php');
        }
    }
}
else {
    // Something's missing, go back to square 1
    //header('Location: login-twitter.php');
    echo 'login-twitter.php';
}

/*
stdClass Object
(
    [id] => 1953938401
    [id_str] => 1953938401
    [name] => mhoula_test
    [screen_name] => mhoula_test
    [location] =>
    [description] =>
    [url] =>
    [entities] => stdClass Object
        (
            [description] => stdClass Object
                (
                    [urls] => Array
                        (
                        )

                )

        )

    [protected] =>
    [followers_count] => 1
    [friends_count] => 4
    [listed_count] => 0
    [created_at] => Fri Oct 11 11:00:23 +0000 2013
    [favourites_count] => 0
    [utc_offset] =>
    [time_zone] =>
    [geo_enabled] =>
    [verified] =>
    [statuses_count] => 1
    [lang] => en
    [status] => stdClass Object
        (
            [created_at] => Fri Oct 11 11:04:43 +0000 2013
            [id] => 388621176567640064
            [id_str] => 388621176567640064
            [text] => http://t.co/41d1TugDyq is the best The Tech News Blog site http://t.co/RTFqSNXDWW via @onlinewebapp
            [source] => Tweet Button
            [truncated] =>
            [in_reply_to_status_id] =>
            [in_reply_to_status_id_str] =>
            [in_reply_to_user_id] =>
            [in_reply_to_user_id_str] =>
            [in_reply_to_screen_name] =>
            [geo] =>
            [coordinates] =>
            [place] =>
            [contributors] =>
            [retweet_count] => 0
            [favorite_count] => 0
            [entities] => stdClass Object
                (
                    [hashtags] => Array
                        (
                        )

                    [symbols] => Array
                        (
                        )

                    [urls] => Array
                        (
                            [0] => stdClass Object
                                (
                                    [url] => http://t.co/41d1TugDyq
                                    [expanded_url] => http://onlinewebapplication.com
                                    [display_url] => onlinewebapplication.com
                                    [indices] => Array
                                        (
                                            [0] => 0
                                            [1] => 22
                                        )

                                )

                            [1] => stdClass Object
                                (
                                    [url] => http://t.co/RTFqSNXDWW
                                    [expanded_url] => http://onlinewebapplication.com/
                                    [display_url] => onlinewebapplication.com
                                    [indices] => Array
                                        (
                                            [0] => 59
                                            [1] => 81
                                        )

                                )

                        )

                    [user_mentions] => Array
                        (
                            [0] => stdClass Object
                                (
                                    [screen_name] => onlinewebapp
                                    [name] => OnlineWebApplication
                                    [id] => 256362929
                                    [id_str] => 256362929
                                    [indices] => Array
                                        (
                                            [0] => 86
                                            [1] => 99
                                        )

                                )

                        )

                )

            [favorited] =>
            [retweeted] =>
            [possibly_sensitive] =>
            [lang] => en
        )

    [contributors_enabled] =>
    [is_translator] =>
    [profile_background_color] => C0DEED
    [profile_background_image_url] => http://abs.twimg.com/images/themes/theme1/bg.png
    [profile_background_image_url_https] => https://abs.twimg.com/images/themes/theme1/bg.png
    [profile_background_tile] =>
    [profile_image_url] => http://abs.twimg.com/sticky/default_profile_images/default_profile_2_normal.png
    [profile_image_url_https] => https://abs.twimg.com/sticky/default_profile_images/default_profile_2_normal.png
    [profile_link_color] => 0084B4
    [profile_sidebar_border_color] => C0DEED
    [profile_sidebar_fill_color] => DDEEF6
    [profile_text_color] => 333333
    [profile_use_background_image] => 1
    [default_profile] => 1
    [default_profile_image] => 1
    [following] =>
    [follow_request_sent] =>
    [notifications] =>
)
*/