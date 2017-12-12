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
 * Enrolment by coursecompleted plugin local functions.
 *
 * @package    enrol_coursecompleted
 * @copyright  2017 iplusacademy  {@link https://www.iplusacademy.org}
 * @author     Renaat Debleu (info@eWallah.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/enrol/locallib.php');


/**
 * Event handler for coursecompleted enrolment plugin.
 *
 * We try to keep everything in sync via listening to events,
 * it may fail sometimes, so we always do a full sync in cron too.
 */
class enrol_coursecompleted_handler {
    /**
     * Event processor - course completed.
     * @param \core\event\course_completed $event
     * @return bool
     */
    public static function course_completed(\core\event\course_completed $event) {
        global $DB, $CFG;
        if (!enrol_is_enabled('coursecompleted')) {
            return true;
        }

        // Does any coursecompleted instance exist?
        $sql = "SELECT * FROM {enrol} WHERE customint1 = :compid AND enrol = 'coursecompleted' AND e.status = :enrolstatus";
        $params['compid'] = $event->objectid;
        $params['enrolstatus'] = ENROL_INSTANCE_ENABLED;
        if (!$instances = $DB->get_records_sql($sql, $params)) {
            return true;
        }
        $plugin = enrol_get_plugin('coursecompleted');
        foreach ($instances as $instance) {
            if ($instance->status != ENROL_INSTANCE_ENABLED ) {
                // No roles for disabled instances.
                $instance->roleid = 0;
            } else if ($instance->roleid and !$instance->roleexists) {
                // Invalid role - let's just enrol, they will have to create new sync and delete this one.
                $instance->roleid = 0;
            }
            unset($instance->roleexists);
            // No problem if already enrolled.
            $plugin->enrol_user($instance, $event->relateduserid, $instance->roleid, 0, 0, ENROL_USER_ACTIVE);
        }
        return true;
    }
}
