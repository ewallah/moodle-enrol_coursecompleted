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
 * Enrol coursecompleted generator.
 *
 * @package    enrol_coursecompleted
 * @copyright  eWallah.net
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Enrol coursecompleted generator.
 *
 * @package    enrol_coursecompleted
 * @copyright  eWallah.net
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_coursecompleted_generator extends component_generator_base {
    /**
     * Create course completion
     * @param array $data containing course and user
     */
    public function create_coursecompletion(array $data) {
        // Should we not complete a course, but better fire a coursecompleted event.
        global $DB;
        if (!isset($data['course'])) {
            throw new coding_exception('Must specify course when creating course completion.');
        }

        if (!isset($data['user'])) {
            throw new coding_exception('Must specify a user name when creating a course completion.');
        }

        if (clean_param($data['course'], PARAM_TEXT) !== $data['course']) {
            throw new coding_exception('Course name must be PARAM_TEXT.');
        }

        if (clean_param($data['user'], PARAM_TEXT) !== $data['user']) {
            throw new coding_exception('User name must be PARAM_TEXT.');
        }

        if (!$courseid = $DB->get_field('course', 'id', ['shortname' => $data['course']])) {
            throw new Exception("A course with shortname '{$course}' does not exist");
        }
        if (!$userid = $DB->get_field('user', 'id', ['username' => $data['user']])) {
            throw new Exception("A user with username '{$username}' does not exist");
        }
        $compevent = \core\event\course_completed::create(
            [
                'objectid' => $courseid,
                'relateduserid' => $userid,
                'context' => \context_course::instance($courseid),
                'courseid' => $courseid,
                'other' => ['relateduserid' => $userid],
            ]
        );
        $compevent->trigger();
        $task = new \core\task\completion_regular_task();
        $task->execute();
        sleep(1);
        $task->execute();
        mark_user_dirty($userid);
        rebuild_course_cache($courseid, true);
    }

    /**
     * Create course enrolment on completion
     * @param array $data of course and required
     */
    public function create_courseenrolment(array $data) {
        global $CFG, $DB;

        if (!isset($data['course'])) {
            throw new coding_exception('Must specify course when creating a course completion enrolment.');
        }

        if (!isset($data['required'])) {
            throw new coding_exception('Must specify a required course when creating a course completion enrolment.');
        }
        if (clean_param($data['course'], PARAM_TEXT) !== $data['course']) {
            throw new coding_exception('Course name must be PARAM_TEXT.');
        }

        if (clean_param($data['required'], PARAM_TEXT) !== $data['required']) {
            throw new coding_exception('required course must be PARAM_TEXT.');
        }

        if (!$courseid = $DB->get_field('course', 'id', ['shortname' => $data['course']])) {
            throw new Exception("A course with shortname '{$course}' does not exist");
        }
        if (!$requiredid = $DB->get_field('course', 'id', ['shortname' => $data['required']])) {
            throw new Exception("A required course with shortname '{$required}' does not exist");
        }
        $CFG->enablecompletion = true;
        $enabled = enrol_get_plugins(true);
        $enabled['coursecompleted'] = true;
        $enabled['guest'] = false;
        set_config('enrol_plugins_enabled', implode(',', array_keys($enabled)));

        $plugin = enrol_get_plugin('coursecompleted');
        $plugin->add_instance(get_course($courseid), ['customint1' => $requiredid, 'customint2' => 1]);
    }
}
