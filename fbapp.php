<?php
/*
 * Licensed to the Apache Software Foundation (ASF) under one or more
 * contributor license agreements.  See the NOTICE file distributed with
 * this work for additional information regarding copyright ownership.
 * The ASF licenses this file to You under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with
 * the License.  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class FacebookApp {
  // メンバー変数
  private $appid;  // アプリケーションID
  private $secret; // アプリケーションンの秘訣
  private $userid; // このアプリを利用するユーザーID
  private $admin;  // 利用ユーザーが管理者かどうか(true or false)
  private $user;   // このアプリを利用するユーザー情報（ID含む）
  private $page;   // アプリがインストールされたページ

  // URL定数
  private static $OAUTH_URL = 'https://www.facebook.com/dialog/oauth';
  private static $TOKEN_URL = 'https://graph.facebook.com/oauth/access_token';
  private static $ME_URL    = 'https://graph.facebook.com/me';

  protected static function base64_url_decode($input) {
    return base64_decode(strtr($input, '-_', '+/'));
  }

  protected function parse_signed_request($signed_request) {
    list($encoded_sig, $payload) = explode('.', $signed_request, 2);

    // decode the data
    $sig = $this->base64_url_decode($encoded_sig);
    $data = json_decode($this->base64_url_decode($payload), true);

    if (strtoupper($data['algorithm']) !== 'HMAC-SHA256') {
      error_log('Unknown algorithm. Expected HMAC-SHA256');
      return null;
    }

    // check sig
    $expected_sig = hash_hmac('sha256', $payload, $this->secret, $raw = true);
    if ($sig !== $expected_sig) {
      error_log('Bad Signed JSON signature!');
      return null;
    }
    return $data;
  }

  public function __construct($appid, $secret) {
    $this->appid  = $appid;
    $this->secret = $secret;
  }

  protected function forward_page() {
   echo('<html><body><p>not yet liked.</p></body></html>');
  }

  protected function forward_liked_page() {
   echo('<html><body><p>liked.</p></body></html>');
  }

  public function forward() {
    $data = $this->parse_signed_request($_REQUEST['signed_request']);
    if ($data['page']['liked']) {
      $this->forward_liked_page();
    } else {
      $this->forward_page();
    }
  }

  public function oauth($scope, $canvas) {
    // facebookからのコールかどうか。
    if (!empty($_REQUEST['signed_request'])) {
      $this->forward_failed_oauth('cannot be shown this page.');
    }
    if (!empty($_REQUEST['error'])) {
      // OAuth認証からの戻りでエラーがあった。
      $this->forward_failed_oauth($_REQUEST['error']);
    }
    if (!empty($_REQUEST['code'])) {
      // $codeがなければ、OAuth認証前なので、認証へ進める。
      $this->forward_oauth($scope, $canvas);
    }
    // このページの情報を取得する。
    list($this->userid, $this->page, $this->admin) = $this->get_pageinfo($_REQUEST['signed_request']);
    if (empty($this->userid)) {
      $this->forward_failed_oauth('failed request.');
    }
    // アクセストークンを取得する。
    list($token, $result) = $this->get_access_token($canvas, $_REQUEST['code']);
    if (empty($token)) {
      $this->forward_failed_oauth($result->error->message);
    }
    $this->user    = $this->get_user($token);
    $this->pages   = $this->get_pages($token);
    $this->nowpage = $this->get_page($this->pages, $_REQUEST['fb_page_id']);

    if (empty($nowpage->access_token)) {
      $this->forward_failed_oauth('can not get the access token of the page.');
    }
  }

  protected function get_access_token($canvas, $code) {
    $url = self::$TOKEN_URL .  '?client_id=' . $this->appid . '&redirect_uri=' . urlencode($canvas)
             . '&client_secret=' . $this->secret . "&code=" . $code;
    $response = file_get_contents($url);
    $params   = null;
    parse_str($response, $params);
    $token  = $params['access_token'];
    $result = null;
    if (empty($token)) {
      $result = json_decode($response);
    }
    return array($token, $result); 
  }

  protected function get_pageinfo($signed_request) {
    $data = $this->parse_signed_request($signed_request);
    if (!empty($data)) {
      return array($data['user_id'], $data['page']['id'], $data['page']['admin']);
    }
    return array();
  }

  protected function get_user($token) {
    $url = self::$ME_URL . '?access_token=' . $token;
    return json_decode(file_get_contents($url));
  }

  protected function get_pages($token) {
    $url = self::$ME_URL . '/accounts?access_token=' . $token;
    return json_decode(file_get_contents($url));
  }

  protected function get_page($pages, $pageid) {
    // 該当ページのアクセストークンを探す。
    if ($pages && $pages->{'data'}) {
      foreach ($pages->{'data'} as $p) {
        if ($pageid == $p->id) {
          return $p;
        }
      }
    }
    return null;
  }

  protected function forward_oauth($scope, $canvas) {
    $url = self::$OAUTH_URL . '?client_id=' . $this->appid . "&redirect_uri=" . urlencode($canvas)
              . '&scope=' . $scope;
    echo("<script> top.location.href='" . $url . "'</script><p>wait a minutes.</p>");
    exit();
  }

  protected function forward_failed_oauth($msg) {
    echo("<div>failed oauth.</div><div>" . $msg . "</div>");
    exit();
  }
}
?>
