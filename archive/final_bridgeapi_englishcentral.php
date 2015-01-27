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
  public $host = "http://bridge.englishcentral.com";
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
  public $useragent = 'EnglishCentral_Moodle_OAuth';




  /**
   * Set API URLS
   */
  function accessTokenURL()  { return 'http://bridge.englishcentral.com/rest/oauth/access_token'; }
  function loginURL()  { return 'http://bridge.englishcentral.com/rest/oauth/login'; }
  function authenticateURL() { return 'http://bridge.englishcentral.com/oauth/authenticate'; }
  function authorizeURL()    { return 'http://bridge.englishcentral.com/oauth/authorize'; }
  function requestTokenURL() { return 'http://bridge.englishcentral.com/rest/oauth/request_token'; }


  /**
   * Debug helpers
   */
  function lastStatusCode() { return $this->http_status; }
  function lastAPICall() { return $this->last_api_call; }

  /**
   * construct TwitterOAuth object
   */
  function __construct($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL) {
    $this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
    $this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);
    if (!empty($oauth_token) && !empty($oauth_token_secret)) {
      $this->token = new OAuthConsumer($oauth_token, $oauth_token_secret);
    } else {
      $this->token = NULL;
    }
  }


  /**
   * Get a request_token from Twitter
   *
   * @returns a key/value array containing oauth_token and oauth_token_secret
   */
  function getRequestToken($oauth_callback) {
    $parameters = array();
    if ($oauth_callback != NULL ) {
        $parameters['oauth_callback'] = $oauth_callback;
    }

    $parameters['oauth_token']='';

   // $response = $this->oAuthRequest($this->requestTokenURL(), 'POST', $min_parameters); syntactically wrong
   $response = $this->oAuthRequest($this->requestTokenURL(),'AUTHPOST',$parameters); //response=""

    //echo '@@aaaa@@<br />';
    // print_r($response);
     //   echo '@@bbbb@@<br />';
    $token = json_decode($response,true);
    print_r($token);

    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token['oauth_token'];
  }
  
  function getVerifier() {
    $parameters=array();
    
    $parameters['applicationBuildDate']='2014-09-26T17:23:54.486Z';
    $parameters['applicationName']='Moodle_EC_Integration';
    $parameters['siteLanguage']='en';
    $parameters['partnerID']='44';
    $parameters['visitorID']='poodll_visitor01';
    $parameters['email']= 'sva9dd15879367a8e2@englishcentral.com';
    $parameters['password']= '12a0daac-34de-4a1f-a29d-7a10ec450599';
    
     $url = $this->loginURL();
    
    //$response = $this->oAuthRequest($url,'AUTHPOST',$parameters); //= response=""
    //$response = $this->oAuthRequest($url,'POST',$parameters); //= email is incorrect
    $response = $this->http($url,'POST',$parameters); //=partnerid is wrong
    
     print_r($response);
     $token = json_decode($response,true);
    return $token['verifier'];
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
  function getAccessToken($oauth_verifier, $request_token) {
    $parameters = array();
    $parameters['oauth_verifier'] = $oauth_verifier;
    $parameters['oauth_token'] = $request_token;

    
    $response = $this->oAuthRequest($this->accessTokenURL(), 'POST', $parameters);

    $token = json_decode($response,true);
    print_r($token);
    //echo "token:" . $token['oauth_token'];
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token['oauth_token'];
  }


  /**
   * Format and sign an OAuth / API request
   */
  function oAuthRequest($url, $method, $parameters) {
    if (strrpos($url, 'https://') !== 0 && strrpos($url, 'http://') !== 0) {
      $url = "{$this->host}{$url}.{$this->format}";
    }
    $usemethod='POST';
    $request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $usemethod, $url, $parameters);
    $request->sign_request($this->sha1_method, $this->consumer, $this->token);
    //print_r($request);
    switch ($method) {
        case 'GET':
          return $this->http($request->to_url(), 'GET');
        case 'AUTHPOST':
          return $this->http($request->get_normalized_http_url(), $method, $request->get_parameters());
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
   
    if($method=="AUTHPOST"){
        $headerauth = true;
        $method='POST';
    }else{
        $headerauth = false;
    }
    
    $this->http_info = array();
    $ci = curl_init();
    /* Curl settings */
    
    //if debugging
    //use this for debugging. Make sure you have a writeable curllog.txt
    /*
    curl_setopt($ci, CURLOPT_VERBOSE, true);
    $verbose = fopen('/var/www/moodle/27x/mod/englishcentral/curlylog.txt', 'rw+');
    curl_setopt($ci, CURLOPT_STDERR, $verbose);
*/
    
    
    curl_setopt($ci, CURLOPT_USERAGENT, $this->useragent);
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
    curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);

    curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
    curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
    curl_setopt($ci, CURLOPT_HEADER, FALSE);
    
    $httpheaders = array();
    $httpheaders[] = 'Expect:';
    $httpheaders[] = 'Accept: application/vnd.englishcentral-v2+json,application/json;q=0.9,*/*;q=0.8';
    //$httpheaders[] = 'Accept: application/vnd.englishcentral-v2+json';
    $httpheaders[] = 'Content-Type: application/x-www-form-urlencoded';
    
    //if we are useing the authorisation header method
   if($headerauth){
        $authfields = "";
        foreach($postfields as $key=>$value){
            if($authfields!=""){$authfields .= ',';}
            $authfields .= $key . '="' . $value . '"';
        }
        $httpheaders[] = 'Authorization: OAuth ' . $authfields;
   }
 
     curl_setopt($ci,CURLOPT_HTTPHEADER,$httpheaders);
    


    switch ($method) {
      case 'POST':
        curl_setopt($ci, CURLOPT_POST, TRUE);
        if (!empty($postfields) && !$headerauth) {
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

}