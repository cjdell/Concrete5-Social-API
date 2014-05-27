<?php

Loader::library('profile_service', 'social_api');
Loader::library('social_service', 'social_api');

$profileService = new ProfileService();
$types = $profileService->getSocialNetworkTypes();

?>

<ul>
    <?php foreach ($types as $type): ?>
    <?php $service = SocialService::getService($type) ?>
    <li>
        <a href="<?php echo $service->getLoginUrl() ?>">Sign in with <?php echo $service->getNetworkName() ?></a>
    </li>
    <?php endforeach ?>
</ul>

<ul>
    <?php foreach ($types as $type): ?>
    <?php $service = SocialService::getService($type) ?>
    <li>
        <p>Connected to <?php echo $service->getNetworkName() ?>: <strong><?php echo $service->hasIntegration() ? 'Yes' : 'No' ?></strong></p>
    </li>
    <?php endforeach ?>
</ul>

<ul>
    <?php foreach ($types as $type): ?>
    <?php $service = SocialService::getService($type) ?>
    <li>
        <a href="/social_api/post_message/<?php echo $type ?>/HelloWorld">Post message to <?php echo $service->getNetworkName() ?></a>
    </li>
    <?php endforeach ?>
</ul>
