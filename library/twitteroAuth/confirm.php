<?php
session_start();

include 'EpiCurl.php';
include 'EpiOAuth.php';
include 'EpiTwitter.php';
include 'secret.php';

$twitterObj = new EpiTwitter($consumer_key, $consumer_secret);

$twitterObj->setToken($_GET['oauth_token']);
$token = $twitterObj->getAccessToken();
$twitterObj->setToken($token->oauth_token, $token->oauth_token_secret);

// save to cookies
$_SESSION['oauth_token'] = $token->oauth_token;
$_SESSION['oauth_token_secret'] = $token->oauth_token_secret;
//setcookie('oauth_token', $token->oauth_token, 0, '/');
//setcookie('oauth_token_secret', $token->oauth_token_secret, 0, '/');

$twitterInfo= $twitterObj->get_accountVerify_credentials();

?>

<script type="text/javascript">
    self.close();
</script>
