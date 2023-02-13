<?php
/**
 * External.
 *
 * @package mod_englishcentral
 * @author  Justin Hunt - poodll.com
 */


use mod_englishcentral\utils;
use mod_englishcentral\constants;

/**
 * External class.
 *
 * @package mod_englishcentral
 * @author  Justin Hunt - Poodll.com
 */
class mod_englishcentral_external extends external_api {

    public static function add_video_parameters() {
        return new external_function_parameters([
            'modid' => new external_value(PARAM_INT),
            'term' => new external_value(PARAM_RAW),
            'definition' => new external_value(PARAM_RAW),
            'translations' => new external_value(PARAM_RAW),
            'sourcedef' => new external_value(PARAM_RAW),
            'modelsentence' => new external_value(PARAM_RAW),
        ]);
    }

    public static function add_video($modid,$term, $definition,$translations,$sourcedef,$modelsentence){
        $ret= utils::add_video($modid,$term, $definition,$translations,$sourcedef,$modelsentence);
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
