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
  var $appId;
  var $appSecret;

  protected function base64_url_decode($input) {
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
    $expected_sig = hash_hmac('sha256', $payload, $this->appSecret, $raw = true);
    if ($sig !== $expected_sig) {
      error_log('Bad Signed JSON signature!');
      return null;
    }
    return $data;
  }

  public function __construct($appId, $appSecret) {
    $this->appId     = $appId;
    $this->appSecret = $appSecret;
  }

  protected function forward_page() {
   echo('<html><body><p>not yet liked.</body></html>');
  }

  protected function forward_liked_page() {
   echo('<html><body><p>liked.</body></html>');
  }

  public function forward($signed_request) {
    $data = $this->parse_signed_request($signed_request);

    if ($data['page']['liked']) {
      $this->forward_liked_page();
    } else {
      $this->forward_page();
    }
  }
}
?>
