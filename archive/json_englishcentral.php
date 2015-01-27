<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Internal library of functions for module English Central
 *
 * All the englishcentral specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_englishcentral
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

//require oauthlib for englishcentral
require_once(dirname(__FILE__).'/OAuth.php');



/**
 * Authentication class to access Quizlet API
 * originally extended oauth_helper, but it was not so helpful
 * quizlet oauth works differently to facebook/google in some ways
 * i)The initial request url for an oauth_token can be made very simply
 * ii) quizlet uses http basic auth in request for access_token, facebook etc doesnt
 * iii) quizlet uses access token and username in data requests, facebook etc use access_token and secrets
 * 
 *
 * @package    englishcentral
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class englishcentral {

  /* Contains the last HTTP status code returned. */
  public $http_code;
  /* Contains the last API call. */
  public $url;
  /* Set up the API root URL. */
  public $host = "http://www.englishcentral.com/platform/";
  /* Set timeout default. */
  public $timeout = 30;
  /* Set connect timeout. */
  public $connecttimeout = 30; 
  /* Verify SSL Cert. */
  public $ssl_verifypeer = FALSE;
  /* Respons format. */
  public $format = 'json';
  /* Decode returned json data. */
  public $decode_json = TRUE;
  /* Contains the last HTTP headers returned. */
  public $http_info;
  /* Set the useragnet. */
  public $useragent = 'englishcentral oauth v1.0';
  /* Immediately retry the API call if the response was not successful. */
  //public $retry = TRUE;




  /**
   * Set API URLS
   */
  function accessTokenURL()  { return 'http://bridge.englishcentral.com/oauth/access_token'; }
  //  function accessTokenURL()  { return 'http://bridge.englishcentral.com/rest/oauth/login'; }
  //function accessTokenURL() { return 'http://bridge.englishcentral.com/oauth/authenticate'; }
  function authenticateURL() { return 'http://bridge.englishcentral.com/oauth/authenticate'; }
  function authorizeURL()    { return 'http://bridge.englishcentral.com/oauth/authorize'; }
  function requestTokenURL()  { return 'http://bridge.englishcentral.com/platform/rest/oauth/login'; }

  /**
   * Debug helpers
   */
  function lastStatusCode() { return $this->http_status; }
  function lastAPICall() { return $this->last_api_call; }

  /**
   * construct English Central object
   */
  function __construct($oauth_token=null,$oauth_token_secret=null) {
  	global $CFG;
  	
  	$config = get_config('englishcentral');
  	$this->consumer_key = $config->consumerkey;
    $this->consumer_secret = $config->consumersecret;
    $this->oauth_callback = new moodle_url($CFG->wwwroot. '/admin/oauth2callback.php');
            
    $this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
    $this->consumer = new OAuthConsumer($this->consumer_key, $this->consumer_secret);
    if (!empty($oauth_token) && !empty($oauth_token_secret)) {
      $this->token = new OAuthConsumer($oauth_token, $oauth_token_secret);
    } else {
      $this->token = NULL;
    }
  }


  /**
   * Get a request_token from EnglishCentral
   *
   * @returns a key/value array containing oauth_token and oauth_token_secret
   */
  function getRequestToken($oauth_callback = NULL) {
    $parameters = array();
    if ($oauth_callback != NULL ) {
        $parameters['oauth_callback'] = $this->oauth_callback;
    }
	$paramaters['applicationBuildDate']='2014-09-26T17:23:54.486Z';
	$paramaters['applicationName']='Moodle_EC_Integration';
	$paramaters['siteLanguage']='en';
	
    $request = $this->oAuthRequest($this->requestTokenURL(), 'POST', $parameters);
	if($request){
		$token_array = $this->parseReturn($request, 'request');	
		$this->token = new OAuthConsumer($token_array['token'], $token_array['token_secret']);
		return $token_array['token_secret'];
	}else{
		return false;
	}
  }

  function parseReturn($jsonstring, $type){
		$ret = array();
		$parsed_element =  json_decode($jsonstring);//simplexml_load_string($xmlstring);
		//should really do a better check, but the xpath did not work
		//if($parsed_element && $parsed_element->xpath('/requestToken/token')){
		if($parsed_element){
			switch($type){
				case 'request':
					//$token = $parsed_element->requestToken->token;
					//$token_secret = $parsed_element->requestToken->token_secret;
					$token = $parsed_element->verifier;
					$token_secret = $json_string;
					break;
				case 'access':
					$token = $json_string;//$parsed_element->accessToken->token;
					$token_secret = $json_string;
					break;
				case 'authenticate':
					$token = $json_string;//$parsed_element->authenticate->token;
					$token_secret = $json_string;
					break;
			}
			$stringtoken = (string)$token;
			$stringtoken_secret = (string)$token_secret;
			$ret = array();
			$ret['token'] = (string)$token;
			$ret['token_secret'] = (string)$token_secret;
		}else{
			$ret['token'] = 'false';
			$ret['token_secret'] = 'false';
		}
		return $ret;
  }
  /**
   * Get the authorize URL
   *
   * @returns a string
   */
  function getAuthorizeURL($token,$ec_user) {
       return $this->authenticateURL() . "?oauth_token={$token}&user={$ec_user}";
  }

  /**
   * Exchange request token and secret for an access token and
   * secret, to sign API calls.
   *
   * @returns array("oauth_token" => "the-access-token",
   *                "oauth_token_secret" => "the-access-secret",
   *                "user_id" => "9436992",
   *                "screen_name" => "abraham")
   */
  function xgetAccessToken($oauth_verifier) {
    $parameters = array();
    $parameters['oauth_verifier'] = $oauth_verifier;
    $request = $this->oAuthRequest($this->accessTokenURL(), 'GET', $parameters);
    $token = OAuthUtil::parse_parameters($request);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }
  
    /**
   * Exchange request token and secret for an access token and
   * secret, to sign API calls.
   *
   * @returns array("oauth_token" => "the-access-token",
   *                "oauth_token_secret" => "the-access-secret",
   *                "user_id" => "9436992",
   *                "screen_name" => "abraham")
   */
  function getAccessToken($req_token, $ec_user) {
    $parameters = array();
    $parameters['user'] = $ec_user;
	$parameters['oauth_token'] = $req_token;
    $url = $this->getAuthorizeURL($req_token, $ec_user);
    
	//$request = $this->http($url, 'GET');
    $request = $this->oAuthRequest($this->accessTokenURL(), 'GET', $parameters);
	if($request){
		$token_array = $this->parseReturn($request, 'authenticate');
		return $token_array['token'];
	}else{
		return 'false';
	}
  }

  /**
   * One time exchange of username and password for access token and secret.
   *
   * @returns array("oauth_token" => "the-access-token",
   *                "oauth_token_secret" => "the-access-secret",
   *                "user_id" => "9436992",
   *                "screen_name" => "abraham",
   *                "x_auth_expires" => "0")
   */  
  function getXAuthToken($username, $password) {
    $parameters = array();
    $parameters['x_auth_username'] = $username;
    $parameters['x_auth_password'] = $password;
    $parameters['x_auth_mode'] = 'client_auth';
    $request = $this->oAuthRequest($this->accessTokenURL(), 'POST', $parameters);
    $token = OAuthUtil::parse_parameters($request);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }

  /**
   * GET wrapper for oAuthRequest.
   */
  function get($url, $parameters = array()) {
    $response = $this->oAuthRequest($url, 'GET', $parameters);
    if ($this->format === 'json' && $this->decode_json) {
      return json_decode($response);
    }
    return $response;
  }
  
  /**
   * POST wrapper for oAuthRequest.
   */
  function post($url, $parameters = array()) {
    $response = $this->oAuthRequest($url, 'POST', $parameters);
    if ($this->format === 'json' && $this->decode_json) {
      return json_decode($response);
    }
    return $response;
  }

  /**
   * DELETE wrapper for oAuthReqeust.
   */
  function delete($url, $parameters = array()) {
    $response = $this->oAuthRequest($url, 'DELETE', $parameters);
    if ($this->format === 'json' && $this->decode_json) {
      return json_decode($response);
    }
    return $response;
  }

  /**
   * Format and sign an OAuth / API request
   */
  function oAuthRequest($url, $method, $parameters) {
    if (strrpos($url, 'https://') !== 0 && strrpos($url, 'http://') !== 0) {
      $url = "{$this->host}{$url}.{$this->format}";
    }
    $request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $parameters);
    $request->sign_request($this->sha1_method, $this->consumer, $this->token);
   echo 'blahblah:' . $request->to_url();
    switch ($method) {
    case 'GET':
      return $this->http($request->to_url(), 'GET');
    default:
      return $this->http($request->get_normalized_http_url(), $method, $request->to_postdata());
    }
    
  }

  /**
   * Make an HTTP request
   *
   * @return API results
   */
  function http($url, $method, $postfields = NULL) {
    $this->http_info = array();
    $ci = curl_init();
    /* Curl settings */
    curl_setopt($ci, CURLOPT_USERAGENT, $this->useragent);
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
    curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ci, CURLOPT_HTTPHEADER, array('Expect:'));
    curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
    curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
    curl_setopt($ci, CURLOPT_HEADER, FALSE);

    switch ($method) {
      case 'POST':
        curl_setopt($ci, CURLOPT_POST, TRUE);
        if (!empty($postfields)) {
          curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
        }
        break;
      case 'DELETE':
        curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
        if (!empty($postfields)) {
          $url = "{$url}?{$postfields}";
        }
    }

    curl_setopt($ci, CURLOPT_URL, $url);
    $response = curl_exec($ci);
    $this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
    $this->http_info = array_merge($this->http_info, curl_getinfo($ci));
    $this->url = $url;
    curl_close ($ci);
    return $response;
  }

  /**
   * Get the header info to store.
   */
  function getHeader($ch, $header) {
    $i = strpos($header, ':');
    if (!empty($i)) {
      $key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
      $value = trim(substr($header, $i + 2));
      $this->http_header[$key] = $value;
    }
    return strlen($header);
  }
}