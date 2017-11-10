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
 * Javascript module to provide grouping selection functionality.
 *
 * @module     block_news/groupings
 * @package    block_news
 * @copyright  2017 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'], function($) {
    var t = {

        groupingSelect: null,
        groupSelect: null,
        allOption: null,


        /**
         * Setup for group IDs against relevant grouping options,
         * and attach event listeners.
         *
         */
        init: function() {
            t.groupingSelect = $('#id_grouping');
            t.groupSelect = $('#id_groupids');
            t.allOption = t.groupSelect.find('option[value="0"]');

            t.groupingSelect.on('change', t.selectGroups);
            t.groupSelect.on('change', t.clearGroupings);
            $('#fitem_id_grouping').css('display', 'inline-block');
        },

        /**
         * Fetch the groups linked in a grouping directly from the HTML Selector.
         *
         * @param {Event} e
         */
        selectGroups: function(e) {
            var grouping = $(e.currentTarget).find('option:selected');
            // Read data from the groupinggroups attribute of the element.
            var groupingGroups = t.groupingSelect.data("groupinggroups");
            var groups = groupingGroups[grouping.val()];
            t.groupSelect.val([]);
            if (groups) {
                groups.forEach(function(groupid) {
                    t.groupSelect.find('option[value="' + groupid + '"]').prop('selected', true);
                });
            }
            if (t.groupSelect.find('option:selected').length === 0) {
                t.allOption.prop('selected', true);
            } else {
                t.allOption.prop('selected', false);
            }
        },

        /**
         * Set the Grouping select to blank, if the "all groups" option is selected, unselect other groups as well.
         */
        clearGroupings: function() {
            if (t.allOption.prop('selected')) {
                t.groupSelect.val(['0']);
            }
            t.groupingSelect.val(['0']);
        }
    };

    return t;
});
