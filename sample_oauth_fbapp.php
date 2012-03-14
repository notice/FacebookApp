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
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<title>Facebookアプリ</title>
</head>
<body>
<table>
<tr><th>user info.</th><td><?php echo(json_encode($app->get_user())); ?></td></tr>
<tr><th>page info.</th><td><?php echo(json_encode($app->get_page())); ?></td></tr>
</table>
</body>
</html>
