<?php
include "fbapp.php";

$app = new FacebookApp('appid', 'secret');
$app->oauth('email,manage_pages,publish_stream,offline_access', 'http://fb.notice.co.jp/fw/');
?>
