FacebookApp
======================
Facebookアプリを開発するためのフレームワークです。
このアプリで実装するページの「いいね！」されていないときと「いいね！」されたときのページを自動的に切り替えます。
認証が必要なアプリは、自動的に認証ダイアログへ遷移し、認証されると指定されたURLへフォワードします。

使い方
------

### サンプルコード(sample_fbapp.php) ###
    <?php
    include "fbapp.ini";
    include "fbapp.php";

    /*
     「いいね！」前と「いいね！」後のコンテンツを切り替えるアプリです。
    */

    class SampleApp extends FacebookApp {
      public function forward_page() {
        // includeでも可
        echo file_get_contents('./welcome.inc');
      }

      public function forward_liked_page() {
        // includeでも可
        echo file_get_contents('./like-welcome.inc');
      }
    }

    $app = new FacebookApp($APP_ID, $SECRET);
    $app->forward();
    ?>

### サンプルコード(sample\_oauth\_fbapp.php) ###
    <?php
    include "fbapp.ini";
    include "fbapp.php";

    /*
      OAuth認証するアプリです。
      このアプリをインストールしたユーザ情報が取得できます。
      scopeにmanage_pagesを指定してユーザが承認すれば、インストールしたページのアクセストークンが取得できます。
    */

    $scope  = 'email,manage_pages,publish_stream,offline_access';
    $canvas = 'http://apps.facebook.com/notice_fbapp/sample_oauth_fbapp.php';
    $app    = new FacebookApp($APP_ID, $SECRET);
    $app->oauth($scope, $canvas);
    ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml" lang="ja" xml:lang="ja" dir="ltr">
    <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Style-Type" content="text/css" />
    <title>Facebookアプリ</title>
    </head>
    <body>
    <table>
    <tr><th>user info.</th><td><?php echo(json_encode($app->get_user())); ?></td></tr>
    <tr><th>page info.</th><td><?php echo(json_encode($app->get_page())); ?></td></tr>
    </table>
    </body>
    </html>

基本クラス
------
FacebookApp

コンストラクタ
----------------
FacebookApp($appid, $secret)

FacebookAppクラスのインスタンスを初期化します。

パラメータの解説
----------------

+   `appid` :
   アプリケーションID

+   `secret` :
   アプリケーションの秘訣

OAUTH認証
----------------
oauth($scope, $canvas)

OAUTH認証ダイアログを呼び出して、必要ならユーザーから認証を求める。認証が得られたら、指定されたURLへリダイレクトする。

パラメータの解説
----------------
+    `scope`
     ユーザーから得たい権限

+    'canvas'
     認証後のリダイレクト先(アプリのネームスペース下:https://apps.facebook.com/app-namespace/)

ライセンス
----------
Copyright &copy; 2011 notice,inc.
Licensed under the [Apache License, Version 2.0][Apache]

[Apache]: http://www.apache.org/licenses/LICENSE-2.0



