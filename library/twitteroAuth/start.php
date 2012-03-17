<?php
session_start();

include 'EpiCurl.php';
include 'EpiOAuth.php';
include 'EpiTwitter.php';
include 'secret.php';

$twitterObj = new EpiTwitter($consumer_key, $consumer_secret);
//echo ($twitterObj->getAuthorizationUrl());
if(isset($_SESSION['oauth_token'])
    && $_SESSION['oauth_token']
    && isset($_SESSION['oauth_token_secret'])
    && $_SESSION['oauth_token_secret']){
         ?>
<script type="text/javascript">
    self.close();
</script>
    <?php
}else{
    header('Location: '.$twitterObj->getAuthorizationUrl());
}
?>

