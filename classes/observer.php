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
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_coursecompleted;

/**
 * Event observers
 *
 * @package   enrol_coursecompleted
 * @copyright 2017 eWallah (www.eWallah.net)
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
        $sql = "SELECT *
                  FROM {enrol}
                 WHERE enrol = :enrol
                       AND status = :status
                       AND customint1 = :customint1
                       AND (enrolstartdate = 0 OR enrolstartdate < :now1)
                       AND (enrolenddate = 0 OR enrolenddate < :now2)";
        $params = [
            'enrol' => 'coursecompleted',
            'status' => ENROL_INSTANCE_ENABLED,
            'customint1' => $event->courseid,
            'now1' => time(),
            'now2' => time(),
        ];

        if ($enrols = $DB->get_records_sql($sql, $params)) {
            foreach ($enrols as $enrol) {
                // Check role and course.
                if (
                    $DB->record_exists('role', ['id' => $enrol->roleid]) &&
                    $DB->record_exists('course', ['id' => $enrol->courseid])
                ) {
                    if ($enrol->enrolperiod > 0) {
                        $enrol->enrolenddate = max(time(), $enrol->enrolstartdate) + $enrol->enrolperiod;
                    }

                    \enrol_get_plugin('coursecompleted')->enrol_user(
                        $enrol,
                        $event->relateduserid,
                        $enrol->roleid,
                        $enrol->enrolstartdate,
                        $enrol->enrolenddate
                    );
                    \enrol_coursecompleted_plugin::keepingroup($enrol, $event->relateduserid);
                    mark_user_dirty($event->relateduserid);
                    if ($enrol->customint2 > 0) {
                        // There is a course welcome message to be sent.
                        $adhock = new task\send_welcome();
                        $adhock->set_custom_data(
                            [
                                'userid' => $event->relateduserid,
                                'enrolid' => $enrol->id,
                                'courseid' => $enrol->courseid,
                                'completedid' => $enrol->customint1,
                            ]
                        );
                        $adhock->set_component('enrol_coursecompleted');
                        \core\task\manager::queue_adhoc_task($adhock);
                    }
                } else {
                    debugging('Role or course does not exist', DEBUG_DEVELOPER);
                }
            }
        }
    }

    /**
     * Course delete event observer.
     *
     * @param \core\event\course_deleted $event The course deleted event.
     */
    public static function coursedeleted(\core\event\course_deleted $event) {
        global $DB;
        $DB->delete_records('enrol', ['enrol' => 'coursecompleted', 'customint1' => $event->courseid]);
    }
}
