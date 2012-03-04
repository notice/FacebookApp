<?php
include "fbapp.php";

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

$app = new SampleiApp('appid', 'secret');
$app->forward($_POST['signed_request']);
?>
