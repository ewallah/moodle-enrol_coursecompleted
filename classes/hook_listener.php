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

namespace enrol_coursecompleted;

use context_course;
use core_course\hook\before_course_deleted;
use core_enrol\hook\after_enrol_instance_status_updated;
use core_enrol\hook\after_user_enrolled;
use core_user;
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
     * Callback for the user_enrolment hook.
     *
     * @param \core_enrol\hook\after_user_enrolled $hook
     */
    public static function send_course_welcome_message(after_user_enrolled $hook): void {
        global $CFG;
        $instance = $hook->get_enrolinstance();
        if ($instance->enrol == 'coursecompleted') {
            if (enrol_is_enabled('coursecompleted')) {
                // Send welcome message.
                if ($instance->customint2 != ENROL_DO_NOT_SEND_EMAIL) {
                    if ($context = context_course::instance($instance->customint1, IGNORE_MISSING)) {
                        $course = get_course($instance->customint1);
                        $formatter = \core\di::get(\core\formatting::class);
                        $a = new stdClass();
                        $a->completed = $formatter->format_string($course->fullname, context: $context);
                        if ($instance->customtext1 == '') {
                            $message = get_string('welcometocourse', 'enrol_coursecompleted', $a);
                        } else {
                            $key = ['{$a->completed}'];
                            $value = [$a->completed];
                            $message = str_replace($key, $value, $instance->customtext1);
                        }
                        $plugin = enrol_get_plugin('coursecompleted');
                        $plugin->send_course_welcome_message_to_user(
                            instance: $instance,
                            userid: $hook->get_userid(),
                            sendoption: $instance->customint2,
                            message: $message,
                        );
                    }
                }

                // Keep the user in a group when needed.
                if ($instance->customint3 > 0) {
                    require_once($CFG->dirroot . '/group/lib.php');
                    $groups = groups_get_user_groups($instance->customint1, $hook->get_userid());
                    foreach ($groups as $group) {
                        foreach ($group as $sub) {
                            $groupnamea = groups_get_group_name($sub);
                            $groupnameb = groups_get_group_by_name($instance->courseid, $groupnamea);
                            if ($groupnameb) {
                                groups_add_member($groupnameb, $hook->get_userid());
                            }
                        }
                    }
                }
            }
        }
    }

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
        $DB->delete_records_select('task_adhoc', "component = :component AND $sqllike", $params);
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
        // TODO: if enabled then recreate adhoc_tasks.
        if ($instance->enrol === 'coursecompleted') {
            if ($hook->newstatus === ENROL_INSTANCE_DISABLED) {
                // Remove adhoc tasks that enrol students in the future.
                $sqllike = $DB->sql_like('customdata', ':customdata');
                $params = ['component' => 'enrol_coursecompleted', 'customdata' => '{"id":"' . $instance->id . '"%'];
                // Only tested by behat.
                $DB->delete_records_select('task_adhoc', "component = :component AND $sqllike", $params);
            }
        }
    }
}
