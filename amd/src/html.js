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
 * load object to produce HTML tags
 *
 * @module      mod_englishcentral/html
 * @category    output
 * @copyright   Gordon Bateson
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since       2.9
 */
define(["jquery"], function() {
    /** @alias module:mod_englishcentral/html */
    var t = {

        "htmlescape" : function(value) {
            value += ""; // convert to String
            return value.replace(new RegExp("&", "g"), "&amp;")
                        .replace(new RegExp("'", "g"), "&apos;")
                        .replace(new RegExp('"', "g"), "&quot;")
                        .replace(new RegExp("<", "g"), "&lt;")
                        .replace(new RegExp(">", "g"), "&gt;");
        },

        "attribute" : function(name, value) {
            if (name = name.replace(new RegExp("^a-zA-Z0-9_-"), "g")) {
                name = " " + name + '="' + t.htmlescape(value) + '"';
            }
            return name;
        },

        "attributes" : function(attr) {
            var html = "";
            if (attr) {
                for (var name in attr) {
                    html += t.attribute(name, attr[name]);
                }
            }
            return html;
        },

        "starttag" : function(tag, attr) {
            return "<" + tag + t.attributes(attr) + ">";
        },

        "endtag" : function(tag) {
            return "</" + tag + ">";
        },

        "emptytag" : function(tag, attr) {
            return "<" + tag + t.attributes(attr) + "/>";
        },

        "tag" : function(tag, content, attr) {
            return (t.starttag(tag, attr) + content + t.endtag(tag));
        },

        "input" : function(name, type, attr) {
            attr.type = type;
            attr.name = name;
            attr.id = "id_" + name;
            return t.emptytag("input", attr);
        },

        "hidden" : function(name, value) {
            var attr = {"value" : (value || "")};
            return t.input(name, "hidden", attr);
        },

        "text" : function(name, value, size) {
            var attr = {"value" : (value || ""),
                        "size" : (size || "15")};
            return t.input(name, "text", attr);
        },

        "checkbox" : function(name, checked) {
            var attr = {"value" : "1"};
            if (checked) {
                attr.checked = "checked";
            }
            return t.input(name, "checkbox", attr);
        },

        "alist" : function(tag, items) {
            var alist = "";
            for (var i in items) {
                alist += t.tag("li", items[i]);
            }
            return t.tag(tag, alist, {});
        },

        "select" : function(name, options, selected, attr) {
            var html = "";
            for (var value in options) {
                var a = {"value" : value};
                if (value==selected) {
                    a["selected"] = "selected";
                }
                html += t.tag("option", options[value], a);
            }
            attr.name = name;
            attr.id = "id_" + name;
            return t.tag("select", html, attr);
        }
    };
    return t;
});
