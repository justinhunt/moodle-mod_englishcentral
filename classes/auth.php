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
    protected $jwt_token = null; // JWT token
    protected $sdk_token = null; // SDK token
    protected $authorization = null; // HTTP header

    protected $uniqueid = null; // user's unique ID on this Moodle site
    protected $accountid = null; // the EC accountid of the current user

    const ACCEPT_V1 = 'application/vnd.englishcentral-v1+json,application/json;q=0.9,*/*;q=0.8';
    const ACCEPT_V2 = 'application/vnd.englishcentral-v2+json,application/json;q=0.9,*/*;q=0.8';
    const ACCEPT_V3 = 'application/vnd.englishcentral-v3+json,application/json;q=0.9,*/*;q=0.8';
    const ACCEPT_V4 = 'application/vnd.englishcentral-v4+json,application/json;q=0.9,*/*;q=0.8';

    const ACTIVITYTYPE_WATCHING = 9;
    const ACTIVITYTYPE_LEARNING = 10;
    const ACTIVITYTYPE_SPEAKING = 11;

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

    public function get_uniqueid() {
        global $DB, $USER;
        if ($this->uniqueid===null) {
            $table = 'englishcentral_accountids';
            $params = array('userid' => $USER->id);
            $this->uniqueid = $DB->get_field($table, 'id', $params);
            if (empty($this->uniqueid)) {
                $record = (object)array('userid' => $USER->id,
                                        'accountid' => 0);
                $this->uniqueid = ''.$DB->insert_record($table, $record);
                // we need the quotes, '', to convert the id to a string
            }
            // NOTE: it does not seem to be necessary to create a permanent
            // EC accountid. Everything works without doing so.
            // However, in the future, we may offer Moodle students the
            // chance to assume control of their anonymous EC accountid
            $this->get_accountid();
        }
        return $this->uniqueid;
    }

    public function get_accountid() {
        global $DB, $USER;
        if ($this->accountid===null) {
            $table = 'englishcentral_accountids';
            $params = array('userid' => $USER->id);
            $this->accountid = $DB->get_field($table, 'accountid', $params);
            if (empty($this->accountid)) {
                $this->accountid = $this->create_accountid();
                $DB->set_field($table, 'accountid', $this->accountid, $params);
            } else {
                // next line is not necessary, because we already know accountID
                // $this->accountid = $this->fetch_accountid();
            }
        }
        return $this->accountid;
    }

    public function get_jwt_token() {
        if ($this->jwt_token===null) {
            $payload = array('userID' => $this->get_uniqueid(),
                             'consumerKey' => $this->consumerkey,
                             'exp' => round((microtime(true) + 10000) * 1000));
            $secret = \mod_englishcentral\jwt\JWT::urlsafeB64Decode($this->encryptedsecret);
            $this->jwt_token = \mod_englishcentral\jwt\JWT::encode($payload, $secret);
        }
        return $this->jwt_token;
    }

    public function set_sdk_token($sdk_token) {
        $this->sdk_token = $sdk_token;
    }

    public function get_sdk_token() {
        if ($this->sdk_token===null) {
            $jwt_token = $this->get_jwt_token();

            $url = $this->get_url('bridge', 'rest/identity/authorize');

            $fields = array('partnerID' => $this->partnerid,
                            'siteLanguage' => $this->get_site_language(),
                            'nativeLanguage' => $this->get_user_language(),
                            'applicationBuildDate' => '2017-08-19T13:33:14.000Z');
            $fields = http_build_query($fields, '', '&', PHP_QUERY_RFC1738);

            $header = array('Accept: ' . self::ACCEPT_V1,
                            'AuthorizeRequest: ' . $jwt_token,
                            'Content-Length: ' . strlen($fields),
                            'Content-Type: application/x-www-form-urlencoded');

            $this->sdk_token = $this->doCurl($url, $header, false, true, $fields);
        }
        return $this->sdk_token;
    }

    public function get_header($accept) {
        return array('Accept: ' . $accept,
                     'Authorization: ' . $this->get_authorization(),
                     'Content-Type: application/x-www-form-urlencoded');
    }

    public function get_authorization() {
        if ($this->authorization===null) {
            if ($sdk_token = $this->get_sdk_token()) {
                $consumersecret = \mod_englishcentral\jwt\JWT::urlsafeB64Decode($this->encryptedsecret);
                $payload = \mod_englishcentral\jwt\JWT::decode($sdk_token, $consumersecret, array('HS256'));
                $payload = array('accessToken' => $payload->accessToken,
                                 'consumerKey' => $this->consumerkey);
                $this->authorization = 'JWT '.\mod_englishcentral\jwt\JWT::encode($payload, $consumersecret);
            }
        }
        return $this->authorization;
    }

    public function create_accountid() {
        $subdomain = 'bridge';
        $endpoint = 'rest/identity/account';
        $fields = array('partnerID' => $this->partnerid,
                        'partnerAccountID' => $this->get_uniqueid(),
                        'nativeLanguage' => $this->get_user_language(),
                        'siteLanguage' => $this->get_site_language(),
                        'isTeacher' => (int)$this->ec->can_manage(),
                        'timezone' => \core_date::get_user_timezone(),
                        'fields' => 'accountID');
        $response = $this->doPost($subdomain, $endpoint, $fields, self::ACCEPT_V1);
        return $this->return_value($response, 'accountID', 0);
    }

    public function fetch_accountid() {
        $subdomain = 'bridge';
        $endpoint = 'rest/identity/account';
        $fields = array('partnerID' => $this->partnerid,
                        'partnerAccountID' => $this->get_uniqueid(),
                        'fields' => 'accountID');
        $response = $this->doPost($subdomain, $endpoint, $fields, self::ACCEPT_V1);
        return $this->return_value($response, 'accountID', 0);
    }

    public function fetch_dialog_list($videoids) {
        $subdomain = 'bridge';
        $endpoint = 'rest/content/dialog';
        $fields = array('dialogIDs' => implode(',', $videoids),
                        'siteLanguage' => $this->get_site_language(),
                        'fields' => 'dialogID,title,difficulty,duration,dialogURL,thumbnailURL');
        return $this->doGet($subdomain, $endpoint, $fields, self::ACCEPT_V1);
    }

    public function fetch_dialog_progress($videoid, $sdk_token='') {
        if ($sdk_token) {
            $this->set_sdk_token($sdk_token);
        }
        $subdomain = 'reportcard';
        $endpoint = "rest/report/dialog/$videoid/progress";
        return $this->doGet($subdomain, $endpoint, array(), self::ACCEPT_V2);
    }

    public function doGet($subdomain, $endpoint, $fields, $accept) {
        $url = $this->get_url($subdomain, $endpoint, $fields);
        $header = $this->get_header($accept);
        return $this->doCurl($url, $header, true, false);
    }

    public function doPost($subdomain, $endpoint, $fields, $accept) {
        $url = $this->get_url($subdomain, $endpoint, $fields);
        $header = $this->get_header($accept);
        return $this->doCurl($url, $header, true, true);
    }

    public function doCurl($url, $header, $json_decode=false, $post=null, $fields=null) {

        // NOTE: we could also use the Moodle API (lib/filelib.php)
        // download_file_content($url, $headers=null, $postdata=null, $fullresponse=false,
        //                       $timeout=300, $connecttimeout=20, $skipcertverify=false,
        //                       $tofile=NULL, $calctimeout=false)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,             $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,  true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,  true);
        curl_setopt($ch, CURLOPT_AUTOREFERER,     true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER,   $header);

        if ($post) {
            curl_setopt($ch, CURLOPT_POST, $post);
            if ($fields) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            }
        }

        $response = curl_exec($ch);
        curl_close($ch);

        if ($json_decode && $this->is_json($response)) {
            $response = json_decode($response);
        }

        return $response;
    }

    public function is_json($response) {
        if (substr($response, 0, 1)=='{' && substr($response, -1)=='}') {
            return true;
        }
        if (substr($response, 0, 1)=='[' && substr($response, -1)==']') {
            return true;
        }
        return false;
    }

    public function return_value($response, $name, $default) {
        if (empty($response->$name)) {
            return $default;
        } else {
            return $response->$name;
        }
    }

    public function get_user_language($default='en') {
        global $CFG, $USER;
        if (! empty($USER->lang)) {
            return substr($USER->lang, 0, 2);
        }
        if (! empty($CFG->lang)) {
            return substr($CFG->lang, 0, 2);
        }
        return $default;
    }

    public function get_site_language() {
        return substr(current_language(), 0, 2);
    }

    public function get_fetch_url() {
        return $this->get_url('bridge', 'rest/content/dialog');
    }

    public function get_search_url() {
        return $this->get_url('bridge', 'rest/content/dialog/search/fulltext');
    }

    public function get_url($subdomain, $endpoint, $fields=array()) {
        $url = "https://$subdomain.$this->domain/$endpoint";
        $url = new \moodle_url($url, $fields);
        return $url->out(false); // join with "&" not "&amp;"
    }

    public function get_js_url() {
        $url = $this->get_url('www', 'partnersdk/sdk.js');
        return new \moodle_url($url);
    }

    public function missing_config() {
        $missing = array('partnerid' => '/^[0-9]+$/',
                         'consumerkey' => '/^[0-9a-fA-F]{32}$/',
                         'consumersecret' => '/^[0-9a-fA-F]{64}$/',
                         'encryptedsecret' => '/^[0-9a-zA-Z\/+=]+$/');
        foreach ($missing as $name => $pattern) {
            if ($name=='consumersecret' && $this->ec->config->$name==$this->ec->config->encryptedsecret) {
                unset($missing[$name]);
            } else if (isset($this->ec->config->$name) && preg_match($pattern, $this->ec->config->$name)) {
                unset($missing[$name]);
            } else {
                $missing[$name] = $this->ec->get_string($name);
            }
        }
        return (empty($missing) ? '' : $missing);
    }

    public function invalid_config() {
        $sdk_token = $this->get_sdk_token();
        // the token is usually 189 chars long and split into 3 parts delimited by [\.].
        // Parts 1 & 2 contain [0-9a-zA-Z]. The 3rd part can additionally contain [_-].
        if (preg_match('/^[0-9a-zA-Z\._-]{180,200}$/', $sdk_token)) {
            return ''; // token is valid - YAY!
        }
        if ($this->is_json($sdk_token)) {
            // JSON error message from EC server
            return json_decode($sdk_token)->log;
        }
        if (strpos($sdk_token, '<!DOCTYPE html>')===0) {
            // HTML error message, maybe a wrong URL or invalid data was sent to EC
            return preg_replace('/^(.*?<body[^>]*>)|(<\/body>.*$)/', '', $sdk_token);
        }
        // some other problematic token
        return $sdk_token;
    }
}
