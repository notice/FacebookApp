FacebookApp
======================
Facebookアプリを開発するためのフレームワークです。
このアプリで実装するページの「いいね！」されていないときと「いいね！」されたときのページを自動的に切り替えます。

使い方
------

### サンプルコード(sample_fbapp.php) ###
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

    $app = new SampleApp('appid', 'secret');
    $app->forward($_POST['signed_request']);
    ?>

パラメータの解説
----------------

    SampleApp(appid, secert)

+   `appid` :  
   アプリケーションID 

+   `secret` :  
   アプリケーションの秘訣 

ライセンス
----------
Copyright &copy; 2011 notice,inc.  
Licensed under the [Apache License, Version 2.0][Apache]  

[Apache]: http://www.apache.org/licenses/LICENSE-2.0

