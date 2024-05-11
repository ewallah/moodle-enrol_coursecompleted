<?php
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
 * Coursecompleted enrolment plugin uninstall.
 *
 * @package   enrol_coursecompleted
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Coursecompleted enrolment plugin uninstall.
 *
 * @package   enrol_coursecompleted
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function xmldb_enrol_coursecompleted_uninstall() {
    global $DB;
    $plugin = enrol_get_plugin('coursecompleted');
    $rs = $DB->get_recordset('enrol', ['enrol' => 'coursecompleted']);
    foreach ($rs as $instance) {
        $plugin->delete_instance($instance);
    }
    $rs->close();
    role_unassign_all(['component' => 'enrol_coursecompleted']);
    // Delete all planned enrolments.
    $DB->delete_records('task_adhoc', ['component' => 'enrol_coursecompleted']);
    return true;
}
