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
 * load object to produce HTML tags
 *
 * @module      mod_englishcentral/html
 * @category    output
 * @copyright   Gordon Bateson
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since       2.9
 */
define([], function() {

    /** @alias module:mod_englishcentral/html */
    var HTML = {};

    // RegExp to clean non-alphanumeric chars from a string
    HTML.nonalphanumeric = new RegExp("[^a-zA-Z0-9_-]+", "g");

    HTML.htmlescape = function(value) {
        value += ""; // convert to String
        return value.replace(new RegExp("&", "g"), "&amp;")
            .replace(new RegExp("'", "g"), "&apos;")
            .replace(new RegExp('"', "g"), "&quot;")
            .replace(new RegExp("<", "g"), "&lt;")
            .replace(new RegExp(">", "g"), "&gt;");
    };

    HTML.attribute = function(name, value) {
        var attr = name.replace(HTML.nonalphanumeric, "");
        if (attr) {
            attr = " " + attr + '="' + HTML.htmlescape(value) + '"';
        }
        return attr;
    };

    HTML.attributes = function(attr) {
        var html = "";
        if (attr) {
            for (var name in attr) {
                html += HTML.attribute(name, attr[name]);
            }
        }
        return html;
    };

    HTML.starttag = function(tag, attr) {
        return "<" + tag + HTML.attributes(attr) + ">";
    };

    HTML.endtag = function(tag) {
        return "</" + tag + ">";
    };

    HTML.emptytag = function(tag, attr) {
        return "<" + tag + HTML.attributes(attr) + "/>";
    };

    HTML.tag = function(tag, content, attr) {
        return (HTML.starttag(tag, attr) + content + HTML.endtag(tag));
    };

    HTML.input = function(name, type, attr, id) {
        attr.type = type;
        attr.name = name;
        if (id) {
            attr.id = id;
        } else {
            attr.id = "id_" + name;
        }
        return HTML.emptytag("input", attr);
    };

    HTML.hidden = function(name, value) {
        var attr = {
            "value": (value || "")
        };
        return HTML.input(name, "hidden", attr);
    };

    HTML.text = function(name, value, size) {
        var attr = {
            "value": (value || ""),
            "size": (size || "15")
        };
        return HTML.input(name, "text", attr);
    };

    HTML.checkbox = function(name, checked) {
        var attr = {
            "value": "1"
        };
        if (checked) {
            attr.checked = "checked";
        }
        return HTML.input(name, "checkbox", attr);
    };

    HTML.alist = function(tag, items) {
        var alist = "";
        for (var i in items) {
            alist += HTML.tag("li", items[i]);
        }
        return HTML.tag(tag, alist, {});
    };

    HTML.select = function(name, options, selected, attr) {
        var html = "";
        for (var value in options) {
            var a = {
                "value": value
            };
            if (value == selected) {
                a["selected"] = "selected";
            }
            html += HTML.tag("option", options[value], a);
        }
        attr.name = name;
        attr.id = "id_" + name;
        return HTML.tag("select", html, attr);
    };

    return HTML;
});
