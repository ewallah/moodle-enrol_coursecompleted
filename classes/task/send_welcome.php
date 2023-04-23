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
 * Process expirations task.
 *
 * @package   enrol_coursecompleted
 * @copyright 2020 eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_coursecompleted\task;

use stdClass;
use moodle_url;
use core_user;

/**
 * Process expirations task.
 *
 * @package   enrol_coursecompleted
 * @copyright 2020 eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_welcome extends \core\task\adhoc_task {

    /**
     * Execute scheduled task
     *
     * @return boolean
     */
    public function execute() {
        global $CFG, $DB;
        $data = $this->get_custom_data();
        if ($user = \core_user::get_user($data->userid)) {
            if ($course = $DB->get_field('course', 'fullname', ['id' => $data->courseid])) {
                if ($complcourse = $DB->get_field('course', 'fullname', ['id' => $data->completedid])) {
                    $context = \context_course::instance($data->courseid);
                    $context2 = \context_course::instance($data->completedid);
                    $a = new stdClass();
                    $a->coursename = format_string($course, true, ['context' => $context]);
                    $a->profileurl = "$CFG->wwwroot/user/view.php?id=$user->id&course=$data->courseid";
                    $a->completed = format_string($complcourse, true, ['context' => $context2]);
                    $custom = $DB->get_field('enrol', 'customtext1', ['id' => $data->enrolid]);
                    $key = ['{$a->coursename}',  '{$a->completed}', '{$a->profileurl}', '{$a->fullname}', '{$a->email}'];
                    $value = [$a->coursename, $a->completed, $a->profileurl, fullname($user), $user->email];
                    if ($custom != '') {
                        $message = str_replace($key, $value, $custom);
                    } else {
                        $message = get_string('welcometocourse', 'enrol_coursecompleted', $a);
                    }
                    if (strpos($message, '<') == false) {
                        $messagehtml = $message;
                    } else {
                        // This is most probably the tag/newline soup known as FORMAT_MOODLE.
                        $messagehtml = format_text($message, FORMAT_MOODLE,
                           ['context' => $context, 'para' => false, 'newlines' => true, 'filter' => true]);
                    }
                    $subject = get_string('welcometocourse', 'moodle', $a->coursename);
                    // Directly emailing welcome message rather than using messaging.
                    email_to_user($user, core_user::get_noreply_user(), $subject, $message, $messagehtml);
                }
            }
        }
    }
}
