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
 * Autocomplete source for the enrol_coursecompleted group filter.
 *
 * Reads the currently selected "completed course" (customint1) from the form
 * and returns its groups via the core_group_get_course_groups web service.
 *
 * @module     enrol_coursecompleted/groupselector
 * @copyright  Danimarqz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

/**
 * Map the transported results straight through (already {value, label}).
 *
 * @param {String} selector
 * @param {Array} results
 * @return {Array}
 */
export const processResults = (selector, results) => results;

/**
 * Fetch the groups of the course selected in the customint1 field.
 *
 * @param {String} selector
 * @param {String} query
 * @param {Function} success
 * @param {Function} failure
 */
export const transport = (selector, query, success, failure) => {
    const input = document.querySelector('[name="customint1"]');
    const courseid = input ? parseInt(input.value, 10) : 0;
    if (!courseid) {
        success([]);
        return;
    }
    Ajax.call([{
        methodname: 'core_group_get_course_groups',
        args: {courseid: courseid},
    }])[0].then((groups) => {
        const q = (query || '').toLowerCase();
        const out = [];
        groups.forEach((g) => {
            if (q === '' || g.name.toLowerCase().indexOf(q) !== -1) {
                out.push({value: g.id, label: g.name});
            }
        });
        success(out);
        return out;
    }).catch(failure);
};
