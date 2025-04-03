
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
 * load the EnglishCentral player
 *
 * @module      mod_englishcentral/form
 * @category    output
 * @copyright   Gordon Bateson
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since       2.9
 */
define(["jquery"], function($) {
    /** @alias module:mod_englishcentral/form */
    var FORM = {};

    FORM.init = function() {

        $("#id_studygoal").prop("type", "hidden");
        $("#id_studygoal").after('<span id="id_studygoaltext"></span>');

        $("#id_watchgoal, #id_learngoal, #id_speakgoal, #id_chatgoal").change(function(){
            var watch = $("#id_watchgoal").val();
            var learn = $("#id_learngoal").val();
            var speak = $("#id_speakgoal").val();
            var chat = $("#id_chatgoal").val();
            var mins = (isNaN(watch) ? 0 : parseInt(watch) * 6) // 6 mins per video watched
                     + (isNaN(learn) ? 0 : parseInt(learn))     // 1 min per word learned
                     + (isNaN(speak) ? 0 : parseInt(speak))     // 1 min per word spoken
                     + (isNaN(chat) ? 0 : parseInt(chat) * 4);  // 4 mins per chat question
            $("#id_studygoal").val(mins);
            $("#id_studygoaltext").text(mins);
        });

        $("#id_watchgoal").trigger("change");

        var lastgoal = null;
        var goaltypes = new Array('watch', 'learn', 'speak', 'chat');
        for (var i = 0; i < goaltypes.length; i++) {
            var type = goaltypes[i];
            var nextgoal = document.querySelector("#fgroup_id_" + type + "goalgroup");
            if (nextgoal) {
                if (lastgoal) {
                    $(lastgoal).after('<div class="mathsymbol"> + </div>');
                }
                lastgoal = nextgoal;
            }
        }
        if (lastgoal) {
            $(lastgoal).after('<div class="mathsymbol"> = </div>');
        }

        // Fix layout on Boost-based themes.
        // We want the goals to appear as boxes:
        // [Watch] + [Learn] + [Speak] + [Chat] = [Study goals]
        var goals = document.querySelector("#id_goals");
        if (goals) {
            if (goals.querySelector("form-group") === null) {
                var s = '[id^=fgroup_id][id$=group]';
                goals.querySelectorAll(s).forEach(function(fgroup){
                    if (fgroup.id.indexOf("_error_") > 0) {
                        return;
                    }
                    fgroup.classList.add("d-inline-block");
                    fgroup.classList.add("rounded");
                    fgroup.classList.add("px-2");
                    fgroup.classList.add("pb-2");
                    fgroup.classList.add("mx-0");
                    // The "form-group" selector seems to have been deprecated
                    // and replaced by "mb-3" in the "Classic" and "Boost" themes.
                    fgroup.querySelectorAll(".mb-3.fitem").forEach(function(fitem){
                        fitem.classList.remove("mb-3");
                        fitem.classList.add("d-inline-block");
                    });
                });
            } else {
                goals.querySelectorAll(".form-group").forEach(function(fgroup){
                    fgroup.classList.remove("form-group");
                    fgroup.classList.add("d-inline-block");
                });
                goals.querySelectorAll(".row").forEach(function(row){
                    row.classList.remove("row");
                    row.classList.add("rounded");
                    row.classList.add("px-2");
                    row.classList.add("mb-2");
                    row.classList.add("align-top");
                    row.style.minHeight = "80px";
                });
            }

            // Remove all the sexy, flexy selectors.
            var selectors = [
                "col-md-3", "col-md-9", "d-flex",
                "align-items-start", "align-self-start",
            ];
            selectors.forEach(function(s){
                goals.querySelectorAll("." + s).forEach(function(elm){
                    elm.classList.remove(s);
                });
            });

            // Embolden all the label text
            goals.querySelectorAll(".col-form-label").forEach(function(elm){
                for (var i=0; i < elm.children.length; i++) {
                    var child = elm.children[i];
                    child.classList.add("d-inline-block");
                    if (child.matches("[id^='fgroup_'][id$='_label']")) {
                        child.classList.add("font-weight-bold");
                    }
                }
            });
        }
    };

    return FORM;
});