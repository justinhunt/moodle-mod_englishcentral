<?php
/**
 * External.
 *
 * @package mod_englishcentral
 * @author  Justin Hunt - poodll.com
 */

global $CFG;
require_once($CFG->libdir . '/externallib.php');

use mod_englishcentral\utils;
use mod_englishcentral\constants;

use external_api;
use external_function_parameters;
use external_value;

/**
 * External class.
 *
 * @package mod_englishcentral
 * @author  Justin Hunt - Poodll.com
 */
class mod_englishcentral_external extends external_api {

    public static function add_video_parameters() {
        return new external_function_parameters([
            'ecid' => new external_value(PARAM_INT),
            'videoid' => new external_value(PARAM_INT)
        ]);
    }

    public static function add_video($ecid,$videoid){
        $ret= utils::add_video($ecid,$videoid);
        if($ret){
            return true;
        }else{
            return false;
        }
    }
    public static function add_video_returns() {
        return new external_value(PARAM_BOOL);
    }



}
