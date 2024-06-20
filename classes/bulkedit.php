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
 * A bulk operation for the coursecompleted enrolment plugin to edit selected users enrolments.
 *
 * @package   enrol_coursecompleted
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_coursecompleted;

// @codeCoverageIgnoreStart
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/enrol/locallib.php');
// @codeCoverageIgnoreEnd

/**
 * A bulk operation for the coursecompleted enrolment plugin to edit selected users enrolments.
 *
 * @package   enrol_coursecompleted
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bulkedit extends \enrol_bulk_enrolment_operation {
    /**
     * Returns the identifier for this bulk operation. This is the key used when the plugin
     * returns an array containing all of the bulk operations it supports.
     *
     * @return string
     */
    public function get_identifier() {
        return 'editselectedusers';
    }

    /**
     * Returns the title to display for this bulk operation.
     *
     * @return string
     */
    public function get_title() {
        return get_string('editselectedusers', 'enrol_coursecompleted');
    }

    /**
     * Returns a enrol_bulk_enrolment_operation extension form to be used
     * in collecting required information for this operation to be processed.
     *
     * @param string|\moodle_url|null $defaultaction
     * @param mixed $defaultcustomdata
     * @return \enrol_coursecompleted\bulkedit_form
     */
    public function get_form($defaultaction = null, $defaultcustomdata = null) {
        $data = is_array($defaultcustomdata) ? $defaultcustomdata : [];
        $data['title'] = $this->get_title();
        $data['message'] = get_string('confirmbulkediteenrolment', 'enrol_coursecompleted');
        $data['button'] = get_string('editusers', 'enrol_coursecompleted');
        return new form\bulkedit($defaultaction, $data);
    }

    /**
     * Processes the bulk operation request for the given userids with the provided properties.
     *
     * @param \course_enrolment_manager $manager
     * @param array $users
     * @param \stdClass $properties The data returned by the form.
     * @return bool
     */
    public function process(\course_enrolment_manager $manager, array $users, \stdClass $properties): bool {
        global $DB, $USER;
        $context = $manager->get_context();
        if (!has_capability("enrol/coursecompleted:manage", $context)) {
            return false;
        }
        $ueids = $updatesql = [];
        foreach ($users as $user) {
            foreach ($user->enrolments as $enrolment) {
                $ueids[] = $enrolment->id;
                $courseid = $enrolment->enrolmentinstance->courseid;
                $data = [
                    'objectid' => $enrolment->id,
                    'courseid' => $courseid,
                    'context' => \context_course::instance($courseid),
                    'relateduserid' => $user->id,
                    'other' => ['enrol' => 'coursecompleted'],
                ];
                \core\event\user_enrolment_updated::create($data)->trigger();
            }
        }
        [$ueidsql, $params] = $DB->get_in_or_equal($ueids, SQL_PARAMS_NAMED);
        if (!empty($properties->status)) {
            $updatesql[] = 'status = :status';
            $params['status'] = $properties->status;
        }
        if (!empty($properties->timestart)) {
            $updatesql[] = 'timestart = :timestart';
            $params['timestart'] = $properties->timestart;
        }
        if (!empty($properties->timeend)) {
            $updatesql[] = 'timeend = :timeend';
            $params['timeend'] = $properties->timeend;
        }
        $updatesql[] = 'modifierid = :modifierid';
        $params['modifierid'] = $USER->id;

        $updatesql[] = 'timemodified = :timemodified';
        $params['timemodified'] = time();

        $updatesql = join(', ', $updatesql);
        $sql = "UPDATE {user_enrolments} SET $updatesql WHERE id $ueidsql";
        return $DB->execute($sql, $params);
    }
}
