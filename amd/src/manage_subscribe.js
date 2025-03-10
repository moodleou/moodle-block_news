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
 * JavaScript to manage news's subscribers page.
 *
 * @module block_news/manage_subscribe
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Manage news's subscribers page.
 *
 * @method  manageSubscribe
 * @param   {string}    stringSelectall      String selectall
 * @param   {string}    stringDeselectall    String delectall
 */
export const manageSubscribe = function (stringSelectall, stringDeselectall) {
    let buttonsDiv = document.getElementById('block-news-buttons');

    let selectAll = document.createElement('input');
    selectAll.setAttribute('type', 'button');
    selectAll.value = stringSelectall;
    buttonsDiv.appendChild(selectAll);

    let deselectAll = document.createElement('input');
    deselectAll.setAttribute('type', 'button');
    deselectAll.value = stringDeselectall;
    buttonsDiv.appendChild(deselectAll);
    let unsubscribe;
    let inputs = document.querySelectorAll('#block-news-subscription-list input');
    let all = [];
    for (let i = 0; i < inputs.length; i++) {
        let input = inputs.item(i);
        if (input.name.indexOf('user') == 0) {
            all.push(input);
        }
        if (input.name == 'unsubscribe') {
            unsubscribe = input;
        }
    }
    let update = () => {
        let allSelected = true, noneSelected = true;
        for (let i = 0; i < all.length; i++) {
            if (all[i].checked) {
                noneSelected = false;
            } else {
                allSelected = false;
            }
        }
        selectAll.disabled = allSelected;
        deselectAll.disabled = noneSelected;
        unsubscribe.disabled = noneSelected;
    };

    update();

    for (let i = 0; i < all.length; i++) {
        all[i].addEventListener('click', () => update());
    }

    selectAll.addEventListener('click', () => {
        for (let i = 0; i < all.length; i++) {
            all[i].checked = true;
        }
        update();
    });

    deselectAll.addEventListener('click', () => {
        for (let i = 0; i < all.length; i++) {
            all[i].checked = false;
        }
        update();
    });
};
