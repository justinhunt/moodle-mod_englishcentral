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

// disable warnings from the JS parser, jshint
/* globals ECSDK:false */

/**
 * load the EnglishCentral player
 *
 * @module      mod_englishcentral/view
 * @category    output
 * @copyright   Gordon Bateson
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'jqueryui'], function($) {
    /** @alias module:mod_englishcentral/view */
    return {
        "init": function (opts) {
            $(".activity-title, .thumb-frame").each(function(){
                var prefix = new RegExp("^.*/");
                var player = opts["playerdiv"];
                var sdktoken = opts["sdktoken"];
                var partnerKey = opts["consumerkey"];
                $(this).click(function(evt){
                    var dialogID = $(this).prop("href")
                                          .replace(prefix, "");
                    window.ECSDK.loadWidget("player", {
                        "partnerKey":      partnerKey,
                        "partnerSdkToken": sdktoken,
                        "dialogId":        dialogID,
                        "container":       player
                    });
                    $(function(){
                        $("#" + opts["playerdiv"]).dialog();
                    });
                    evt.preventDefault();
                });
            });
        }
    };
});
