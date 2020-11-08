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
 * A bulk operation for the coursecompleted enrolment plugin to delete selected users enrolments.
 *
 * @package   enrol_coursecompleted
 * @copyright 2020 eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/enrol/locallib.php');

/**
 * A bulk operation for the coursecompleted enrolment plugin to delete selected users enrolments.
 *
 * @package   enrol_coursecompleted
 * @copyright 2020 eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_coursecompleted_bulkdelete extends enrol_bulk_enrolment_operation {

    /**
     * Returns the identifier for this bulk operation. This is the key used when the plugin
     * returns an array containing all of the bulk operations it supports.
     *
     * @return string
     */
    public function get_identifier() {
        return 'deleteselectedusers';
    }

    /**
     * Returns the title to display for this bulk operation.
     *
     * @return string
     */
    public function get_title() {
        return get_string('deleteselectedusers', 'enrol_coursecompleted');
    }

    /**
     * Returns a enrol_bulk_enrolment_operation extension form to be used
     * in collecting required information for this operation to be processed.
     *
     * @param string|moodle_url|null $defaultaction
     * @param mixed $defaultcustomdata
     * @return enrol_coursecompleted_deleteselectedusers_form
     */
    public function get_form($defaultaction = null, $defaultcustomdata = null) {
        $data = array($defaultcustomdata) ? $defaultcustomdata : [];
        $data['title'] = $this->get_title();
        $data['message'] = get_string('confirmbulkdeleteenrolment', 'enrol_coursecompleted');
        $data['button'] = get_string('unenrolusers', 'enrol_coursecompleted');
        return new \enrol_coursecompleted\form\bulkdelete($defaultaction, $data);
    }

    /**
     * Processes the bulk operation request for the given userids with the provided properties.
     *
     * @param course_enrolment_manager $manager
     * @param array $users
     * @param stdClass $properties The data returned by the form.
     */
    public function process(course_enrolment_manager $manager, array $users, stdClass $properties) {
        if (!has_capability("enrol/coursecompleted:unenrol", $manager->get_context())) {
            return false;
        }
        foreach ($users as $user) {
            foreach ($user->enrolments as $enrolment) {
                $plugin = $enrolment->enrolmentplugin;
                $instance = $enrolment->enrolmentinstance;
                if ($plugin->allow_unenrol_user($instance, $enrolment)) {
                    $plugin->unenrol_user($instance, $user->id);
                }
            }
        }
        return true;
    }
}
