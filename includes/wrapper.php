<?php
// VOXmail API Wrapper v1.18(20160204)
//
// Compatible with PHP4+ with HASH Cryptography extension (PHP >5.1.2)
// or the MHASH Cryptography extension.
//
// Uses PHP xmlrpc extension http://php.net/manual/en/book.xmlrpc.php
// Or the xmlrpc library at http://phpxmlrpc.sourceforge.net/
//
// VOXmail API Wrapper
// by Void Labs s.n.c <info@void.it>

// Copyright (c) 2008,2011 Void Labs s.n.c.
// All rights reserved.

//
// Redistribution and use in source and binary forms, with or without
// modification, are permitted provided that the following conditions
// are met:
//
//    * Redistributions of source code must retain the above copyright
//      notice, this list of conditions and the following disclaimer.
//
//    * Redistributions in binary form must reproduce the above
//      copyright notice, this list of conditions and the following
//      disclaimer in the documentation and/or other materials provided
//      with the distribution.
//
//    * Neither the name of the "XML-RPC for PHP" nor the names of its
//      contributors may be used to endorse or promote products derived
//      from this software without specific prior written permission.
//
// THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
// "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
// LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
// FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
// REGENTS OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
// INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
// (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
// SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
// HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
// STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
// ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
// OF THE POSSIBILITY OF SUCH DAMAGE. 

if (!class_exists('xmlrpc_client'))
  @include_once('xmlrpc.inc');

// Settings
if (!isset($GLOBALS['voxmail_wrapper_debug']))
  $GLOBALS['voxmail_wrapper_debug'] = 0;
if (!isset($GLOBALS['voxmail_wrapper_timeout']))
  $GLOBALS['voxmail_wrapper_timeout'] = 30;
if (!isset($GLOBALS['voxmail_wrapper_method']))
  $GLOBALS['voxmail_wrapper_method'] = "http11";
if (!isset($GLOBALS['voxmail_wrapper_url']))
  $GLOBALS['voxmail_wrapper_url'] = "services/xmlrpc";
if (!isset($GLOBALS['voxmail_wrapper_time_offset']))
  $GLOBALS['voxmail_wrapper_time_offset'] = 0;

