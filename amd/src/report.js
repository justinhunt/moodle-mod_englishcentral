// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the term of the GNU General Public License as published by
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
 * load the EnglishCentral player
 *
 * @module      mod_englishcentral/report
 * @category    output
 * @copyright   Gordon Bateson
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since       2.9
 */
define(["jquery", "jqueryui", "core/str"], function($, JUI, STR) {

    /** @alias module:mod_englishcentral/report */
    var REPORT = {};

    // cache full plugin name
    REPORT.plugin = "mod_englishcentral";

    // initialize string cache
    REPORT.str = {};

    // set up strings
    STR.get_strings([
        {"key": "videoswatched",    "component": REPORT.plugin},
        {"key": "wordslearned",     "component": REPORT.plugin},
        {"key": "linesspoken",      "component": REPORT.plugin},
        {"key": "overallprogress",  "component": REPORT.plugin}
    ]).done(function(s) {
        var i = 0;
        REPORT.str.videoswatched = s[i++];
        REPORT.str.wordslearned = s[i++];
        REPORT.str.linesspoken = s[i++];
        REPORT.str.overallprogress = s[i++];
    });

    REPORT.init = function() {
        if (window.outerWidth > 1000) {
            $(".bars .text").each(function(){
                $(this).text($(this).prop("title"));
            });
        }
    };

    return REPORT;
});
