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

  // 定数
  private static $FB_PAGE_ID = 'fb_page_id';
  private static $OAUTH_URL  = 'https://www.facebook.com/dialog/oauth';
  private static $TOKEN_URL  = 'https://graph.facebook.com/oauth/access_token';
  private static $ME_URL     = 'https://graph.facebook.com/me';

  // getters
  public function isadmin()  { return $this->admin; }
  public function get_user() { return $this->user;  }
  public function get_page() { return $this->page;  }

  // utils.
  protected static function base64_url_decode($input) {
    return base64_decode(strtr($input, '-_', '+/'));
  }

  protected static function canvas_url($canvas, $page) {
    if (!empty($page)) {
      $canvas .= '?' . self::$FB_PAGE_ID . '=' . $page;
    }
    return urlencode($canvas);
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
    if (!empty($_REQUEST['signed_request'])) {
      // facebookからのファーストコールのとき
      // このページの情報を取得する。
      list($this->userid, $this->page, $this->admin) = $this->get_page_info($_REQUEST['signed_request']);
      if (empty($this->page)) {
        $this->forward_failed_oauth('get_page_info: failed request.');
      }
    }
    if ($this->page['liked']) {
      $this->forward_liked_page();
    } else {
      $this->forward_page();
    }
  }

  public function oauth($scope, $canvas) {
    if (!empty($_REQUEST['error'])) {
      // OAuth認証からの戻りでエラーがあった。
      // e.g.
      // $_REQUEST['error'] = 'access_deied';
      // $_REQUEST['error_reason'] = 'user_denied'
      // $_REQUEST['error_description'] = 'The user denied your request.'
      $this->forward_failed_oauth($_REQUEST['error']);
    }
    // request parameters.
    $code       = $_REQUEST['code'];
    $fb_page_id = $_REQUEST[self::$FB_PAGE_ID];

    if (empty($code)) {
      // $codeがなければ、OAuth認証へ進める。
      $this->forward_oauth($scope, $canvas, $fb_page_id);
    }
    // アクセストークンを取得する。
    list($token, $result) = $this->get_access_token($canvas, $fb_page_id, $code);
    if (empty($token)) {
      $this->forward_failed_oauth('access_token: ' . $result->error->message);
    }
    $this->user  = $this->get_user_info($token);
    if (!empty($fb_page_id)) {
      $this->pages = $this->get_pages_info($token);
      $this->page  = $this->get_target_page_info($this->pages, $fb_page_id);
    }
  }

  protected function deauth_page($page) {
    // virtual function.
  }

  protected function deauth_user($user) {
    // virtual function.
  }

  public function deauth() {
    $data = $this->parse_signed_request($_REQUEST['signed_request']);
    if ($data['profile_id']) {
      deauth_page($data['profile_id']);
    }
    if ($data['user']) {
      deauth_page($data['user']);
    }
  }

  protected function get_access_token($canvas, $page, $code) {
    $url = self::$TOKEN_URL . '?client_id=' . $this->appid
         . '&redirect_uri=' . $this->canvas_url($canvas, $page) . '&client_secret=' . $this->secret . "&code=" . $code;
    $response = file_get_contents($url);
    $params   = null;
    parse_str($response, $params);
    $token  = $params['access_token'];
    $result = null;
    if (empty($token)) {
/* e.g.
      {
        "error": {
        "type": "OAuthException",
          "message": "Error validating verification code."
        }
      }
*/
      $result = json_decode($response);
    }
    return array($token, $result);
  }

  protected function get_page_info($signed_request) {
    $data = $this->parse_signed_request($signed_request);
    if (!empty($data)) {
      return array($data['user'], $data['page']['id'], $data['page']['admin']);
    }
    return array();
  }

  protected function get_user_info($token) {
    $url = self::$ME_URL . '?access_token=' . $token;
    return json_decode(file_get_contents($url));
  }

  protected function get_pages_info($token) {
    $url = self::$ME_URL . '/accounts?access_token=' . $token;
    return json_decode(file_get_contents($url));
  }

  protected function get_target_page_info($pages, $pageid) {
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

  protected function forward_oauth($scope, $canvas, $page) {
    $url = self::$OAUTH_URL . '?client_id=' . $this->appid . "&redirect_uri=" . $this->canvas_url($canvas, $page) . '&scope=' . $scope;
    echo("<script> top.location.href='" . $url . "'</script><p>wait a minutes.</p>");
    exit();
  }

  protected function forward_failed_oauth($msg) {
    echo("<div>failed oauth.</div><div>" . $msg . "</div>");
    exit();
  }
}
?>
