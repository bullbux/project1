<?php
include 'EpiCurl.php';
include 'EpiOAuth.php';
include 'EpiTwitter.php';
include 'secret.php';

$twitterObj = new EpiTwitter($consumer_key, $consumer_secret, $_COOKIE['oauth_token'], $_COOKIE['oauth_token_secret']);

$twitterInfo= $twitterObj->post_statusesUpdate(array('status'=>'hello fffs'));
foreach($twitterInfo as $value) {
  echo $value;
}
?>
