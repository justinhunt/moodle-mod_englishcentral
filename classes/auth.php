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
class auth {

    protected $ec = null; // EC activity
    protected $jwt = null; // JWT token
    protected $sdk = null; // SDK token

    protected $ecuserid = null; // the EC userid of the current user
    protected $fake_userid = null; // the id that is presented to EC as a user's unique ID

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
    function __construct($ec) {

        $this->ec = $ec;
        $this->partnerid = $ec->config->partnerid;
        $this->consumerkey = $ec->config->consumerkey;
        $this->consumersecret = $ec->config->consumersecret;
        $this->encryptedsecret = $ec->config->encryptedsecret;

        if (empty($ec->config->developmentmode)) {
            $this->domain = 'englishcentral.com';
        } else {
            $this->domain = 'qaenglishcentral.com';
        }
    }

    /**
     * Creates a new EnglishCentral auth object
     *
     * @param object $ec a EC activity
     * @return object the new EC auth object
     */
    static public function create($ec) {
        return new auth($ec);
    }

    function build_authorize_token() {
        global $USER;

        $exp = round((microtime(true) + 10000) * 1000);
        $consumersecret = \mod_englishcentral\jwt\JWT::urlsafeB64Decode($this->encryptedsecret);
        $payload = array(
            'userID'      => $USER->id,
            'consumerKey' => $this->consumerkey,
            'name'        => fullname($USER),
            'email'       => $USER->email,
            'exp'         => $exp
        );
        $jwt = \mod_englishcentral\jwt\JWT::encode($payload, $consumersecret);

        return $jwt;
    }

    function login_and_auth($jwt) {
        global $USER;

        $url = 'https://bridge.' . $this->domain . '/rest/identity/authorize';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); # required for https urls
        $fields = array('name' => '', //fullname($USER),
                        'partnerID' => $this->partnerid,
                        'nativeLanguage' => $this->get_user_language(),
                        'siteLanguage' => $this->get_site_language(),
                        'applicationBuildDate' => '2017-08-19T13:33:14.000Z');
        $fields_string = http_build_query($fields, '', '&', PHP_QUERY_RFC1738);
        $http_header = array('Accept: ' . self::ACCEPT_V1,
                             'Content-Type: application/x-www-form-urlencoded',
                             'authorizeRequest: ' . $jwt,
                             'Content-Length: ' . strlen($fields_string));
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $http_header);
        $partnerSdkToken = curl_exec($ch);
        curl_close($ch);
        return $partnerSdkToken;
    }

    public function get_site_language($default='en') {
        if (empty($CFG->lang)) {
            return $default;
        } else {
            return str_replace('_utf8', '', $CFG->lang);
        }
    }

    public function get_user_language($default='en') {
        global $USER;
        if (empty($USER->lang)) {
            return $this->get_site_language($default);
        } else {
            return str_replace('_utf8', '', $USER->lang);
        }
    }

    public function create_partner_account() {
        global $USER;
        $sdk = $this->get_sdk_token();
        $url = 'https://bridge.' . $this->domain . '/rest/identity/account';
        $fields = array('partnerID' => $this->partnerid,
                        'partnerAccountID' => $this->get_fake_userid(),
                        'name' => '', // fullname($USER),
                        'email' => '', // $USER->email,
                        'nativeLanguage' => $this->get_user_language(),
                        'siteLanguage' => $this->get_site_language(),
                        'isTeacher' => (int)$this->ec->can_manage(),
                        'timezone' => \core_date::get_user_timezone(),
                        'country' => '', // $USER->country,
                        'fields' => 'amountID,partnerAccountID');
        $url = new \moodle_url($url, $fields);
        $response = $this->doGet($sdk, $url->out(false), self::ACCEPT_V2);
        print_object($response);
        die;
    }

    public function get_auth_header($sdk_token) {
        $consumersecret = \mod_englishcentral\jwt\JWT::urlsafeB64Decode($this->encryptedsecret);
        $payload = \mod_englishcentral\jwt\JWT::decode($sdk_token, $consumersecret, array('HS256'));
        $payload = array('accessToken' => $payload->accessToken,
                         'consumerKey' => $this->consumerkey);
        return 'JWT '.\mod_englishcentral\jwt\JWT::encode($payload, $consumersecret);
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

    public function fetch_js_url() {
        return new \moodle_url('https://www.' . $this->domain . '/partnersdk/sdk.js');
    }

    public function get_jwt_token() {
        if ($this->jwt===null) {
            $this->jwt = $this->build_authorize_token();
        }
        return $this->jwt;
    }

    public function get_sdk_token() {
        if ($this->sdk===null) {
            $jwt = $this->get_jwt_token();
            $this->sdk = $this->login_and_auth($jwt);
        }
        return $this->sdk;
    }

    public function get_fake_userid() {
        global $DB, $USER;
        if ($this->fake_userid===null) {
            $this->fake_userid = $DB->get_field('englishcentral_userids', 'id', array('userid' => $USER->id));
            if (empty($this->fake_userid)) {
                $userid = (object)array(
                    'userid' => $USER->id,
                    'ecuserid' => 0, // this will be set later by get_ecuserid()
                );
                $this->fake_userid = $DB->insert_record('englishcentral_userids', $userid);
            }
        }
        return $this->fake_userid;
    }

    public function get_ecuserid() {
        global $DB, $USER;
        if ($this->ecuserid===null) {
            $this->ecuserid = $DB->get_field('englishcentral_userids', 'ecuserid', array('userid' => $USER->id));
            if (empty($this->ecuserid)) {
                $this->ecuserid = $this->create_partner_account();
                $DB->set_field('englishcentral_userids', 'ecuserid', $this->ecuserid, array('userid' => $USER->id));
            }
        }
        return $this->ecuserid;
    }

    public function missing_config() {
        $missing = array('partnerid' => '/^[0-9]+$/',
                         'consumerkey' => '/^[0-9a-fA-F]{32}$/',
                         'consumersecret' => '/^[0-9a-fA-F]{64}$/',
                         'encryptedsecret' => '/^[0-9a-zA-Z\/+=]+$/');
        foreach ($missing as $name => $pattern) {
            if (isset($this->ec->config->$name) && preg_match($pattern, $this->ec->config->$name)) {
                unset($missing[$name]);
            } else {
                $missing[$name] = $this->ec->get_string($name);
            }
        }
        return (empty($missing) ? '' : $missing);
    }

    public function invalid_config() {
        $sdk = $this->get_sdk_token();
        if (strpos($sdk, '<!DOCTYPE html>')===false) {
            return '';
        } else {
            return preg_replace('/^(.*?<body[^>]*>)|(<\/body>.*$)/', '', $sdk);
        }
    }
}
