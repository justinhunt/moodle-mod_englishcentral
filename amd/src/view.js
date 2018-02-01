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
define(['jquery', 'mod_englishcentral/html'], function($, HTML) {
    /** @alias module:mod_englishcentral/view */
    var t = {

        "init": function (opts) {

            // cache the opts passed from the server
            for (var i in opts) {
                t[i] = opts[i];
            }

            // regexp to remove leading path in the URL for an EC video
            // e.g. https://www.qaenglishcentral.com/video/28864
            var prefix = new RegExp("^.*/");

            $(".activity-title, .thumb-frame").each(function(){
                $(this).click(function(evt){

                    // remove previous player
                    $("#" + t.playercontainer).html('');

                    // initialize EC player
                    window.ECSDK.loadWidget("player", {
                        "partnerSdkToken": t.sdktoken,
                        "partnerKey":      t.consumerkey,
                        "container":       t.playercontainer,
                        "dialogId":        $(this).prop("href").replace(prefix, "")
                    });

                    // disable normal event behavior/propagation
                    evt.preventDefault();
                    evt.stopPropagation();
                });
            });

            $(".addvideos").click(function(){

                // remove previous EC player
                $("#" + t.playercontainer).html("");

                // create search box/results
                var html = "";
                html += HTML.tag("span", "Search term", {"class" : "searchprompt"});
                html += HTML.input("searchterm", "text", {"size" : 30});
                html += HTML.input("searchbutton", "submit", {"value" : "Go"});
                html  = HTML.tag("div", html, {"class" : "searchbox"});
                html += HTML.tag("div", "", {"class" : "searchresults"});
                $("#" + t.playercontainer).html(html);

                // add click event to button
                $("#id_searchbutton").click(function(){
                    var term = $("#id_searchterm").val();
                    if (term=="") {
                        $(".searchresults").html("Please enter one or more search term.");
                    } else {
                        $.ajax({
                            "url" : t.searchurl,
                            "type" : "GET",
                            "data" : {"term"       : term,
                                      "pageSize"   : "20",
                                      "page"       : "0"},
                            "dataType" : "json",
                            "headers" : {"Accept": t.accept,
                                         "Authorization" : t.authorization,
                                         "Content-Type": "application/json"},
                            "success" : function(info){
                                window.console.log(JSON.stringify(info, null, 4));
                                var html = '';
                                html += HTML.tag("p", info.count + " items found");
                                for (var i=0; i<info.results.length; i++) {
                                    html += t.format_result(info.results[i]);
                                }
                                $(".searchresults").html(html);
                            }
                        });
                    }
                });
            });
        },

        "format_result": function (r) {
            // see renderer->show_video() for ideas about how to format
            var html = "";

            html += HTML.starttag("div", {"class" : "result-add"});
            html += HTML.emptytag('img', {"src" : $(".addvideos .icon").prop("src"),
                                          "title" : "Add this video"});
            html += HTML.endtag("div"); // result-add

            html += HTML.starttag("div", {"class" : "result-thumb"});
            html += HTML.emptytag('a', {"style" : "background-image: url('" + r.value.thumbnailURL + "')",
                                        "class" : "thumb-frame"});
            html += HTML.endtag("div"); // result-thumb

            html += HTML.starttag("div", {"class" : "result-info"});
            html += HTML.tag("h2", r.value.title, {"class" : "result-title"});
            html += t.format_topics(r.value.topics);
            html += t.format_description(r.value.description);
            html += t.format_transcript(r.highlights.transcript);
            html += HTML.endtag("div"); // result-info

            return html;
            //"dialogID": 11875,
            //"title": "The Japanese Are Very Important",
            //"description": "A global economy analyst discusses globalization and its influence on countries like Japan.",
            //"difficulty": 4,
            //"duration": "00:02:07",
            //"dateModified": "2017-08-03T11:20:30.000Z",
            //"dateFirstPublished": "2011-05-12T17:04:26.000Z",
            //"popularityWeight": 1.76737,
            //"slowSpeakAudioURL": "https://cdna.qaenglishcentral.com/dialogs/11875/slowspeakaudio_11875_84768.mp3",
            //"dialogURL": "https://www.qaenglishcentral.com/video/11875",
            //"thumbnailURL": "https://cdna.qaenglishcentral.com/dialogs/11875/thumb_11875_20160719065106.jpg",
            //"featurePictureURL": "https://cdna.qaenglishcentral.com/dialogs/11875/featureddialog_11875_20160719065115.jpg",
            //"demoPictureURL": "https://cdna.qaenglishcentral.com/dialogs/11875/demopicture_11875_20160719065122.jpg",
            //"videoDetailsURL": "https://www.qaenglishcentral.com/videodetails/11875",
            //"seriesThumbnailURL": "https://cdna.qaenglishcentral.com/dialogs/11875/dialogseriesthumbnail_11875_20160719065111.jpg",
            //"dialogM4aAudioURL": "https://cdna.qaenglishcentral.com/dialogs/11875/audio_11875_20130527081953.m4a",
            //"smallVideoURL": "https://cdna.qaenglishcentral.com/dialogs/11875/videomobile_11875_20140205180008.mp4",
            //"mediumVideoURL": "https://cdna.qaenglishcentral.com/dialogs/11875/videoslowconn_11875_20131130050102.mp4",
            //"largeVideoURL": "https://cdna.qaenglishcentral.com/dialogs/11875/videoh264_11875_20130527081953.mp4",
            //"promotionalDialog": false,
        },

        "format_topics" : function(topics) {
            var txt = [];
            for (var i=0; i<topics.length; i++) {
                txt.push(topics[i].name);
            }
            if (txt.length==0) {
                return "";
            }
            return t.format_item("Topics", txt.join(", "));
        },

        "format_description" : function(description) {
            if (description==null || description=="") {
                return "";
            }
            return t.format_item("Description", description);
        },

        "format_transcript" : function(transcript) {
            if (transcript==null || transcript.length==0) {
                return "";
            }
            var dots = "...";
            var slashes = new RegExp("//", "g");
            return t.format_item("Transcript", dots + transcript[0].replace(slashes, dots) + dots);
        },

        "format_item" : function(label, value) {
            var html = "";
            html += HTML.tag("span", label, {"class" : "result-label"});
            html += HTML.tag("span", value, {"class" : "result-value"});
            return HTML.tag("div", html, {"class" : "result-item"});
        }
    };
    return t;
});
