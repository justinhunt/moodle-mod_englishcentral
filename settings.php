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
 * englishcentral module admin settings and defaults
 *
 * @package    mod
 * @subpackage englishcentral
 * @copyright  2014 Justin Hunt poodllsupport@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_englishcentral\constants;

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $plugin = 'mod_englishcentral';

    $name = 'poodllapiuser';
    $label = get_string($name, $plugin);
    $details = get_string($name.'_details', $plugin);
    $settings->add(new admin_setting_configtext("$plugin/$name", $label, $details, '', PARAM_TEXT));

    $cloudpoodll_apiuser=get_config(constants::M_COMPONENT,'poodllapiuser');
    $cloudpoodll_apisecret=get_config(constants::M_COMPONENT,'poodllapisecret');
    $show_below_apisecret='';
//if we have an API user and secret we fetch token
    if(!empty($cloudpoodll_apiuser) && !empty($cloudpoodll_apisecret)) {
        $tokeninfo = mod_englishcentral\cloudpoodllauth::fetch_token_for_display(
            $cloudpoodll_apiuser,
            $cloudpoodll_apisecret);

        $show_below_apisecret=$tokeninfo;
//if we have no API user and secret we show a "fetch from elsewhere on site" or "take a free trial" link
    }else{
        $amddata=['apppath'=>$CFG->wwwroot . '/' .constants::M_URL];
        $cp_components=['filter_poodll','qtype_cloudpoodll','mod_readaloud','mod_wordcards','mod_solo','mod_minilesson','mod_pchat',
            'atto_cloudpoodll','tinymce_cloudpoodll', 'assignfeedback_cloudpoodll', 'assignsubmission_cloudpoodll'];
        foreach($cp_components as $cp_component){
            switch($cp_component){
                case 'filter_poodll':
                    $apiusersetting='cpapiuser';
                    $apisecretsetting='cpapisecret';
                    break;
                case 'mod_englishcentral':
                    $apiusersetting='poodllapiuser';
                    $apisecretsetting='poodllapisecret';
                    break;
                default:
                    $apiusersetting='apiuser';
                    $apisecretsetting='apisecret';
            }
            $cloudpoodll_apiuser=get_config($cp_component,$apiusersetting);
            if(!empty($cloudpoodll_apiuser)){
                $cloudpoodll_apisecret=get_config($cp_component,$apisecretsetting);
                if(!empty($cloudpoodll_apisecret)){
                    $amddata['apiuser']=$cloudpoodll_apiuser;
                    $amddata['apisecret']=$cloudpoodll_apisecret;
                    break;
                }
            }
        }
        $show_below_apisecret=$OUTPUT->render_from_template( constants::M_COMPONENT . '/managecreds',$amddata);
    }

    $name = 'poodllapisecret';
    $label = get_string($name, $plugin);
    $settings->add(new admin_setting_configtext("$plugin/$name", $label, $show_below_apisecret, '', PARAM_TEXT));

    // Progress dials options
    $name = 'progressdials';
    $label = get_string($name, constants::M_COMPONENT);
    $details = get_string($name . '_details', constants::M_COMPONENT);
    $default = constants::M_PROGRESSDIALS_TOP;
    $options = array(constants::M_PROGRESSDIALS_BOTTOM=>get_string('progressdials_bottom',constants::M_COMPONENT),
        constants::M_PROGRESSDIALS_TOP=>get_string('progressdials_top',constants::M_COMPONENT));
    $settings->add(new admin_setting_configselect(constants::M_COMPONENT . "/$name",
        $label, $details, $default, $options));

    // Chat Mode
    $name = 'chatmode';
    $label = get_string($name, constants::M_COMPONENT);
    $details = get_string($name . '_details', constants::M_COMPONENT);
    $default = false;
    $settings->add(new admin_setting_configcheckbox(constants::M_COMPONENT . "/$name",
        $label, $details, $default));

    $name = 'advancedsection';
    $label = get_string($name, $plugin);
    $details = get_string($name.'_details', $plugin);
    $settings->add(new admin_setting_heading("$plugin/$name", $label, $details));

    // $link = new moodle_url('/mod/englishcentral/support.php');
    // $link = html_writer::tag('a', 'Poodll.com (EnglishCentral demo request)', array('href' => $link, 'target' => 'EC'));
    // whenever possible, the support URL will display a form in the browser's preferred language
    $link = new moodle_url('https://poodll.com/contact');
    $link = html_writer::tag('a', 'Poodll.com', array('href' => $link, 'target' => 'EC'));


    $name = 'partnerid';
    $label = get_string($name, $plugin);
    $explain = get_string($name.'explain', $plugin, $link);
    $default = ''; // get_string($name.'default', $plugin);
    $settings->add(new admin_setting_configtext("$plugin/$name", $label, $explain, $default, PARAM_TEXT));

    $name = 'consumerkey';
    $label = get_string($name, $plugin);
    $explain = get_string($name.'explain', $plugin, $link);
    $default = ''; // get_string($name.'default', $plugin);
    $settings->add(new admin_setting_configtext("$plugin/$name", $label, $explain, $default, PARAM_TEXT));

    $name = 'consumersecret';
    $label = get_string($name, $plugin);
    $explain = get_string($name.'explain', $plugin, $link);
    $default = ''; // get_string($name.'default', $plugin);
    $settings->add(new admin_setting_configtext("$plugin/$name", $label, $explain, $default, PARAM_TEXT));

    $name = 'encryptedsecret';
    $label = get_string($name, $plugin);
    $explain = get_string($name.'explain', $plugin, $link);
    $default = ''; // get_string($name.'default', $plugin);
    $settings->add(new admin_setting_configtext("$plugin/$name", $label, $explain, $default, PARAM_TEXT));

    $name = 'developmentmode';
    $label = get_string($name, $plugin);
    $explain = get_string($name.'explain', $plugin);
    $default = (strpos($CFG->wwwroot, '/localhost/')===false ? 0 : 1);
    $settings->add(new admin_setting_configcheckbox("$plugin/$name", $label, $explain, $default));

    $name = 'playerversion';
    $label = get_string($name, $plugin);
    $explain = get_string($name.'explain', $plugin, $link);
    $default = get_string($name.'default', $plugin);
    $options = array('JSDK2' => 'JSDK2', 'JSDK3' => 'JSDK3');
    $settings->add(new admin_setting_configselect("$plugin/$name", $label, $explain, $default, $options));

    $settings->add(new admin_setting_configcheckbox($plugin .  '/enablesetuptab',
            get_string('enablesetuptab', $plugin ), get_string('enablesetuptab_details',$plugin ), 0));


}
