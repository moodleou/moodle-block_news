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
 * Javascript module to move the new icon into the right place. This is necessary
 * because if we put it into the title to begin with, then it ends up inside the
 * skip link text as well.
 *
 * @module block_news/newicon
 * @package block_news
 * @copyright 2019 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    var t = {
        /**
         * Move icon for given block.
         *
         * @param {number} instanceId Block instance id
         */
        moveIcon: function(instanceId) {
            var block = document.getElementById('inst' + instanceId);
            if (!block) {
                window['console'].log('Unable to move icon ' + instanceId + ' into place: missing block');
                return;
            }
            var title = block.querySelector('h5.card-title');
            var icon = block.querySelector('.block_news_unreadicon');
            if (!title || !icon) {
                window['console'].log('Unable to move icon ' + instanceId + ' into place: missing title/icon');
                return;
            }
            title.appendChild(icon);
            icon.style.display = 'inline';
        }
    };

    return t;
});
