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
 * Event observers
 *
 * @package   enrol_coursecompleted
 * @copyright 2017 eWallah (www.eWallah.net)
 * @author    Renaat Debleu (www.ewallah.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Event observers
 *
 * @package   enrol_coursecompleted
 * @copyright 2017 eWallah (www.eWallah.net)
 * @author    Renaat Debleu (www.ewallah.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_coursecompleted_observer {

    /**
     * Triggered when user completes a course.
     *
     * @param \core\event\course_completed $event
     */
    public static function enroluser(\core\event\course_completed $event) {
        global $DB;
        $params = ['enrol' => 'coursecompleted', 'status' => 0, 'customint1' => $event->courseid];
        if ($enrols = $DB->get_records('enrol', $params)) {
            $plugin = \enrol_get_plugin('coursecompleted');
            foreach ($enrols as $enrol) {
                if ($DB->record_exists('role', ['id' => $enrol->roleid])) {
                    $plugin->enrol_user($enrol, $event->relateduserid, $enrol->roleid,
                                        $enrol->enrolstartdate, $enrol->enrolenddate);
                } else {
                    debugging('Role does not exist', DEBUG_DEVELOPER);
                }
            }
        }
    }
}
