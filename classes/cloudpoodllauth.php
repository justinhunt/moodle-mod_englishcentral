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
 * Auth helper via Cloud Poodll
 *
 * @package    mod_englishcentral
 * @copyright  2020 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_englishcentral;
defined('MOODLE_INTERNAL') || die();


/**
 * Functions used generally across this mod
 *
 * @package    mod_englishcentral
 * @copyright  2020 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cloudpoodllauth {

    //constants
    const M_COMPONENT = 'mod_englishcentral';
    const M_MODNAME = 'englishcentral';
    const M_URL = '/mod/englishcentral';
    const M_CLASS = 'mod_englishcentral';
    const M_PLUGINSETTINGS = '/admin/settings.php?section=modsettingenglishcentral';
    const CLOUDPOODLL = 'https://cloud.poodll.com';

    //see if this is truly json or some error
    public static function is_json($string) {
        if (!$string) {
            return false;
        }
        if (empty($string)) {
            return false;
        }
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    //we use curl to fetch transcripts from AWS and Tokens from cloudpoodll
    //this is our helper
    //we use curl to fetch transcripts from AWS and Tokens from cloudpoodll
    //this is our helper
    public static function curl_fetch($url, $postdata = false) {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');
        $curl = new \curl();

        $result = $curl->get($url, $postdata);
        return $result;
    }

    //This is called from the settings page and we do not want to make calls out to cloud.poodll.com on settings
    //page load, for performance and stability issues. So if the cache is empty and/or no token, we just show a
    //"refresh token" links
    public static function fetch_token_for_display($apiuser, $apisecret) {
        global $CFG;

        //First check that we have an API id and secret
        //refresh token
        $refresh = \html_writer::link($CFG->wwwroot . self::M_URL . '/refreshtoken.php',
                        get_string('refreshtoken', self::M_COMPONENT)) . '<br>';

        $message = '';
        $apiuser = utils::super_trim($apiuser);
        $apisecret = utils::super_trim($apisecret);
        if (empty($apiuser)) {
            $message .= get_string('noapiuser', self::M_COMPONENT) . '<br>';
        }
        if (empty($apisecret)) {
            $message .= get_string('noapisecret', self::M_COMPONENT);
        }

        if (!empty($message)) {
            return $refresh . $message;
        }

        //Fetch from cache and process the results and display
        $cache = \cache::make_from_params(\cache_store::MODE_APPLICATION, self::M_COMPONENT, 'token');
        $tokenobject = $cache->get('recentpoodlltoken');

        //if we have no token object the creds were wrong ... or something
        if (!($tokenobject)) {
            $message = get_string('notokenincache', self::M_COMPONENT);
            //if we have an object but its no good, creds werer wrong ..or something
        } else if (!property_exists($tokenobject, 'token') || empty($tokenobject->token)) {
            $message = get_string('credentialsinvalid', self::M_COMPONENT);
            //if we do not have subs, then we are on a very old token or something is wrong, just get out of here.
        } else if (!property_exists($tokenobject, 'subs')) {
            $message = 'No subscriptions found at all';
        }
        if (!empty($message)) {
            return $refresh . $message;
        }

        //we have enough info to display a report. Lets go.
        foreach ($tokenobject->subs as $sub) {
            $sub->expiredate = date('d/m/Y', $sub->expiredate);
            $message .= get_string('displaysubs', self::M_COMPONENT, $sub) . '<br>';
        }
        //Is app authorised
        if (in_array(self::M_COMPONENT, $tokenobject->apps)) {
            $message .= get_string('appauthorised', self::M_COMPONENT) . '<br>';
        } else {
            $message .= get_string('appnotauthorised', self::M_COMPONENT) . '<br>';
        }

        return $refresh . $message;

    }

    //We need a Poodll token to get the EC creds
    public static function fetch_token($apiuser, $apisecret, $force = false) {

        $cache = \cache::make_from_params(\cache_store::MODE_APPLICATION, self::M_COMPONENT, 'token');
        $tokenobject = $cache->get('recentpoodlltoken');
        $tokenuser = $cache->get('recentpoodlluser');
        $apiuser = utils::super_trim($apiuser);
        $apisecret = utils::super_trim($apisecret);

        //if we got a token and its less than expiry time
        // use the cached one
        if ($tokenobject && $tokenuser && $tokenuser == $apiuser && !$force) {
            if ($tokenobject->validuntil == 0 || $tokenobject->validuntil > time()) {
                return $tokenobject;
            }
        }else{
            //init our token object since we will need to fetch and process it
            $tokenobject=false;
        }


        // Send the request & save response to $resp
        $token_url = self::CLOUDPOODLL . "/local/cpapi/poodlltoken.php";
        $postdata = array(
                'username' => $apiuser,
                'password' => $apisecret,
                'service' => 'cloud_poodll'
        );
        $token_response = self::curl_fetch($token_url, $postdata);
        if ($token_response) {
            $resp_object = json_decode($token_response);
            if ($resp_object && property_exists($resp_object, 'token')) {
                //store the expiry timestamp and adjust it for diffs between our server times
                if ($resp_object->validuntil) {
                    $validuntil = $resp_object->validuntil - ($resp_object->poodlltime - time());
                    //we refresh one hour out, to prevent any overlap
                    $validuntil = $validuntil - (1 * HOURSECS);
                } else {
                    $validuntil = 0;
                }

                //cache the token
                $tokenobject = new \stdClass();
                $tokenobject->token = $resp_object->token;
                $tokenobject->validuntil = $validuntil;
                $tokenobject->subs = false;
                $tokenobject->apps = false;
                $tokenobject->sites = false;
                $tokenobject->custom = false;
                if (property_exists($resp_object, 'subs')) {
                    $tokenobject->subs = $resp_object->subs;
                }
                if (property_exists($resp_object, 'apps')) {
                    $tokenobject->apps = $resp_object->apps;
                }
                if (property_exists($resp_object, 'sites')) {
                    $tokenobject->sites = $resp_object->sites;
                }
                if (property_exists($resp_object, 'custom')) {
                    $tokenobject->custom = $resp_object->custom;
                }

                $cache->set('recentpoodlltoken', $tokenobject);
                $cache->set('recentpoodlluser', $apiuser);

            } else {
                //its no good.
                $tokenobject = false;
                if ($resp_object && property_exists($resp_object, 'error')) {
                    //ERROR = $resp_object->error
                }
            }
        } else {
            //its no good
            $tokenobject = false;
        }
        //return whatever we ended up with
        return $tokenobject;
    }

    //check token and tokenobject(from cache)
    //return error message or blank if its all ok
    public static function fetch_token_error($token){
        global $CFG;

        //check token authenticated
        if(empty($token)) {
            $message = get_string('novalidcredentials', self::M_COMPONENT,
                    $CFG->wwwroot . self::M_PLUGINSETTINGS);
            return $message;
        }

        // Fetch from cache and process the results and display.
        $cache = \cache::make_from_params(\cache_store::MODE_APPLICATION, self::M_COMPONENT, 'token');
        $tokenobject = $cache->get('recentpoodlltoken');

        //we should not get here if there is no token, but lets gracefully die, [v unlikely]
        if (!($tokenobject)) {
            $message = get_string('notokenincache', self::M_COMPONENT);
            return $message;
        }

        //We have an object but its no good, creds were wrong ..or something. [v unlikely]
        if (!property_exists($tokenobject, 'token') || empty($tokenobject->token)) {
            $message = get_string('credentialsinvalid', self::M_COMPONENT);
            return $message;
        }
        // if we do not have subs.
        if (!property_exists($tokenobject, 'subs')) {
            $message = get_string('nosubscriptions', self::M_COMPONENT);
            return $message;
        }
        // Is app authorised?
        if (!property_exists($tokenobject, 'apps') || !in_array(self::M_COMPONENT, $tokenobject->apps)) {
            $message = get_string('appnotauthorised', self::M_COMPONENT);
            return $message;
        }

        // Do we have custom properties - in gthis case that would indicate our subscription had expired
        if (!self::fetch_token_customproperty($tokenobject,self::M_COMPONENT .'_partnerid')) {
            $message = get_string('subscriptionhasnocreds', self::M_COMPONENT);
            return $message;
        }

        //just return empty if there is no error.
        return '';
    }

    //extract a custom property from the token
    public static function fetch_token_customproperty($tokenobject, $property){
        if(!($tokenobject)){
            return false;
        }
        if(!isset($tokenobject->custom) || !($tokenobject->custom)){
            return false;
        }
        if(isset($tokenobject->custom->{$property})){
            return $tokenobject->custom->{$property};
        }else{
            return false;
        }
    }

    //stage remote processing job ..just logging really
    public static function stage_remote_process_job($cmid) {

        global $CFG, $USER;

        $token=false;
        $conf= get_config(constants::M_COMPONENT);
        if (!empty($conf->poodllapiuser) && !empty($conf->poodllapisecret)) {
            $token = self::fetch_token($conf->poodllapiuser, $conf->poodllapisecret);
        }
        if(!$token || empty($token)){
            return false;
        }

        $host = parse_url($CFG->wwwroot, PHP_URL_HOST);
        if (!$host) {
            $host = "unknown";
        }
        //owner
        $owner = hash('md5',$USER->username);
        $ownercomphash = hash('md5',$USER->username . constants::M_COMPONENT . $cmid . date("Y-m-d"));

        //The REST API we are calling
        $functionname = 'local_cpapi_stage_remoteprocess_job';

        //log.debug(params);
        $params = array();
        $params['wstoken'] = $token->token;
        $params['wsfunction'] = $functionname;
        $params['moodlewsrestformat'] = 'json';
        $params['appid'] = constants::M_COMPONENT;
        $params['region'] = "useast1";
        $params['host'] = $host;
        $params['s3outfilename'] = $ownercomphash; //we just want a unique value per session here
        $params['owner'] = $owner;
        $params['transcode'] =  '0';
        $params['transcoder'] = 'default';
        $params['transcribe'] =  '0';
        $params['subtitle'] = '0';
        $params['language'] = 'en-US';
        $params['vocab'] = 'none';
        $params['s3path'] ='/';
        $params['mediatype'] = 'other';
        $params['notificationurl'] = 'none';
        $params['sourcemimetype'] = 'unknown';

        $serverurl = self::CLOUDPOODLL . '/webservice/rest/server.php';
        $response = self::curl_fetch($serverurl, $params);
        if (!self::is_json($response)) {
            return false;
        }
        $payloadobject = json_decode($response);

        //returnCode > 0  indicates an error
        if ($payloadobject->returnCode > 0) {
            return false;
            //if all good, then lets just return true
        } else if ($payloadobject->returnCode === 0) {
            return true;
        } else {
            return false;
        }
    }

}
