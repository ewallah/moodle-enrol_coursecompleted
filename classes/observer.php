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
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_coursecompleted;

/**
 * Event observers
 *
 * @package   enrol_coursecompleted
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Triggered when user completes a course.
     *
     * @param \core\event\course_completed $event
     */
    public static function enroluser(\core\event\course_completed $event) {
        global $DB;
        if (enrol_is_enabled('coursecompleted')) {
            // TODO: What if there are 2 roles?
            $sql = "SELECT *
                      FROM {enrol}
                      WHERE enrol = :enrol
                            AND status = :status
                            AND customint1 = :customint1";
            $params = [
                'enrol' => 'coursecompleted',
                'status' => ENROL_INSTANCE_ENABLED,
                'customint1' => $event->courseid,
            ];

            if ($enrols = $DB->get_records_sql($sql, $params)) {
                $userid = $event->relateduserid;
                foreach ($enrols as $enrol) {
                    if ($enrol->customint4 > time() + 10) {
                        $adhock = new \enrol_coursecompleted\task\process_future();
                        $adhock->set_userid($userid);
                        $adhock->set_custom_data($enrol);
                        $adhock->set_next_run_time($enrol->customint4);
                        $adhock->set_component('enrol_coursecompleted');
                        \core\task\manager::queue_adhoc_task($adhock);
                    } else {
                        \enrol_get_plugin('coursecompleted')->enrol_user($enrol, $userid);
                    }
                }
            }
        }
    }
}
