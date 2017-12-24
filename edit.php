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
 * Adds new or edit instance of enrol_coursecompleted to specified course
 *
 * @package   enrol_coursecompleted
 * @copyright 2017 eWallah (www.eWallah.net)
 * @author    Renaat Debleu (info@eWallah.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once('edit_form.php');
$courseid   = required_param('courseid', PARAM_INT);
$instanceid = optional_param('id', 0, PARAM_INT);

$course = get_course($courseid);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);
require_capability('enrol/coursecompleted:config', $context);

$PAGE->set_url('/enrol/coursecompleted/edit.php', ['courseid' => $course->id, 'id' => $instanceid]);
$PAGE->set_pagelayout('admin');

$return = new moodle_url('/enrol/instances.php', ['id' => $course->id]);
if (!enrol_is_enabled('coursecompleted')) {
    redirect($return);
}

$plugin = enrol_get_plugin('coursecompleted');

if ($instanceid) {
    $arr = ['courseid' => $courseid, 'enrol' => 'coursecompleted', 'id' => $instanceid];
    $instance = $DB->get_record('enrol', $arr, '*', MUST_EXIST);
} else {
    require_capability('moodle/course:enrolconfig', $context);
    // No instance yet, we have to add new instance.
    navigation_node::override_active_url(new moodle_url('/enrol/instances.php', ['id' => $course->id]));
    $instance = new stdClass();
    $instance->id = null;
    $instance->courseid = $course->id;
}

$mform = new enrol_coursecompleted_edit_form(null, [$instance, $plugin, $context]);

if ($mform->is_cancelled()) {
    redirect($return);

} else if ($data = $mform->get_data()) {
    if ($instance->id) {
        $reset = ($instance->status != $data->status);
        $instance->status         = $data->status;
        $instance->name           = $data->name;
        $instance->roleid         = $data->roleid;
        $instance->enrolperiod    = $data->enrolperiod;
        $instance->enrolstartdate = $data->enrolstartdate;
        $instance->enrolenddate   = $data->enrolenddate;
        $instance->customint1     = $data->customint1;
        $instance->timemodified   = time();
        $DB->update_record('enrol', $instance);
        if ($reset) {
            $context->mark_dirty();
        }
    } else {
        $fields = [
            'status' => $data->status, 'name' => $data->name, 'roleid' => $data->roleid, 'enrolperiod' => $data->enrolperiod,
            'enrolstartdate' => $data->enrolstartdate, 'enrolenddate' => $data->enrolenddate, 'customint1' => $data->customint1];
        $plugin->add_instance($course, $fields);
    }
    redirect($return);
}

$PAGE->set_heading($course->fullname);
$PAGE->set_title(get_string('pluginname', 'enrol_coursecompleted'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'enrol_coursecompleted'));
$mform->display();
echo $OUTPUT->footer();
