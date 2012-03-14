<?php
include "fbapp.php";
/*
  「いいね！」前と「いいね！」後のコンテンツを切り替えるアプリです。
*/
class SampleApp extends FacebookApp {
  public function forward_page() {
    // welcome.incの内容はご用意ください。
    echo file_get_contents('./welcome.inc');
  }

  public function forward_liked_page() {
    // like-welcome.incの内容はご用意ください。
    echo file_get_contents('./like-welcome.inc');
  }
}

$app = new SampleApp('your_appid', 'your_app_secret');
$app->forward();
?>
