<?php

class TwitterFeedBlockController extends BlockController
{
  var $pobj;

  protected $btDescription = "Twitter Feed";
  protected $btName = "twitter_feed";
  protected $btTable = 'btTwitterFeed';
  protected $btInterfaceWidth = "750";
  protected $btInterfaceHeight = "400";
  
  public function getBlockTypeName()
  {
    return t("Twitter Feed");
  }

  public function add()
  {
    return $this->edit();
  }

  public function edit()
  {

  }

  public function validate($args)
  {
      
  }

  public function save($args)
  {
    parent::save($args);
  }

  public function view()
  {
    $this->set('response', $this->getCachedFeed($this->screen_name, $this->max_results));
  }

  public function getCachedFeed($screen_name, $max_results)
  {
    $cacheKey = md5(serialize([$screen_name, $max_results]));
    $response = Cache::get('TwitterFeed', $cacheKey, time() - 300); // 5 minute cache max age

    if (!$response)
    {
      $response = $this->getFeed($screen_name, $max_results);
      Cache::set('TwitterFeed', $cacheKey, $response);
    }

    return $response;
  }

  public function getFeed($screen_name, $max_results)
  {
    Loader::library('social/twitter/twitteroauth',  'social_api');
    Loader::library('social/config/twconfig',       'social_api');

    $twitteroauth = new TwitterOAuth(YOUR_CONSUMER_KEY, YOUR_CONSUMER_SECRET, TWITTER_ACCESS_TOKEN, TWITTER_ACCESS_TOKEN_SECRET);
    $response = $twitteroauth->get('statuses/user_timeline', array('screen_name' => $screen_name, 'count' => $max_results));

    return $response;
  }

  public function getTextWithProcessedUrls($tweet, $trackerUrl = NULL)
  {
    $text = $tweet->text; //return $text;

    if ($tweet->entities->urls)
    {
      foreach ($tweet->entities->urls as $urlRef)
      {
        $linkUrl = 'http://' . $urlRef->display_url;
        //$linkUrl = $urlRef->url;

        if ($trackerUrl) $linkUrl = $trackerUrl . urlencode($linkUrl);

        $tag = '<a href="'.$linkUrl.'" rel="nofollow" target="_blank">'.$urlRef->display_url.'</a>';
        $text = str_replace($urlRef->url, $tag, $text);
      }
    }

    if ($tweet->entities->media)
    {
      foreach ($tweet->entities->media as $urlRef)
      {
        $linkUrl = 'http://' . $urlRef->display_url;
        //$linkUrl = $urlRef->url;

        //if ($trackerUrl) $linkUrl = $trackerUrl . urlencode($linkUrl);

        $tag = '<a href="'.$linkUrl.'" rel="nofollow" target="_blank">'.$urlRef->display_url.'</a>';
        $text = str_replace($urlRef->url, $tag, $text);
      }
    }

    return $text;
  }
}