if (!function_exists('voxmail_version')) {

// Constants
define('ENS_ERROR_WRONG_PARAMETER_COUNT', -32602);
define('ENS_ERROR_UNKNOWN', -99);
define('ENS_ERROR_INVALID_API_KEY', 11);
define('ENS_ERROR_TOKEN_EXPIRED', 12);
define('ENS_ERROR_TOKEN_PREVIOUSLY_USED', 13);
define('ENS_ERROR_UNSUPPORTED', 101);
define('ENS_ERROR_PERMISSION_DENIED', 102);
define('ENS_ERROR_INVALID_ARGUMENT', 103);
define('ENS_ERROR_NEWSLETTER_NOT_EXISTS', 201);
define('ENS_ERROR_NEWSLETTER_NOT_VALID', 202);
define('ENS_ERROR_NEWSLETTER_NOT_SENT', 203);
define('ENS_ERROR_NEWSLETTER_CANT_SEND', 204);
define('ENS_ERROR_NEWSLETTER_TOO_OLD', 205);
define('ENS_ERROR_USER_NOT_EXISTS', 301);
define('ENS_ERROR_USER_NOT_VALID', 302);
define('ENS_ERROR_USER_ID_NOT_FOUND', 303);
define('ENS_ERROR_AUDIENCE_NOT_EXISTS', 401);
define('ENS_ERROR_AUDIENCE_NOT_VALID', 402);
define('ENS_ERROR_INVALID_FROM', 502);


  function voxmail_version() {
    return 1018;
  }

  function voxmail_init($hostoruniquekey, $api_key = false, $secret = false) {
    global $voxmail_host, $voxmail_api_key, $voxmail_secret;
    if ($api_key == false && $secret == false) {
      $keyparts = explode('-', $hostoruniquekey);
      $voxmail_host = hex2bin($keyparts[0]);
      $voxmail_api_key = $keyparts[1];
      $voxmail_secret = $keyparts[2];
    } else {
      $voxmail_host = $hostoruniquekey;
      $voxmail_api_key = $api_key;
      $voxmail_secret = $secret;
    }
  }

  function voxmail_invoke($method) {
    global $voxmail_host, $voxmail_api_key, $voxmail_secret, $voxmail_last_result, $voxmail_last_method, $voxmail_last_args;
    $voxmail_last_result = 0;
    $timestamp = time() + $GLOBALS['voxmail_wrapper_time_offset'];
    $nonce = md5(mt_rand());
    $hash = function_exists('hash_hmac') ? 
      hash_hmac("sha256", $voxmail_api_key.';'.$timestamp.';'.$nonce.';'.$method, $voxmail_secret) :
      bin2hex(mhash(MHASH_SHA256, $voxmail_api_key.';'.$timestamp.';'.$nonce.';'.$method, $voxmail_secret));

    $args = func_get_args();
    array_shift($args);
    
    $voxmail_last_method = $method; 
    $voxmail_last_args = $args;

    $args = array_merge(array('API'.$voxmail_api_key, $timestamp, $nonce, $hash), $args);

    if (class_exists('xmlrpc_client')) {    

      $c = new xmlrpc_client($GLOBALS['voxmail_wrapper_url'], $voxmail_host, '', $GLOBALS['voxmail_wrapper_method']);
      // $c->setSSLVerifyPeer(0);
      $c->setDebug($GLOBALS['voxmail_wrapper_debug']);
      $c->return_type = "phpvals";
    
      foreach ($args as $k => $a)
        $args[$k] = php_xmlrpc_encode($a);
    
      $xmlrpc_internalencoding_save = isset($GLOBALS['xmlrpc_internalencoding']) ? $GLOBALS['xmlrpc_internalencoding'] : null;
      $GLOBALS['xmlrpc_internalencoding'] = 'UTF-8';
    
      $voxmail_last_result_dec = $c->send(new xmlrpcmsg($method ,$args), $GLOBALS['voxmail_wrapper_timeout']);
    
      if (is_null($xmlrpc_internalencoding_save))
        unset($GLOBALS['xmlrpc_internalencoding']);
      else
        $GLOBALS['xmlrpc_internalencoding'] = $xmlrpc_internalencoding_save;

      $voxmail_last_result = array(
        'faultCode' => $voxmail_last_result_dec->faultCode(),
        'faultString' => $voxmail_last_result_dec->faultString()
      );

      if (!$voxmail_last_result_dec->faultCode()) {
        $voxmail_last_result['value'] = $voxmail_last_result_dec->value();
      }

    } else if (function_exists('xmlrpc_encode_request')) {

      $request = xmlrpc_encode_request($method,$args,array(
        'encoding' => 'utf-8',
        'escaping' => 'markup',
        'verbosity' => 'no_white_space',
        'timeout' => $GLOBALS['voxmail_wrapper_timeout'],
      ));
      $context = stream_context_create(array('http' => array(
        'method' => 'POST',
        'header' => "Content-Type: text/xml; charset=UTF-8\r\n".
                    "User-Agent: phpxmlrpc-wrapper/".voxmail_version()."\r\n",
        'content' => $request
      )));

      $voxmail_last_result_raw = file_get_contents('http://'.$voxmail_host.'/'.$GLOBALS['voxmail_wrapper_url'], false, $context);
      $voxmail_last_result_dec = xmlrpc_decode($voxmail_last_result_raw);

      if (isset($voxmail_last_result_dec['faultCode'])) {
        $voxmail_last_result = $voxmail_last_result_dec;
      } else {
        $voxmail_last_result = array(
          'faultCode' => 0,
          'faultString' => '',
          'value' => $voxmail_last_result_dec
        );
      }

    } else {

      $voxmail_last_result = array(
        'faultCode' => ENS_ERROR_UNKNOWN,
        'faultString' => 'No XMLRPC library found. Please enable xmlrpc PHP extension or download xmlrpc.inc from http://phpxmlrpc.sourceforge.net/'
      );

    }
   
    return !$voxmail_last_result['faultCode'] ? $voxmail_last_result['value'] : false;
  }

  function voxmail_errorcode() {
    global $voxmail_last_result;
    return $voxmail_last_result === 0 ? 0 : $voxmail_last_result['faultCode'];
  }

  function voxmail_errormessage() {
    global $voxmail_last_result;
    return $voxmail_last_result === 0 ? '' : $voxmail_last_result['faultString'];
  }

  function voxmail_last_method() {
    global $voxmail_last_method;
    return $voxmail_last_method;
  }

  function voxmail_last_method_args() {
    global $voxmail_last_args;
    return $voxmail_last_args;
  }

}
function voxmail_info() {
  return voxmail_invoke('voxmail.info');
}

