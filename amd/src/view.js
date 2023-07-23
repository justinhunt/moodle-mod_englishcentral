/* eslint-disable max-len */
/* eslint-disable no-console */
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
define(["jquery", "js/jquery-ui.js", "core/log", "core/str", "mod_englishcentral/html"], function ($, JUI, LOG, STR, HTML) {

    /** @alias module:mod_englishcentral/view */
    var VIEW = {};

    // Cache full plugin name
    VIEW.plugin = "mod_englishcentral";

    // Define DOM element names
    VIEW.playercontainer = "id_playercontainer";
    VIEW.progresscontainer = "id_progresscontainer";
    VIEW.searchcontainer = "id_searchcontainer";

    // Initialize search params
    VIEW.searchpage = 0;
    VIEW.searchsize = 20;
    VIEW.searchterm = "";
    VIEW.searchlevel = "";

    // Initialize string cache
    VIEW.str = {};

    // Set up strings
    STR.get_strings([
        { "key": "addthisvideo", "component": VIEW.plugin },
        { "key": "advanced", "component": VIEW.plugin },
        { "key": "beginner", "component": VIEW.plugin },
        { "key": "confirmremovevideo", "component": VIEW.plugin },
        { "key": "copyright", "component": VIEW.plugin },
        { "key": "description", "component": VIEW.plugin },
        { "key": "duration", "component": "search" },
        { "key": "error", "component": "error" },
        { "key": "goals", "component": VIEW.plugin },
        { "key": "hideadvanced", "component": "form" },
        { "key": "info", "component": "moodle" },
        { "key": "intermediate", "component": VIEW.plugin },
        { "key": "level", "component": VIEW.plugin },
        { "key": "ok", "component": "moodle" },
        { "key": "search", "component": "moodle" },
        { "key": "showadvanced", "component": "form" },
        { "key": "topics", "component": VIEW.plugin },
        { "key": "transcript", "component": VIEW.plugin },
        { "key": "videosearch", "component": VIEW.plugin },
        { "key": "videosearchhelp", "component": VIEW.plugin },
        { "key": "videosearchprompt", "component": VIEW.plugin },
        { "key": "videodetails", "component": VIEW.plugin }
    ]).done(function (s) {
        var i = 0;
        VIEW.str.addthisvideo = s[i++];
        VIEW.str.advanced = s[i++];
        VIEW.str.beginner = s[i++];
        VIEW.str.confirmremovevideo = s[i++];
        VIEW.str.copyright = s[i++];
        VIEW.str.description = s[i++];
        VIEW.str.duration = s[i++];
        VIEW.str.error = s[i++];
        VIEW.str.goals = s[i++];
        VIEW.str.hideadvanced = s[i++];
        VIEW.str.info = s[i++];
        VIEW.str.intermediate = s[i++];
        VIEW.str.level = s[i++];
        VIEW.str.ok = s[i++];
        VIEW.str.search = s[i++];
        VIEW.str.showadvanced = s[i++];
        VIEW.str.topics = s[i++];
        VIEW.str.transcript = s[i++];
        VIEW.str.videosearch = s[i++];
        VIEW.str.videosearchhelp = s[i++];
        VIEW.str.videosearchprompt = s[i++];
        VIEW.str.videodetails = s[i++];
    });

    VIEW.init = function (opts) {

        // Cache basic options received from Moodle, e.g. cmid, sesskey, ajax URL.
        // These give us just enough information to get more options asynchronously.
        for (var i in opts) {
            VIEW[i] = opts[i];
        }

        // Get more options asynchronously from Moodle
        VIEW.getoptions = $.Deferred();
        $.ajax({
            "url": VIEW.viewajaxurl,
            "type": "POST",
            "data": {
                "id": VIEW.cmid,
                "action": "getoptions",
                "sesskey": VIEW.moodlesesskey
            },
            "dataType": "json",
            "success": function (opts) {
                for (var i in opts) {
                    VIEW[i] = opts[i];
                }
                VIEW.getoptions.resolve();
            }
        });

        // Ensure ECSDK is fully loaded before it is used
        VIEW.ECSDK = $.Deferred();
        $.when(VIEW.getoptions).done(function () {
            // Set appropriate SDK url for specified SDK version
            if (VIEW.sdkmode == 1) {
                // Development mode
                VIEW.sdkurl = "https://www.qaenglishcentral.com";
            } else {
                // Production mode
                VIEW.sdkurl = "https://www.englishcentral.com";
            }
            if (VIEW.sdkversion == "JSDK2") {
                // JSDK2 (available until Sept 2018)
                VIEW.sdkurl += "/partnersdk/sdk.js";
            } else {
                // JSDK3 (available from July 2017)
                VIEW.sdkurl += "/dist/sdk/sdk.js";
            }
            $.ajax({
                url: VIEW.sdkurl,
                dataType: "script",
                cache: true
            }).done(function () {
                VIEW.ECSDK.resolve(window.ECSDK);
            });

            $(".activity-title").each(function () {
                var url = this.dataset.videoDetailsUrl;
                if (url) {
                    // Create the icon
                    var src = $(".removevideo img").prop("src")
                        .replace("removevideo", "i/info")
                        .replace("mod_englishcentral", "core");
                    var icon = HTML.emptytag("img", {
                        "src": src,
                        "class": "icon infoicon",
                        "title": VIEW.str.info
                    });
                    // Convert icon to a clickable link.
                    icon = $(HTML.tag("a", icon, {
                        "href": url,
                        "style": "position: absolute;"
                    }));
                    icon.click(function (evt) {
                        VIEW.open_window(this.href);
                        evt.stopPropagation();
                        evt.preventDefault();
                        return false;
                    });
                    // Make room for the icon, and then insert it.
                    $(this).css({ "margin-left": "0px" }).before(icon);
                }
            });
        });

        /* Composing video placeholder on initial load by getting data from the first element loaded in the video thumbnails */
        var activityThumbnail = $('.activity-thumbnail').first();
        var thumbOutline = activityThumbnail.children('.thumb-outline');
        var videoPlaceholderVideo = $('.video-placeholder-video');

        // Getting and setting the title
        var activityTitle = thumbOutline.children('.activity-title').text();
        var videoPlaceholderTextTitle = $('.video-placeholder-text-title');
        videoPlaceholderTextTitle.text(activityTitle);

        // Getting and setting the difficulty level
        var activityLevel = thumbOutline.find('.difficulty-level').text();
        var levelNumber = activityLevel.slice(-1);
        var videoPlaceholderTextLabel = $('.video-placeholder-text-label');
        videoPlaceholderTextLabel.text(levelNumber);
        if (levelNumber == 1 || levelNumber == 2) {
            videoPlaceholderTextLabel.addClass('label-green');
        } else if (levelNumber == 3 || levelNumber == 4) {
            videoPlaceholderTextLabel.addClass('label-blue');
        } else {
            videoPlaceholderTextLabel.addClass('label-black');
        }

        // Getting and setting the difficulty label
        var difficultyLabel = thumbOutline.find('.difficulty-label').text();
        var videoPlaceholderTextLevel = $('.video-placeholder-text-level');
        videoPlaceholderTextLevel.text(difficultyLabel);

        if (levelNumber == 1 || levelNumber == 2) {
            videoPlaceholderTextLevel.addClass('level-green');
        } else if (levelNumber == 3 || levelNumber == 4) {
            videoPlaceholderTextLevel.addClass('level-blue');
        } else {
            videoPlaceholderTextLevel.addClass('level-black');
        }

        // Getting and setting the video description
        var activityDescription = thumbOutline.find('.thumb-frame').attr("description");
        var videoPlaceholderTextDescription = $('.video-placeholder-text-description');
        videoPlaceholderTextDescription.text(activityDescription);

        // Getting and setting the topics
        var activityTopics = thumbOutline.find('.thumb-frame').attr("topics");
        var videoPlaceholderTextTags = $('.video-placeholder-text-tags');
        videoPlaceholderTextTags.text(activityTopics);

        // Creating, appending, getting and setting the image
        var activityImage = thumbOutline.children('.thumb-frame').attr("data-demopicurl");
        var activityVideoId = thumbOutline.children('.thumb-frame').attr("data-url");
        var $newImage = $("<img>");
        $newImage.attr("src", activityImage);
        $newImage.attr("data-url", activityVideoId);
        $newImage.attr("class", "video-placeholder-video-image");
        videoPlaceholderVideo.append($newImage);

        // Setting the social buttons
        var fauxSocialButtons = $('.video-placeholder-text-social');
        fauxSocialButtons.attr("data-url", activityVideoId);

        // Setting the text progress
        var videoPlaceholderTextProgress = $('.video-placeholder-text-progress');
        videoPlaceholderTextProgress.attr("data-url", activityVideoId);

        // Creating, appending, getting and setting the big play button
        var $bigPlayButton = $("<img>");
        $bigPlayButton.attr("src", 'pix/big-play-icon.svg');
        $bigPlayButton.attr("data-url", activityVideoId);
        $bigPlayButton.attr("class", "video-placeholder-video-big-play-button");
        videoPlaceholderVideo.append($bigPlayButton);


        $(".activity-title, .thumb-frame, .video-placeholder-video-image, .video-placeholder-video-big-play-button, .video-placeholder-text-social, .video-placeholder-text-progress")
            .click(function (evt) {
                VIEW.play_video(evt, this);
                $(".video-placeholder").hide();
                $(".faux-loader").show();
                $(".faux-loader").delay(2000).fadeOut('slow');
                evt.stopPropagation();
                evt.preventDefault();
                return false;
            });

        // Make the video thumnails sortable
        $(".englishcentral_videos").sortable({
            "cursor": "grabbing",
            "items": ".activity-thumbnail",
            "update": function (evt, ui) {
                var url = ui.item.find(".activity-title").data("url");
                var data = {
                    "dialogId": VIEW.get_videoid_from_url(url),
                    "sortorder": ui.item.index() + 1
                };
                $.ajax({
                    "url": VIEW.viewajaxurl,
                    "type": "POST",
                    "data": {
                        "id": VIEW.cmid,
                        "data": data,
                        "action": "sortvideo",
                        "sesskey": VIEW.moodlesesskey
                    },
                    "dataType": "html",
                    "success": function (html) {
                        if (html) {
                            // Probably an error message
                            $("#" + VIEW.playercontainer).html(html);
                        }
                    }
                });
            }
        });

        // Make the video thumnails draggable
        // $(".activity-thumbnail").draggable({
        // });

        $(".removevideo").droppable({
            "drop": function (evt, ui) {
                if (confirm(VIEW.str.confirmremovevideo)) {
                    ui.draggable.remove();
                    var url = ui.draggable.find(".activity-title").data("url");
                    var data = {
                        "dialogId": VIEW.get_videoid_from_url(url)
                    };
                    $.ajax({
                        "url": VIEW.viewajaxurl,
                        "type": "POST",
                        "data": {
                            "id": VIEW.cmid,
                            "data": data,
                            "action": "removevideo",
                            "sesskey": VIEW.moodlesesskey
                        },
                        "dataType": "html",
                        "success": function (html) {
                            if (html) {
                                // Probably an error message
                                $("#" + VIEW.playercontainer).html(html);
                            }
                        }
                    });
                }
            }
        });

        // Override standard form submit action
        $("#" + VIEW.searchcontainer + " .search-form").submit(function (evt) {

            // Remove player and previous search results
            VIEW.clear_searchresults();

            // RegExp to match a list of comma-separated values
            var list = new RegExp("^\s*[0-9]+([, \\t\\r\\n]+[0-9]+)*\s*$");

            var term = $("#id_searchterm").val();
            var level = $("[name='level[]']:checked").map(function () {
                return this.value;
            }).get().join(',');
            if (term == "") {
                $(".search-results").html(VIEW.str.videosearchhelp);
            } else if (term.match(list)) {
                VIEW.fetch_videos(null, null, term, level);
            } else {
                VIEW.search_videos(null, null, term, level);
            }
            evt.preventDefault();
        });

        // Add event event handler to show/hide advanced settings
        $("#" + VIEW.searchcontainer + " .search-advanced").click(function () {
            if ($(this).text() == VIEW.str.showadvanced) {
                $(this).text(VIEW.str.hideadvanced);
            } else {
                $(this).text(VIEW.str.showadvanced);
            }
            $("#" + VIEW.searchcontainer + " .search-fields").find("dt:not(.visible), dd:not(.visible)").toggle();
        });

        // Setting 'Add video' interaction mechanics
        var idSearchContainer = $('#id_searchcontainer');
        var fauxsearchButton = $('#faux-search-button');
        var closeSearchButton = $('#close-search-button');
        var searchBox = $('.search-box');
        var searchResults = $('.search-results');

        if (fauxsearchButton || closeSearchButton) {

            // When clicking on the "Add video" button, it displays the full search view.
            fauxsearchButton.click(function () {
                searchBox.css("display", "flex");
                searchResults.css("display", "flex");
                idSearchContainer.css({ "width": "auto", "height": "auto" });
                fauxsearchButton.css("display", "none");
                closeSearchButton.css("display", "flex");
            });
            // When clicking on the times closing button, it hides the full search view and returns to the initial state.
            closeSearchButton.click(function () {
                searchBox.css("display", "none");
                searchResults.css("display", "none");
                idSearchContainer.css({ "width": "178px", "height": "110px" });
                fauxsearchButton.css("display", "flex");
                closeSearchButton.css("display", "none");
            });
        }

    };

    VIEW.play_video = function (evt, elm) {

        // Make sure the ECSDK object is available
        // and also pass through evt and elm
        $.when(VIEW.ECSDK, evt, elm).done(function (ecsdk, evt, elm) {

            // Remove player and previous search results
            VIEW.clear_player_and_searchresults();

            // Set handler for end of mode
            var setHandler = "";
            if (ecsdk.setOnProgressEventHandler) {
                setHandler = "setOnProgressEventHandler";
            } else if (ecsdk.setOnModeEndHandler) {
                setHandler = "setOnModeEndHandler";
            }
            if (setHandler) {
                ecsdk[setHandler](function (data) {
                    // TODO: remove use of LOG in production sites.
                    LOG.debug(data);
                    switch (data.eventType) {
                        case "CompleteActivityWatch":
                        case "LearnedWord":
                        case "DialogLineSpeak":
                            break;

                        case "DialogLineWatch":
                            var thumbframe = ".thumb-frame[data-url$=" + data.dialogID + "]";
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
                            // Oops - an unexpected value
                            break;
                    }

                    // AJAX call to send the data.dialogID to the Moodle server
                    // and receive the html for the updated Progress pie-charts
                    $.ajax({
                        "url": VIEW.viewajaxurl,
                        "type": "POST",
                        "data": {
                            "id": VIEW.cmid,
                            "data": {
                                "dialogID": data.dialogID,
                                "sdktoken": VIEW.sdktoken
                            },
                            "action": "storeresults",
                            "sesskey": VIEW.moodlesesskey
                        },
                        "dataType": "html"
                    }).done(function (html) {

                        // Is this a PHP error reported as a JSON string?
                        // e.g. a session timeout.
                        if (html.match(new RegExp("^\\{.*\\}$"))) {
                            html = html.replace(new RegExp("\\n", "g"), "\\\\n");
                            html = JSON.parse(html);
                            if (html.error) {
                                html = html.error.replace(new RegExp("\\n", "g"), "<br>");
                            } else {
                                html = html.toString().substr(0, 200);
                                html = 'Sorry, there was an unknown error on the server.\n' + html;
                            }

                            if (VIEW.dialog) {
                                if (VIEW.dialog.dialog("isOpen")) {
                                    VIEW.dialog.dialog("close");
                                }
                            } else {
                                VIEW.dialog = $("<div></div>").addClass("dialog");
                                VIEW.dialog.dialog({ "autoOpen": false });
                            }
                            VIEW.dialog.html(html);

                            VIEW.dialog.dialog("option", "title", VIEW.str.error);
                            VIEW.dialog.dialog("option", "buttons", [{
                                "text": VIEW.str.ok,
                                // "icon": "ui-icon-check",
                                "click": function () {
                                    $(this).dialog("close");
                                    var href = VIEW.viewajaxurl.replace(".ajax", "");
                                    window.location.href = href + "?id=" + VIEW.cmid;
                                }
                            }]);
                            VIEW.dialog.dialog("open");
                            return false;
                        }

                        if (html.indexOf("englishcentral_progress") < 0) {
                            // Probably an error message
                            $(".englishcentral_progress").html(html);
                        } else {
                            $(".englishcentral_progress").replaceWith(html);
                        }

                        // AJAX call to send the data.dialogID to the Moodle server
                        // and receive the html for the updated status of this video
                        $.ajax({
                            "url": VIEW.viewajaxurl,
                            "type": "POST",
                            "data": {
                                "id": VIEW.cmid,
                                "data": {
                                    "dialogID": data.dialogID,
                                    "sdktoken": VIEW.sdktoken
                                },
                                "action": "showstatus",
                                "sesskey": VIEW.moodlesesskey
                            },
                            "dataType": "html"
                        }).done(function (html) {
                            var thumb = $(".thumb-frame[data-url$=" + data.dialogID + "]");
                            thumb.find(".watch-status, .learn-status, .speak-status").remove();
                            thumb.find(".play-icon").after(html);
                        });
                    });
                });
            }

            var dialogId = VIEW.get_videoid(elm);
            var completed = ".thumb-frame[data-url$=" + dialogId + "] .watch-status.completed";

            // Set player options (JSDK2 and JSDK3)
            var options = {
                "partnerKey": VIEW.consumerkey,
                "partnerSdkToken": VIEW.sdktoken,
                "container": VIEW.playercontainer,
                "dialogId": dialogId,
                "settings": VIEW.settings
            };

            if (VIEW.sdkversion == "JSDK3") {
                // JSDK3 (from July 2018)
                options.lang = VIEW.sitelanguage;
            } else {
                // JSDK2 (until Sept 2018)
                options.siteLanguage = VIEW.sitelanguage;
                options.autoStart = $(completed).length;
                options.activityPanelEnabled = false;
                options.interstitialsEnabled = true;
                options.learnMode = true;
                options.speakMode = true;
                options.quizMode = true;
                // Options.newWindow = (is_iOS ? true : false);
                // options.goLiveMode = true;
                options.width = 640;

                // Set player width and height, in order to see WLS controls
                var w = window.outerWidth - $("#" + VIEW.playercontainer).offset().left - 24;
                if (options.width > w) {
                    options.width = w;
                } else if (w > 1000) {
                    options.width = 1000;
                } else {
                    options.height = 655;
                }
            }
LOG.debug('options: ' + JSON.stringify(options));
            // Initialize EC player
            ecsdk.loadWidget("player", options);
        });
    };

    VIEW.clear_searchresults = function () {
        $("#" + VIEW.searchcontainer + " .search-results").html("");
    };

    VIEW.clear_player_and_searchresults = function () {
        $("#" + VIEW.playercontainer).html("");
        $("#" + VIEW.searchcontainer + " .search-results").html("");
    };

    VIEW.fetch_videos = function (page, size, term, level) {
        var spacer = new RegExp("[, \\t\\r\\n]+", "g");
        var ids = term.replace(spacer, ",");
        VIEW.set_search_params(page, size, ids, level);
        var data = {
            "dialogIDs": ids,
            "page": VIEW.searchpage,
            "pageSize": VIEW.searchsize,
            "siteLanguage": VIEW.siteanguage
        };
        $.ajax({
            "url": VIEW.fetchurl,
            "type": "GET",
            "data": data,
            "dataType": "json",
            "headers": {
                "Accept": VIEW.accept1,
                "Authorization": VIEW.authorization,
                "Content-Type": "application/json"
            },
            "success": function (dialogs) {
                var info = false;
                // If we have results, format as view expects and list them up
                if (dialogs && dialogs.length > 0) {
                    info = { count: dialogs.length, results: [] };
                    for (var i = 0; i < dialogs.length; i++) {
                        var highlights = {};
                        highlights.en_name = ["<em>" + dialogs[i].title + "</em>"];
                        highlights.en_topic = ["<em>" + dialogs[i].topics[0].name + "</em>"];
                        highlights.en_description = [dialogs[i].description];
                        info.results.push({ score: 150, value: dialogs[i], highlights: highlights });
                    }
                }
                VIEW.format_results(info);
            }
        });
    };

    VIEW.search_videos = function (page, size, term, level) {
        VIEW.set_search_params(page, size, term, level);
        var data = {
            "term": VIEW.searchterm,
            "page": VIEW.searchpage,
            "pageSize": VIEW.searchsize
        };
        if (level) {
            data.difficulty = level;
        }
        if (VIEW.searchterm) {
            $.ajax({
                "url": VIEW.searchurl,
                "type": "GET",
                "data": data,
                "dataType": "json",
                "headers": {
                    "Accept": VIEW.accept1,
                    "Authorization": VIEW.authorization,
                    "Content-Type": "application/json"
                },
                "success": function (info) {
                    VIEW.format_results(info);
                }
            });
        }
    };

    VIEW.set_search_params = function (page, size, term, level) {
        if (VIEW.isNotNum(page)) {
            VIEW.searchpage = 0;
            VIEW.searchterm = "";
            VIEW.searchlevel = "";
        } else {
            VIEW.searchpage = parseInt(page);
        }
        if (size) {
            VIEW.searchsize = parseInt(size);
        }
        if (term) {
            VIEW.searchterm = term;
        }
        if (level) {
            VIEW.searchlevel = level;
        }
    };

    VIEW.format_results = function (info) {
        var html = "";

        // Finish early if there are no results
        if ((!info) || (!info.results) || info.results.length == 0) {
            return html;
        }

        // Cache the paging-bar (we need it twice)
        var pagingbar = VIEW.pagingbar(info.count);


        // Add results
        var videoids = VIEW.get_videoids();
        for (var i = 0; i < info.results.length; i++) {
            // Skip videos that are already displayed
            var id = info.results[i].value.dialogID.toString();
            if (videoids.indexOf(id) < 0) {
                html += VIEW.format_result(info.results[i]);
            }
        }

        // Add bottom paging-bar
        html += pagingbar;

        // Populate search results
        $(".search-results").html(html);

        // Add number of items found
        STR.get_string("xitemsfound", VIEW.plugin, VIEW.formatnumber(info.count)).done(function (s) {
            $(".search-results").prepend(HTML.tag("div", s, { "class": "itemsfound" }));
        });

        // Add click handlers for "+" and thumbnail
        for (var i = 0; i < info.results.length; i++) {
            var v = info.results[i].value;
            var data = {
                "dialogId": v.dialogID,
                "title": v.title,
                "duration": v.duration,
                "difficulty": v.difficulty,
                "dialogURL": v.dialogURL,
                "thumbnailURL": v.thumbnailURL,
                "description": v.description,
                "topics": JSON.stringify(v.topics),
            };
            var id = "#id_add_video_" + data.dialogId;
            $(id).data(data);
            $(id).parent().click(function (evt) {
                if ($(evt.target).closest('.result-info').length) {
                    return; // Exit the function early
                }
                // Run your add_video function
                VIEW.add_video(evt, this);
            });
            $(id).siblings(".result-thumb").click(function () {
                // VIEW.open_window($(this).data("url"));
            });
            $(id).siblings(".result-info").find(".video-info").click(function () {
                var id = $(this).closest(".result-info").siblings(".result-add").prop("id");
                VIEW.open_window(VIEW.videoinfourl + "/" + VIEW.get_videoid_from_id(id));
            });
        }

        // Add click handlers for page numbers on paging-bar
        $(".pagingbar").find(".pagenumber:not(.currentpage)").click(function () {
            var page = $(this).text() - 1;
            VIEW.search_videos(page);
        });
    };

    VIEW.get_videoids = function () {
        var videoids = [];
        $(".englishcentral_videos .activity-title").each(function () {
            videoids.push(VIEW.get_videoid(this));
        });
        return videoids;
    };

    VIEW.get_videoid = function (elm) {
        var url = $(elm).data("url");
        return VIEW.get_videoid_from_url(url);
    };

    VIEW.get_videoid_from_url = function (url) {
        // Sample href: https://www.qaenglishcentral.com/video/28864
        return url.replace(new RegExp("^.*/"), "");
    };

    VIEW.get_videoid_from_id = function (id) {
        // Sample id: id_add_video_27323
        return id.replace(new RegExp("^.*_"), "");
    };

    VIEW.open_window = function (url) {
        var w = Math.min(640, window.innerWidth);
        var h = Math.min(480, window.innerHeight);
        var x = window.outerWidth / 2 + window.screenX - (w / 2);
        var y = window.outerHeight / 2 + window.screenY - (h / 2);
        var options = "width=" + w + ",height=" + h + ",top=" + y + ",left=" + x;
        var win = window.open(url, VIEW.targetwindow, options);
        if (win.focus) {
            win.focus();
        }
    };

    VIEW.pagingbar = function (count) {
        var html = "";
        if (count) {
            var size = VIEW.searchsize;
            var lastpage = Math.ceil(count / size) - 1;
            if (lastpage > 0) {
                var firstpage = 0;

                var page = VIEW.searchpage;
                var p_min = Math.max(firstpage, VIEW.searchpage - 4);
                var p_max = Math.min(lastpage, Math.max(9, VIEW.searchpage + 4));

                if (firstpage < p_min) {
                    html += HTML.tag("span", (firstpage + 1), { "class": "pagenumber" });
                    if ((p_min - firstpage) > 1) {
                        html += HTML.tag("span", "...", { "class": "pageseparator" });
                    }
                }
                for (var p = p_min; p <= p_max; p++) {
                    var s = "pagenumber";
                    if (p == page) {
                        s += " currentpage";
                    }
                    html += HTML.tag("span", (p + 1), { "class": s });
                }
                if (p_max < lastpage) {
                    if ((lastpage - p_max) > 1) {
                        html += HTML.tag("span", "...", { "class": "pageseparator" });
                    }
                    html += HTML.tag("span", (lastpage + 1), { "class": "pagenumber" });
                }
            }
        }
        if (html) {
            html = HTML.tag("div", html, { "class": "pagingbar" });
        }
        return html;
    };

    VIEW.formatnumber = function (n, separator) {
        if (typeof (separator) == "undefined") {
            separator = ",";
        }
        if (typeof (n) == "number") {
            n = n.toString();
        }
        // "B" metachar means "not at beginning or end of word"
        var regexp = new RegExp("\\B(?=(\\d{3})+(?!\\d))", "g");
        return n.replace(regexp, separator);
    };

    VIEW.add_video = function (evt, elm) {
        $.ajax({
            "url": VIEW.viewajaxurl,
            "type": "POST",
            "data": {
                "id": VIEW.cmid,
                "data": $(elm).children(":first").data(),
                "action": "addvideo",
                "sesskey": VIEW.moodlesesskey
            },
            "dataType": "html",
            "success": function (html) {
                $(html).insertBefore(".removevideo").find(".thumb-frame").click(function (evt) {
                    VIEW.play_video(evt, this);
                    $(".video-placeholder").hide();
                    $(".faux-loader").show();
                    $(".faux-loader").delay(2000).fadeOut('slow');
                    evt.stopPropagation();
                    evt.preventDefault();
                    return false;
                });
                //show the remove button if it is hidden
                $(".videoicon.removevideo").removeClass('page-mod-englishcentral-hide');
                //show the preview panel if it is hidden
                $(".player-container-class").removeClass('page-mod-englishcentral-hide');
            }
        });

        // Remove this "result-item" from "search-results"
        $(elm).closest(".result-item").fadeTo(1000, 0.01, function () {
            $(this).slideUp(150, function () {
                $(this).remove();
            });
        });
        // https://stackoverflow.com/questions/1807187/how-to-remove-an-element-slowly-with-jquery
    };

    // "dialogID": 11875,
    // "title": "The Japanese Are Very Important",
    // "description": "A global economy analyst discusses globalization and its influence on countries like Japan.",
    // "difficulty": 4,
    // "duration": "00:02:07",
    // "dateModified": "2017-08-03T11:20:30.000Z",
    // "dateFirstPublished": "2011-05-12T17:04:26.000Z",
    // "popularityWeight": 1.76737,
    // "slowSpeakAudioURL": "https://cdna.qaenglishcentral.com/dialogs/11875/slowspeakaudio_11875_84768.mp3",
    // "dialogURL": "https://www.qaenglishcentral.com/video/11875",
    // "thumbnailURL": "https://cdna.qaenglishcentral.com/dialogs/11875/thumb_11875_20160719065106.jpg",
    // "featurePictureURL": "https://cdna.qaenglishcentral.com/dialogs/11875/featureddialog_11875_20160719065115.jpg",
    // "demoPictureURL": "https://cdna.qaenglishcentral.com/dialogs/11875/demopicture_11875_20160719065122.jpg",
    // "videoDetailsURL": "https://www.qaenglishcentral.com/videodetails/11875",
    // "seriesThumbnailURL": "https://cdna.qaenglishcentral.com/dialogs/11875/dialogseriesthumbnail_11875_20160719065111.jpg",
    // "dialogM4aAudioURL": "https://cdna.qaenglishcentral.com/dialogs/11875/audio_11875_20130527081953.m4a",
    // "smallVideoURL": "https://cdna.qaenglishcentral.com/dialogs/11875/videomobile_11875_20140205180008.mp4",
    // "mediumVideoURL": "https://cdna.qaenglishcentral.com/dialogs/11875/videoslowconn_11875_20131130050102.mp4",
    // "largeVideoURL": "https://cdna.qaenglishcentral.com/dialogs/11875/videoh264_11875_20130527081953.mp4",
    // "promotionalDialog": false,

    VIEW.format_result = function (r) {
        if (VIEW.isNotNum(r.value.difficulty) || r.value.difficulty < 1 || r.value.difficulty > 7) {
            r.value.difficulty = 0;
            r.difficulty = "unknown";
        } else {
            switch (true) {
                case (r.value.difficulty <= 2): r.difficulty = "beginner"; break;
                case (r.value.difficulty <= 4): r.difficulty = "intermediate"; break;
                case (r.value.difficulty <= 7): r.difficulty = "advanced"; break;
            }
        }
        var html = "";
        html += VIEW.format_add(r);
        html += VIEW.format_thumb(r);
        html += VIEW.format_info(r);
        return HTML.tag("div", html, {
            "class": "result-item"
        });
    };

    VIEW.format_add = function (r) {
        var src = $(".removevideo img").prop("src").replace("removevideo", "add");
        var html = HTML.emptytag("img", {
            "src": src,
            "title": VIEW.str.addthisvideo
        });
        return HTML.tag("div", html, {
            "class": "result-add",
            "id": "id_add_video_" + r.value.dialogID
        });
    };

    VIEW.format_thumb = function (r) {
        var duration = r.value.duration.replace(new RegExp("^00:"), "");
        var html = "";
        html += HTML.starttag("div", {
            "class": "thumb-frame",
            "style": "background-image: url('" + r.value.thumbnailURL + "')"
        });
        html += HTML.tag("div", r.value.difficulty, {
            "class": "result-difficulty " + r.difficulty
        });
        html += HTML.tag("div", duration, {
            "class": "result-duration"
        });
        html += HTML.endtag("div");
        return HTML.tag("div", html, {
            "class": "result-thumb",
            "data-url": r.value.dialogURL,
        });
    };

    VIEW.format_info = function (r) {
        var html = "";
        var src = $(".removevideo img").prop("src")
            .replace("removevideo", "i/info")
            .replace("mod_englishcentral", "core");
        var title = r.value.title;
        if (r.highlights.en_name) {
            title = r.highlights.en_name[0];
        }
        html += HTML.tag("h2", title, {
            "class": "result-title"
        });
        html += HTML.tag("div", "", {
            "src": src,
            "class": "video-info",
            "data-toggle": "tooltip",
            "data-placement": "left",
            "title": VIEW.str.videodetails
        });
        html += VIEW.format_details(r);
        return HTML.tag("div", html, {
            "class": "result-info"
        });
    };

    VIEW.format_details = function (r) {
        var html = "";
        // Html += VIEW.format_copyright(r.value.copyright);
        html += VIEW.format_goalstopics(r.value.goals, r.value.topics, r.difficulty);
        html += VIEW.format_description(r.value.description);
        html += VIEW.format_transcript(r.highlights.transcript);
        return HTML.tag("dl", html, {
            "class": "result-details"
        });
    };

    VIEW.format_copyright = function (copyright) {
        if (copyright && copyright.length) {
            return VIEW.format_detail("copyright", copyright);
        }
        return "";
    };

    VIEW.format_goalstopics = function (goals, topics, difficulty) {
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
        txt = txt.join(", ");
        txt += HTML.tag("span", VIEW.str[difficulty], {
            "class": "result-level " + difficulty
        });
        return VIEW.format_detail("topics", txt);
    };

    VIEW.format_description = function (description) {
        if (description && description.length) {
            return VIEW.format_detail("description", description);
        }
        return "";
    };

    VIEW.format_transcript = function (transcript) {
        if (transcript && transcript.length) {
            var dots = "...";
            var slashes = new RegExp("//", "g");
            return VIEW.format_detail("transcript", dots + transcript[0].replace(slashes, dots) + dots);
        }
        return "";
    };

    VIEW.format_detail = function (type, value) {
        var html = "";
        html += HTML.tag("dt", VIEW.str[type], {
            "class": "result-label " + type
        });
        html += HTML.tag("dd", value, {
            "class": "result-value " + type
        });
        return html;
    };

    VIEW.isNotNum = function (n) {
        switch (typeof (n)) {
            case "number": return false;
            case "string": return (!n.match(new RegExp("^[0-9]+$")));
            default: return true; // Everything else is NOT a number
        }
    };

    return VIEW;

});
