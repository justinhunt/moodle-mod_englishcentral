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

namespace mod_englishcentral;

defined('MOODLE_INTERNAL') || die();

/**
 * Authentication class to access EnglishCentral API
 * originally used OAuth, modified to use JWT
 *
 *
 * @package    englishcentral
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class englishcentral {

    const ACCEPT_V1 = "application/vnd.englishcentral-v1+json,application/json;q=0.9,*/*;q=0.8";
    const ACCEPT_V2 = "application/vnd.englishcentral-v2+json,application/json;q=0.9,*/*;q=0.8";
    const ACCEPT_V3 = "application/vnd.englishcentral-v3+json,application/json;q=0.9,*/*;q=0.8";
    const ACCEPT_V4 = "application/vnd.englishcentral-v4+json,application/json;q=0.9,*/*;q=0.8";
    const ACTIVITYTYPE_SPEAKING = 11;
    const ACTIVITYTYPE_WATCHING = 9;
    const ACTIVITYTYPE_LEARNING = 10;

    /**
     * construct English Central object
     */
    function __construct() {

        $config                 = get_config('englishcentral');
        $this->consumer_key     = $config->consumerkey;
        $this->consumer_secret  = $config->consumersecret;
        $this->encrypted_secret = $config->encryptedsecret;
        $this->partnerid        = $config->partnerid;

        if (empty($config->developmentmode)) {
            $this->domain = 'englishcentral.com';
        } else {
            $this->domain = 'qaenglishcentral.com';
        }
    }

    function build_authorize_token($user) {

        $exp = round((microtime(true) + 10000) * 1000);
        $consumersecret = \mod_englishcentral\Firebase\JWT\JWT::urlsafeB64Decode($this->encrypted_secret);
        $payload = array(
            'userID'      => $user->id,
            'consumerKey' => $this->consumer_key,
            'name'        => fullname($user),
            'email'       => $user->email,
            'exp'         => $exp
        );
        $jwt = \mod_englishcentral\Firebase\JWT\JWT::encode($payload, $consumersecret);

        return $jwt;
    }

    function login_and_auth($jwt, $ec_user) {

        $url = 'https://bridge.' . $this->domain . '/rest/identity/authorize';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); # required for https urls
        $fields = array(
            'name' => fullname($ec_user),
            'partnerID' => $this->partnerid,
            'nativeLanguage' => 'en',
            'siteLanguage'   => 'en',
            'applicationBuildDate' => '2017-08-19T13:33:14.000Z'
        );

        //it took hours to find this was causing curl and ec to miscommunicate ...
        //$fields_string = http_build_query($fields);
        $fields_string = http_build_query($fields, '', '&', PHP_QUERY_RFC1738);

        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Accept: " . self::ACCEPT_V1,
            "Content-Type: application/x-www-form-urlencoded",
            "authorizeRequest: $jwt",
            "Content-Length: " . strlen($fields_string)
        ));
        $partnerSdkToken = curl_exec($ch);
        curl_close($ch);
        return $partnerSdkToken;

    }

    public function get_auth_header($sdk_token) {
        $consumersecret = \mod_englishcentral\Firebase\JWT\JWT::urlsafeB64Decode($this->encrypted_secret);
        $payload        = (array) \mod_englishcentral\Firebase\JWT\JWT::decode($sdk_token, $consumersecret, array(
            'HS256'
        ));

        $jwt = 'JWT ' . \mod_englishcentral\Firebase\JWT\JWT::encode(array(
            'accessToken' => $payload['accessToken'],
            'consumerKey' => $this->consumer_key
        ), $consumersecret);

        return $jwt;

    }

    public function doPost($jwt, $url, $fields = array(), $accept) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $fields_string = http_build_query($fields);

        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Accept: " . $accept,
            "Content-Type: application/x-www-form-urlencoded",
            "authorization: $jwt",
            "Content-Length: " . strlen($fields_string)
        ));

        $responseText = curl_exec($ch);
        curl_close($ch);

        return json_decode($responseText);
    }

    public function doGet($sdk_token, $url, $accept) {

        $auth_header = $this->get_auth_header($sdk_token);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Accept: " . $accept,
            "Content-Type: application/x-www-form-urlencoded",
            "Authorization: $auth_header"
        ));

        $responseText = curl_exec($ch);
        curl_close($ch);
        return json_decode($responseText);
    }

    function fetch_js_url() {
        return 'https://www.' . $this->domain . '/partnersdk/sdk.js';
    }
}