function voxmail_newsletter_load($nid, $fields = array ()) {
  return voxmail_invoke('voxmail.newsletter.load', $nid, $fields);
}

function voxmail_newsletter_create($data) {
  return voxmail_invoke('voxmail.newsletter.create', $data);
}

function voxmail_newsletter_update($nid, $data) {
  return voxmail_invoke('voxmail.newsletter.update', $nid, $data);
}

function voxmail_newsletter_delete($nid) {
  return voxmail_invoke('voxmail.newsletter.delete', $nid);
}

function voxmail_newsletter_duplicate($nid) {
  return voxmail_invoke('voxmail.newsletter.duplicate', $nid);
}

function voxmail_newsletter_check($nid) {
  return voxmail_invoke('voxmail.newsletter.check', $nid);
}

function voxmail_newsletter_send($nid, $timestamp = 0) {
  return voxmail_invoke('voxmail.newsletter.send', $nid, $timestamp);
}

function voxmail_newsletter_csend($data, $timestamp = 0) {
  return voxmail_invoke('voxmail.newsletter.csend', $data, $timestamp);
}

function voxmail_newsletter_send_test($nid, $to = false) {
  return voxmail_invoke('voxmail.newsletter.send_test', $nid, $to);
}

function voxmail_newsletter_results($nid, $section = '') {
  return voxmail_invoke('voxmail.newsletter.results', $nid, $section);
}

function voxmail_newsletter_results_users($nid, $filters = array (), $order = '', $pageLength = 0, $pageNo = 0) {
  return voxmail_invoke('voxmail.newsletter.results_users', $nid, $filters, $order, $pageLength, $pageNo);
}

function voxmail_newsletter_list($filters = array (), $order = '', $pageLength = 0, $pageNo = 0) {
  return voxmail_invoke('voxmail.newsletter.list', $filters, $order, $pageLength, $pageNo);
}

function voxmail_user_load($uid_mail, $fields = array ()) {
  return voxmail_invoke('voxmail.user.load', $uid_mail, $fields);
}

function voxmail_user_login($uid_mail, $pass, $fields = array ()) {
  return voxmail_invoke('voxmail.user.login', $uid_mail, $pass, $fields);
}

function voxmail_user_subscribe($data, $ip = false) {
  return voxmail_invoke('voxmail.user.subscribe', $data, $ip);
}

function voxmail_user_unsubscribe($uid_mail, $ip = false) {
  return voxmail_invoke('voxmail.user.unsubscribe', $uid_mail, $ip);
}

function voxmail_user_disable_mail($uid_mail, $type = 'admin', $ip = '') {
  return voxmail_invoke('voxmail.user.disable_mail', $uid_mail, $type, $ip);
}

function voxmail_user_enable_mail($uid_mail, $ip = '') {
  return voxmail_invoke('voxmail.user.enable_mail', $uid_mail, $ip);
}

function voxmail_user_create($data) {
  return voxmail_invoke('voxmail.user.create', $data);
}

function voxmail_user_update($uid_mail, $data, $create_if_not_exists = false) {
  return voxmail_invoke('voxmail.user.update', $uid_mail, $data, $create_if_not_exists);
}

function voxmail_user_erase($uid_mail) {
  return voxmail_invoke('voxmail.user.erase', $uid_mail);
}

function voxmail_user_list($filters = array (), $order = '', $pageLength = 0, $pageNo = 0) {
  return voxmail_invoke('voxmail.user.list', $filters, $order, $pageLength, $pageNo);
}

function voxmail_user_count($filters = array ()) {
  return voxmail_invoke('voxmail.user.count', $filters);
}

function voxmail_user_profile_fields_list() {
  return voxmail_invoke('voxmail.user.profile_fields.list');
}

function voxmail_audience_reset($aidlist) {
  return voxmail_invoke('voxmail.audience.reset', $aidlist);
}

function voxmail_audience_list($filters = array ()) {
  return voxmail_invoke('voxmail.audience.list', $filters);
}

function voxmail_audience_create($data) {
  return voxmail_invoke('voxmail.audience.create', $data);
}

function voxmail_audience_delete($aid) {
  return voxmail_invoke('voxmail.audience.delete', $aid);
}

