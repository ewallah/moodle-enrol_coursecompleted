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

declare(strict_types=1);

namespace enrol_coursecompleted;

use context_course;
use core_course\hook\before_course_deleted;
use core_enrol\hook\after_enrol_instance_status_updated;
use core_enrol\hook\after_user_enrolled;
use core_user;
use moodle_page;
use stdClass;

/**
 * Enrol coursecompleted plugin hook listener
 *
 * @package   enrol_coursecompleted
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_listener {
    /**
     * Delete enrolment methods related to this course.
     * Delete all adhoc tasks to enrol users in the future.
     *
     * @param before_course_deleted $hook The course deleted hook.
     */
    public static function before_course_deleted(
        before_course_deleted $hook,
    ): void {
        global $DB;
        // Delete all past enrolments.
        $DB->delete_records('enrol', ['enrol' => 'coursecompleted', 'customint1' => $hook->course->id]);
        // Delete all future enrolments.
        $sqllike = $DB->sql_like('customdata', ':customdata');
        $params = ['component' => 'enrol_coursecompleted', 'customdata' => '%"customint1":"' . $hook->course->id . '"%'];
        $DB->delete_records_select('task_adhoc', "component = :component AND {$sqllike}", $params);
    }

    /**
     * Cancel all enrolments when enrol disabled.
     *
     * @param after_enrol_instance_status_updated $hook The enrol status updated hook.
     */
    public static function after_enrol_instance_status_updated(
        after_enrol_instance_status_updated $hook,
    ): void {
        global $DB;
        $instance = $hook->enrolinstance;
        if ($instance->enrol === 'coursecompleted') {
            if ($hook->newstatus === ENROL_INSTANCE_DISABLED) {
                // Remove adhoc tasks that enrol students in the future.
                $sqllike = $DB->sql_like('customdata', ':customdata');
                $params = ['component' => 'enrol_coursecompleted', 'customdata' => '{"id":"' . $instance->id . '"%'];
                // Only tested by behat.
                $DB->delete_records_select('task_adhoc', "component = :component AND {$sqllike}", $params);
            }

            // TODO: if enabled then recreate adhoc_tasks.
        }
    }
}
