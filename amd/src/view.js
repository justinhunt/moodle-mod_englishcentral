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
 * @module      mod_englishcentral/view
 * @category    output
 * @copyright   Gordon Bateson
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since       2.9
 */
define(["jquery", "jqueryui", "core/str", "mod_englishcentral/html"], function($, JUI, STR, HTML) {

    /** @alias module:mod_englishcentral/view */
    var VIEW = {};

    // cache full plugin name
    VIEW.plugin = "mod_englishcentral";

    VIEW.playercontainer = "id_playercontainer";
    VIEW.progresscontainer = "id_progresscontainer";

    // initialize string cache
    VIEW.str = {};

    // set up strings
    STR.get_strings([
        {"key": "addthisvideo",       "component": VIEW.plugin},
        {"key": "advanced",           "component": VIEW.plugin},
        {"key": "beginner",           "component": VIEW.plugin},
        {"key": "clickwhenfinished",  "component": VIEW.plugin},
        {"key": "confirmremovevideo", "component": VIEW.plugin},
        {"key": "description",        "component": VIEW.plugin},
        {"key": "entersearchterm",    "component": VIEW.plugin},
        {"key": "goals",              "component": VIEW.plugin},
        {"key": "intermediate",       "component": VIEW.plugin},
        {"key": "searchterm",         "component": VIEW.plugin},
        {"key": "topics",             "component": VIEW.plugin},
        {"key": "transcript",         "component": VIEW.plugin}
    ]).done(function(s) {
        var i = 0;
        VIEW.str.addthisvideo = s[i++];
        VIEW.str.advanced = s[i++];
        VIEW.str.beginner = s[i++];
        VIEW.str.clickwhenfinished = s[i++];
        VIEW.str.confirmremovevideo = s[i++];
        VIEW.str.description = s[i++];
        VIEW.str.entersearchterm = s[i++];
        VIEW.str.goals = s[i++];
        VIEW.str.intermediate = s[i++];
        VIEW.str.searchterm = s[i++];
        VIEW.str.topics = s[i++];
        VIEW.str.transcript = s[i++];
    });

    VIEW.init = function(opts) {

        // cache the opts passed from the server
        for (var i in opts) {
            VIEW[i] = opts[i];
        }

        $(".activity-title, .thumb-frame").click(function(evt) {
            VIEW.play_video(evt, this);
            evt.stopPropagation();
            evt.preventDefault();
            return false;
        });

        // make the video thumnails sortable
        $(".englishcentral_videos").sortable({
            "cursor": "move",
            "items": ".activity-thumbnail",
            "update": function(evt, ui) {
                var href = ui.item.find(".activity-title").prop("href");
                var data = {
                    "dialogId": VIEW.get_videoid_from_href(href),
                    "sortorder": ui.item.index() + 1
                };
                $.ajax({
                    "url": VIEW.viewajaxurl,
                    "data": {
                        "id": VIEW.cmid,
                        "data": data,
                        "action": "sortvideo",
                        "sesskey": VIEW.moodlesesskey
                    },
                    "dataType": "html",
                    "success": function(html) {
                        if (html) {
                            // probably an error message
                            $("#" + VIEW.playercontainer).html(html);
                        }
                    }
                });
            }
        });

        // make the video thumnails draggable
        //$(".activity-thumbnail").draggable({
        //});

        $(".removevideo").droppable({
            "drop": function(evt, ui) {
                if (confirm(VIEW.str.confirmremovevideo)) {
                    ui.draggable.remove();
                    var href = ui.draggable.find(".activity-title").prop("href");
                    var data = {
                        "dialogId": VIEW.get_videoid_from_href(href)
                    };
                    $.ajax({
                        "url": VIEW.viewajaxurl,
                        "data": {
                            "id": VIEW.cmid,
                            "data": data,
                            "action": "removevideo",
                            "sesskey": VIEW.moodlesesskey
                        },
                        "dataType": "html",
                        "success": function(html) {
                            if (html) {
                                // probably an error message
                                $("#" + VIEW.playercontainer).html(html);
                            }
                        }
                    });
                }
            }
        });

        $(".addvideo").click(function() {

            var container = document.getElementById(VIEW.playercontainer);
            if (container) {
                // remove previous EC player
                $("#" + VIEW.playercontainer).html("");
            } else {
                container = document.createElement("DIV");
                container.setAttribute("id", VIEW.playercontainer);
                $(VIEW.progresscontainer).after(container);
            }

            // create search box/results
            var html = "";
            html += HTML.tag("span", VIEW.str.searchterm, {
                "class": "search-prompt"
            });
            html += HTML.input("searchterm", "text", {
                "size": 30
            });
            html += HTML.input("searchbutton", "submit", {
                "value": "Go"
            });
            html = HTML.tag("div", html, {
                "class": "search-box"
            });
            html += HTML.tag("div", "", {
                "class": "search-results"
            });
            $("#" + VIEW.playercontainer).html(html);

            // add click event to button
            $("#id_searchbutton").click(function() {
                var term = $("#id_searchterm").val();
                var list = new RegExp("^\s*[0-9]+([, \\t\\r\\n]+[0-9]+)*\s*$");
                if (term == "") {
                    $(".search-results").html(VIEW.str.entersearchterm);
                } else if (term.match(list)) {
                    VIEW.fetch_videos(term);
                } else {
                    VIEW.search_videos(term);
                }
            });
        });
    };

    VIEW.play_video = function(evt, elm) {

        // remove previous player
        $("#" + VIEW.playercontainer).html("");

        // set handler for end of mode
        var setHandler = 'setOnModeEndHandler';
        if (window.ECSDK.setOnProgressEventHandler) {
            setHandler = 'setOnProgressEventHandler';
        }
        window.ECSDK[setHandler](function(data) {

            // we are only interested in the events
            // that affect the grade of this EC activity
            switch (data.eventType) {
                case "CompleteActivityWatch":
                case "LearnedWord":
                case "DialogLineSpeak":
                    break;

                case "DialogLineWatch":
                    var thumbframe = ".thumb-frame[href$=" + data.dialogID + "]";
                    if ($(thumbframe + " .watch-status").length) {
                        return false;
                    }
                    break;

                case "CompleteActivityLearn":
                case "CompleteActivitySpeak":
                    return false;

                case "StartActivityWatch":
                case "StartActivityLearn":
                case "TypedWord":
                case "StudiedWord":
                case "StartActivitySpeak":
                    return false;

                default:
                    // oops - an unexpected value
                    break;
            }

            // AJAX call to send the data.dialogID to the Moodle server
            // and receive the html for the updated Progress pie-charts
            $.ajax({
                "url": VIEW.viewajaxurl,
                "data": {
                    "id": VIEW.cmid,
                    "data": {
                        "dialogID": data.dialogID,
                        "sdktoken": VIEW.sdktoken
                    },
                    "action": "storeresults",
                    "sesskey": VIEW.moodlesesskey
                },
                "dataType": "html",
                "success": function(html) {
                    if (html.indexOf("englishcentral_progress") < 0) {
                        // probably an error message
                        $(".englishcentral_progress").html(html);
                    } else {
                        $(".englishcentral_progress").replaceWith(html);
                    }
                }
            }).then(function(){
                // AJAX call to send the data.dialogID to the Moodle server
                // and receive the html for the updated status of this video
                $.ajax({
                    "url": VIEW.viewajaxurl,
                    "data": {
                        "id": VIEW.cmid,
                        "data": {
                            "dialogID": data.dialogID,
                            "sdktoken": VIEW.sdktoken
                        },
                        "action": "showstatus",
                        "sesskey": VIEW.moodlesesskey
                    },
                    "dataType": "html",
                    "success": function(html) {
                        var thumb = $(".thumb-frame[href$=" + data.dialogID + "]");
                        thumb.find(".watch-status, .learn-status, .speak-status").remove();
                        thumb.find(".play-icon").after(html);
                    }
                });
            });
        });

        var dialogId = VIEW.get_videoid(elm);
        var completed = ".thumb-frame[href$=" +dialogId + "] .watch-status.completed";

        // set player options
        var options = {
            "partnerKey": VIEW.consumerkey,
            "partnerSdkToken": VIEW.sdktoken,
            "siteLanguage": VIEW.sitelanguage,
            "container": VIEW.playercontainer,
            "autoStart": $(completed).length,
            "interstitialsEnabled": true,
            "dialogId": dialogId,
            "learnMode": true,
            "speakMode": true,
            "quizMode": true,
            "width": 640
        };

        // set player width and height, in order to see WLS controls
        var w = window.outerWidth - $("#" + VIEW.playercontainer).offset().left - 24;
        if (options.width > w) {
            options.width = w;
        } else if (w > 1000) {
            options.width = 1000;
        } else {
            options.height = 655;
        }

        // initialize EC player
        window.ECSDK.loadWidget("player", options);
    };

    VIEW.fetch_videos = function(term) {
        var spacer = new RegExp("[, \\t\\r\\n]+", "g");
        var ids = term.replace(spacer, ",");
        $.ajax({
            "url": VIEW.fetchurl,
            "type": "GET",
            "data": {
                "dialogIDs": ids,
                "siteLanguage": VIEW.siteanguage,
                "page": "0",
                "pageSize": "20"
            },
            "dataType": "json",
            "headers": {
                "Accept": VIEW.accept1,
                "Authorization": VIEW.authorization,
                "Content-Type": "application/json"
            },
            "success": function(info) {
                VIEW.format_results(info);
            }
        });
    };

    VIEW.search_videos = function(term) {
        $.ajax({
            "url": VIEW.searchurl,
            "type": "GET",
            "data": {
                "term": term,
                "page": "0",
                "pageSize": "20"
            },
            "dataType": "json",
            "headers": {
                "Accept": VIEW.accept1,
                "Authorization": VIEW.authorization,
                "Content-Type": "application/json"
            },
            "success": function(info) {
                VIEW.format_results(info);
            }
        });
    };

    VIEW.format_results = function(info) {
        if ((!info) || (!info.results) || info.results.length == 0) {
            return "";
        }
        var html = "";
        var videoids = VIEW.get_videoids();
        for (var i = 0; i < info.results.length; i++) {
            // skip videos that are already displayed
            var id = info.results[i].value.dialogID.toString();
            if (videoids.indexOf(id) < 0) {
                html += VIEW.format_result(info.results[i]);
            }
        }
        $(".search-results").html(html);
        STR.get_string("xitemsfound", VIEW.plugin, info.count).done(function(s) {
            $(".search-results").prepend(HTML.tag("p", s));
        });
        for (var i = 0; i < info.results.length; i++) {
            var v = info.results[i].value;
            var data = {
                "dialogId": v.dialogID,
                "title": v.title,
                "duration": v.duration,
                "difficulty": v.difficulty,
                "dialogURL": v.dialogURL,
                "thumbnailURL": v.thumbnailURL
            };
            var id = "#id_add_video_" + data.dialogId;
            $(id).data(data);
            $(id).click(function(evt) {
                VIEW.add_video(evt, this);
            });
            $(id).siblings(".result-info").find(".icon").click(function() {
                var id = $(this).closest(".result-info")
                    .siblings(".result-add")
                    .prop("id");
                id = VIEW.get_videoid_from_id(id);
                var w = Math.min(640, window.innerWidth);
                var h = Math.min(480, window.innerHeight);
                var x = window.outerWidth / 2 + window.screenX - (w / 2);
                var y = window.outerHeight / 2 + window.screenY - (h / 2);
                var options = "width=" + w + ",height=" + h + ",top=" + y + ",left=" + x;
                var win = window.open(VIEW.videoinfourl + "/" + id, VIEW.targetwindow, options);
                if (win.focus) {
                    win.focus();
                }
            });
        }
    };

    VIEW.get_videoids = function() {
        var videoids = [];
        $(".englishcentral_videos .activity-title").each(function() {
            videoids.push(VIEW.get_videoid(this));
        });
        return videoids;
    };

    VIEW.get_videoid = function(elm) {
        return VIEW.get_videoid_from_href($(elm).prop("href"));
    };

    VIEW.get_videoid_from_href = function(href) {
        // sample href: https://www.qaenglishcentral.com/video/28864
        return href.replace(new RegExp("^.*/"), "");
    };

    VIEW.get_videoid_from_id = function(id) {
        // sample id: id_add_video_27323
        return id.replace(new RegExp("^.*_"), "");
    };

    VIEW.add_video = function(evt, elm) {
        $.ajax({
            "url": VIEW.viewajaxurl,
            "data": {
                "id": VIEW.cmid,
                "data": $(elm).data(),
                "action": "addvideo",
                "sesskey": VIEW.moodlesesskey
            },
            "dataType": "html",
            "success": function(html) {
                $(html).insertBefore(".removevideo").find("a").click(function(evt) {
                    VIEW.play_video(evt, this);
                    evt.stopPropagation();
                    evt.preventDefault();
                    return false;
                });
            }
        });

        // remove this "result-item" from "search-results"
        $(elm).closest(".result-item").fadeTo(1000, 0.01, function() {
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
        return HTML.tag("div", html, {
            "class": "result-item"
        });
    };

    VIEW.format_add = function(r) {
        var src = $(".addvideo img").prop("src").replace("addvideo", "add");
        var html = HTML.emptytag("img", {
            "src": src,
            "title": VIEW.str.addthisvideo
        });
        return HTML.tag("div", html, {
            "class": "result-add",
            "id": "id_add_video_" + r.value.dialogID
        });
    };

    VIEW.format_thumb = function(r) {
        var duration = r.value.duration.replace(new RegExp("^00:"), "");
        var html = "";
        html += HTML.starttag("a", {
            "class": "thumb-frame",
            "href": r.value.dialogURL,
            "style": "background-image: url('" + r.value.thumbnailURL + "')"
        });
        html += HTML.tag("div", r.value.difficulty, {
            "class": "result-difficulty"
        });
        html += HTML.tag("div", duration, {
            "class": "result-duration"
        });
        html += HTML.endtag("a");
        return HTML.tag("div", html, {
            "class": "result-thumb"
        });
    };

    VIEW.format_info = function(r) {
        var html = "";
        var src = $(".addvideo img").prop("src")
            .replace("addvideo", "i/info")
            .replace("mod_englishcentral", "core");
        var img = HTML.emptytag("img", {
            "src": src,
            "class": "icon"
        });
        html += HTML.tag("h2", r.value.title + img, {
            "class": "result-title"
        });
        html += VIEW.format_details(r);
        return HTML.tag("div", html, {
            "class": "result-info"
        });
    };

    VIEW.format_details = function(r) {
        var html = "";
        html += VIEW.format_goalstopics(r.value.goals, r.value.topics);
        html += VIEW.format_description(r.value.description);
        html += VIEW.format_transcript(r.highlights.transcript);
        return HTML.tag("dl", html, {
            "class": "result-details"
        });
    };

    VIEW.format_goalstopics = function(goals, topics) {
        var txt = [];
        if (goals && goals.length) {
            for (var i = 0; i < goals.length; i++) {
                txt.push(goals[i].title);
            }
        }
        if (topics && topics.length) {
            for (var i = 0; i < topics.length; i++) {
                txt.push(topics[i].name);
            }
        }
        if (txt.length == 0) {
            return "";
        }
        return VIEW.format_detail("topics", txt.join(", "));
    };

    VIEW.format_description = function(description) {
        if (description && description.length) {
            return VIEW.format_detail("description", description);
        }
        return "";
    };

    VIEW.format_transcript = function(transcript) {
        if (transcript && transcript.length) {
            var dots = "...";
            var slashes = new RegExp("//", "g");
            return VIEW.format_detail("transcript", dots + transcript[0].replace(slashes, dots) + dots);
        }
        return "";
    };

    VIEW.format_detail = function(type, value) {
        var html = "";
        html += HTML.tag("dt", VIEW.str[type], {
            "class": "result-label " + type
        });
        html += HTML.tag("dd", value, {
            "class": "result-value " + type
        });
        return html;
    };

    return VIEW;
});
