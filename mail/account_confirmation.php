<?php
defined('C5_EXECUTE') or die("Access Denied.");

$subject = t("Account Confirmation");
$body = t("

Dear %s,

Please specify a password for your %s account

Your username is: %s

You may change your password at the following address:

%s

Thanks for browsing the site!

", $uName, SITE, $uName, $changePassURL);

?>