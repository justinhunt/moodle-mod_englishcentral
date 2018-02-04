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
 * @since       2.9
 */
define(["jquery", "core/str", "mod_englishcentral/html", "mod_englishcentral/ecsdk"], function($, STR, HTML, ECSDK) {

    /** @alias module:mod_englishcentral/view */
    var VIEW = {};

    /* set up strings */
    VIEW.plugin = "mod_englishcentral";
    VIEW.str = {};
    STR.get_strings([
        // TODO: add other strings here as required
        {"key" : 'searchterm', "component" : VIEW.plugin},
    ]).done(function(s) {
        VIEW.str.searchterm = s[0];
    });

    VIEW.init = function(opts) {

        // cache the opts passed from the server
        for (var i in opts) {
            VIEW[i] = opts[i];
        }

        $(".activity-title, .thumb-frame").click(function(evt){
            VIEW.play_video(evt, this);
        });

        $(".addvideos").click(function(){

            // remove previous EC player
            $("#" + VIEW.playercontainer).html("");

            // create search box/results
            var html = "";
            html += HTML.tag("span", VIEW.str.searchterm, {"class" : "search-prompt"});
            html += HTML.input("searchterm", "text", {"size" : 30});
            html += HTML.input("searchbutton", "submit", {"value" : "Go"});
            html  = HTML.tag("div", html, {"class" : "search-box"});
            html += HTML.tag("div", "", {"class" : "search-results"});
            $("#" + VIEW.playercontainer).html(html);

            // add click event to button
            $("#id_searchbutton").click(function(){
                var term = $("#id_searchterm").val();
                if (term=="") {
                    $(".search-results").html("Please enter one or more search term.");
                } else {
                    VIEW.search_videos(term);
                }
            });
        });
    };

    VIEW.play_video = function(evt, elm) {

        // remove previous player
        $("#" + VIEW.playercontainer).html("");


        // initialize EC player
        window.ECSDK.loadWidget("player", {
            "partnerSdkToken": VIEW.sdktoken,
            "partnerKey":      VIEW.consumerkey,
            "container":       VIEW.playercontainer,
            "dialogId":        VIEW.get_videoid(elm)
        });

        // disable normal event behavior/propagation
        evt.preventDefault();
        evt.stopPropagation();
    };

    VIEW.search_videos = function(term) {
        $.ajax({
            "url" : VIEW.searchurl,
            "type" : "GET",
            "data" : {"term"       : term,
                      "page"       : "0",
                      "pageSize"   : "20"},
            "dataType" : "json",
            "headers" : {"Accept": VIEW.accept,
                         "Authorization" : VIEW.authorization,
                         "Content-Type": "application/json"},
            "success" : function(info){
                VIEW.format_results(info);
            }
        });
    };

    VIEW.format_results = function(info) {
        var videoids = VIEW.get_videoids();
        var html = "";
        html += HTML.tag("p", info.count + " items found");
        for (var i=0; i<info.results.length; i++) {
            // skip videos that are already displayed
            var id = info.results[i].value.dialogID.toString();
            if (videoids.indexOf(id) < 0) {
                html += VIEW.format_result(info.results[i]);
            }
        }
        $(".search-results").html(html);
        for (var i=0; i<info.results.length; i++) {
            var v = info.results[i].value;
            var data = {"dialogId"     : v.dialogID,
                        "title"        : v.title,
                        "duration"     : v.duration,
                        "difficulty"   : v.difficulty,
                        "dialogURL"    : v.dialogURL,
                        "thumbnailURL" : v.thumbnailURL};
            var id = "#id_add_video_" + data.dialogId;
            $(id).data(data);
            $(id).click(function(evt){
                VIEW.add_video(evt, this);
            });
        }
    };

    VIEW.get_videoids = function() {
        var videoids = [];
        $(".englishcentral_videos .activity-title").each(function(){
            videoids.push(VIEW.get_videoid(this));
        });
        return videoids;
    };

    VIEW.get_videoid = function(elm) {
        // sample href: https://www.qaenglishcentral.com/video/28864
        return $(elm).prop("href").replace(new RegExp("^.*/"), "")
    };

    VIEW.add_video = function(evt, elm) {
        $.ajax({
            "url" : VIEW.addvideourl,
            "data" : {"id"      : VIEW.cmid,
                      "data"    : $(elm).data(),
                      "action"  : "addvideo",
                      "sesskey" : VIEW.moodlesesskey},
            "dataType" : "html",
            "success" : function(html){
                $(html).insertBefore(".addvideos").find("a").click(function(evt){
                    VIEW.play_video(evt, this);
                });
            }
        });

        // remove this "result-item" from "search-results"
        $(elm).closest(".result-item").fadeTo(1000, 0.01, function(){
            $(this).slideUp(150, function() {
                $(this).remove();
            });
        });
        // https://stackoverflow.com/questions/1807187/how-to-remove-an-element-slowly-with-jquery
    };

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

    VIEW.format_result = function(r) {
        var html = "";
        html += VIEW.format_add(r);
        html += VIEW.format_thumb(r);
        html += VIEW.format_info(r);
        return HTML.tag("div", html, {"class" : "result-item"});
    };

    VIEW.format_add = function(r) {
        var html = HTML.emptytag("img", {"src" : $(".addvideos .icon").prop("src"),
                                         "title" : "Add this video"});
        return HTML.tag("div", html, {"class" : "result-add",
                                      "id" : "id_add_video_" + r.value.dialogID});
    };

    VIEW.format_thumb = function(r) {
        var duration = r.value.duration.replace(new RegExp("^00:"), "");
        var html = "";
        html += HTML.starttag("a", {"class" : "thumb-frame",
                                    "href"  : r.value.dialogURL,
                                    "style" : "background-image: url('" + r.value.thumbnailURL + "')"});
        html += HTML.tag("div", r.value.difficulty, {"class" : "result-difficulty"});
        html += HTML.tag("div", duration, {"class" : "result-duration"});
        html += HTML.endtag("a");
        return HTML.tag("div", html, {"class" : "result-thumb"});
    };

    VIEW.format_info = function(r) {
        var html = "";
        html += HTML.tag("h2", r.value.title, {"class" : "result-title"});
        html += VIEW.format_details(r);
        return HTML.tag("div", html, {"class" : "result-info"});
    };

    VIEW.format_details = function(r) {
        html =  "";
        html += VIEW.format_topics(r.value.topics);
        html += VIEW.format_description(r.value.description);
        html += VIEW.format_transcript(r.highlights.transcript);
        return HTML.tag("dl", html, {"class" : "result-details"});
    };

    VIEW.format_topics = function(topics) {
        var txt = [];
        for (var i=0; i<topics.length; i++) {
            txt.push(topics[i].name);
        }
        if (txt.length==0) {
            return "";
        }
        return VIEW.format_detail("Topics", txt.join(", "));
    };

    VIEW.format_description = function(description) {
        if (description==null || description=="") {
            return "";
        }
        return VIEW.format_detail("Description", description);
    };

    VIEW.format_transcript = function(transcript) {
        if (transcript==null || transcript.length==0) {
            return "";
        }
        var dots = "...";
        var slashes = new RegExp("//", "g");
        return VIEW.format_detail("Transcript", dots + transcript[0].replace(slashes, dots) + dots);
    };

    VIEW.format_detail = function(label, value) {
        var html = "";
        html += HTML.tag("dt", label, {"class" : "result-label"});
        html += HTML.tag("dd", value, {"class" : "result-value"});
        return html;
    };

    return VIEW;
});
