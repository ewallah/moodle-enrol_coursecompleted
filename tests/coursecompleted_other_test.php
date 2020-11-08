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
 * coursecompleted enrolment plugin other tests.
 *
 * @package   enrol_coursecompleted
 * @copyright 2017 eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/grouplib.php');
require_once($CFG->dirroot . '/group/lib.php');

/**
 * coursecompleted enrolment plugin other tests.
 *
 * @package   enrol_coursecompleted
 * @copyright 2017 eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \enrol_coursecompleted_plugin
 */
class enrol_coursecompleted_other_testcase extends advanced_testcase {

    /**
     * Tests initial setup.
     */
    protected function setUp():void {
        global $CFG;
        $CFG->enablecompletion = true;
        $this->resetAfterTest(true);
        $enabled = enrol_get_plugins(true);
        $enabled['coursecompleted'] = true;
        set_config('enrol_plugins_enabled', implode(',', array_keys($enabled)));
    }

    /**
     * Basic test.
     * @coversDefaultClass \enrol_coursecompleted_plugin
     */
    public function test_basics() {
        $this->assertTrue(enrol_is_enabled('coursecompleted'));
        $this->assertEquals(ENROL_EXT_REMOVED_SUSPENDNOROLES, get_config('enrol_coursecompleted', 'expiredaction'));
        $plugin = enrol_get_plugin('coursecompleted');
        $this->assertNotEmpty($plugin);
        $this->assertInstanceOf('enrol_coursecompleted_plugin', $plugin);
    }

    /**
     * Test other files.
     * @coversDefaultClass \enrol_coursecompleted_plugin
     */
    public function test_files() {
        global $CFG;
        include($CFG->dirroot . '/enrol/coursecompleted/db/tasks.php');
        include($CFG->dirroot . '/enrol/coursecompleted/db/access.php');
        include($CFG->dirroot . '/enrol/coursecompleted/db/events.php');
    }

    /**
     * Test invalid instance.
     * @coversDefaultClass \enrol_coursecompleted_plugin
     */
    public function test_invalid_instance() {
        $plugin = enrol_get_plugin('coursecompleted');
        $tst = new stdClass();
        $tst->enrol = 'wrong';
        $this->expectException('moodle_exception');
        $this->expectExceptionMessage('invalid enrol instance!');
        $this->assertCount(0, $plugin->get_action_icons($tst));
    }

    /**
     * Test invalid role.
     * @coversDefaultClass \enrol_coursecompleted_observer
     */
    public function test_invalid_role() {
        global $DB;
        $generator = $this->getDataGenerator();
        $plugin = enrol_get_plugin('coursecompleted');
        $studentid = $generator->create_user()->id;
        $course1 = $generator->create_course(['shortname' => 'B1', 'enablecompletion' => 1]);
        $course2 = $generator->create_course(['shortname' => 'B2', 'enablecompletion' => 1]);
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->setAdminUser();
        $plugin->add_instance($course1, ['customint1' => $course2->id, 'roleid' => 9999]);
        $instance = $DB->get_record('enrol', ['courseid' => $course1->id, 'enrol' => 'coursecompleted'], '*', MUST_EXIST);
        $this->assertCount(2, $plugin->build_course_path($instance));
        $manualplugin = enrol_get_plugin('manual');
        $instance = $DB->get_record('enrol', ['courseid' => $course2->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $manualplugin->enrol_user($instance, $studentid, $studentrole->id);
        $compevent = \core\event\course_completed::create([
            'objectid' => $course2->id,
            'relateduserid' => $studentid,
            'context' => context_course::instance($course2->id),
            'courseid' => $course2->id,
            'other' => ['relateduserid' => $studentid]]);
        $observer = new enrol_coursecompleted_observer();
        $observer->enroluser($compevent);
        $this->assertDebuggingCalled("Role does not exist");
    }

    /**
     * Test group member.
     * @coversDefaultClass \enrol_coursecompleted_observer
     */
    public function test_groups_child() {
        global $DB;
        $generator = $this->getDataGenerator();
        $plugin = enrol_get_plugin('coursecompleted');
        $studentid = $generator->create_user()->id;
        $course1 = $generator->create_course(['shortname' => 'B1', 'enablecompletion' => 1]);
        $data = new stdClass();
        $data->courseid = $course1->id;
        $data->idnumber = $course1->id . 'A';
        $data->name = 'A group';
        $data->description = '';
        $data->descriptionformat = FORMAT_HTML;
        $groupid1 = groups_create_group($data);
        rebuild_course_cache($course1->id, true);
        $course2 = $generator->create_course(['shortname' => 'B2', 'enablecompletion' => 1]);
        $data->courseid = $course2->id;
        $data->idnumber = $course2->id . 'A';
        $groupid2 = groups_create_group($data);
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->setAdminUser();
        $plugin->add_instance($course1, ['customint1' => $course2->id, 'roleid' => $studentrole->id]);
        $instance = $DB->get_record('enrol', ['courseid' => $course1->id, 'enrol' => 'coursecompleted'], '*', MUST_EXIST);
        $this->assertCount(2, $plugin->build_course_path($instance));
        $manualplugin = enrol_get_plugin('manual');
        $instance = $DB->get_record('enrol', ['courseid' => $course2->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $manualplugin->enrol_user($instance, $studentid, $studentrole->id);
        groups_add_member($groupid2, $studentid);
        rebuild_course_cache($course2->id, true);
        $compevent = \core\event\course_completed::create([
            'objectid' => $course2->id,
            'relateduserid' => $studentid,
            'context' => context_course::instance($course2->id),
            'courseid' => $course2->id,
            'other' => ['relateduserid' => $studentid]]);
        $observer = new enrol_coursecompleted_observer();
        $observer->enroluser($compevent);
        $this->assertTrue(groups_is_member($groupid2, $studentid));
        rebuild_course_cache($course1->id, true);
        rebuild_course_cache($course2->id, true);
        $this->assertTrue(groups_is_member($groupid1, $studentid));
    }

    /**
     * Test expiration task.
     * @coversDefaultClass \enrol_coursecompleted\task\process_expirations
     */
    public function test_task() {
        $task = new \enrol_coursecompleted\task\process_expirations;
        $this->assertEquals('Course completed enrolment expiry task', $task->get_name());
        ob_start();
        $task->execute();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertStringContainsString('No expired enrol_coursecompleted enrolments detected', $output);
    }

    /**
     * Test adhoc sending of welcome messages.
     * @coversDefaultClass enrol_coursecompleted\task\send_welcome
     */
    public function  test_adhoc_email_welcome_message() {
        global $DB;
        $generator = $this->getDataGenerator();
        $sink = $this->redirectEmails();
        $plugin = enrol_get_plugin('coursecompleted');
        $studentid = $generator->create_user()->id;
        $course = $generator->create_course(['shortname' => 'B0', 'enablecompletion' => 1]);
        $courseid1 = $generator->create_course(['shortname' => 'B1', 'enablecompletion' => 1])->id;
        $courseid2 = $generator->create_course(['shortname' => 'B2', 'enablecompletion' => 1])->id;
        $courseid3 = $generator->create_course(['shortname' => 'B3', 'enablecompletion' => 1])->id;
        $courseid4 = $generator->create_course(['shortname' => 'B4', 'enablecompletion' => 1])->id;
        $plugin->add_instance($course, ['customint1' => $courseid1, 'roleid' => 5, 'customint2' => 0]);
        $i2 = $plugin->add_instance($course, ['customint1' => $courseid2, 'roleid' => 5, 'customint2' => 1]);
        $i3 = $plugin->add_instance($course, ['customint1' => $courseid3, 'customtext1' => 'boe', 'customint2' => 1]);
        $i4 = $plugin->add_instance($course,
           ['customint1' => $courseid4, 'customtext1' => '{$a->fullname} <b>boe</b>', 'customint2' => 1]);
        $compevent = \core\event\course_completed::create([
            'objectid' => $courseid1,
            'relateduserid' => $studentid,
            'context' => \context_course::instance($courseid1),
            'courseid' => $courseid1,
            'other' => ['relateduserid' => $studentid]]);
        $observer = new \enrol_coursecompleted_observer();
        $observer->enroluser($compevent);
        $adhock = new \enrol_coursecompleted\task\send_welcome();
        $adhock->set_custom_data(
             ['userid' => $studentid, 'enrolid' => $i2, 'courseid' => $course->id, 'completedid' => $courseid2]);
        $adhock->set_component('enrol_coursecompleted');
        $adhock->execute();
        \core\task\manager::queue_adhoc_task($adhock);
        $adhock->set_custom_data(
              ['userid' => $studentid, 'enrolid' => $i3, 'courseid' => $course->id, 'completedid' => $courseid3]);
        \core\task\manager::queue_adhoc_task($adhock);
        $adhock->set_custom_data(
              ['userid' => $studentid, 'enrolid' => $i4, 'courseid' => $course->id, 'completedid' => $courseid4]);
        \core\task\manager::queue_adhoc_task($adhock);
        $this->assertCount(3, $DB->get_records('task_adhoc', ['component' => 'enrol_coursecompleted']));
        phpunit_util::run_all_adhoc_tasks();
        $messages = $sink->get_messages();
        $this->assertCount(4, $messages);
        $sink->close();
        foreach ($messages as $message) {
            $this->assertStringNotContainsString('{a->', $message->header);
            $this->assertStringNotContainsString('{a->', $message->body);
        }
        $this->assertCount(0, $DB->get_records('task_adhoc', ['component' => 'enrol_coursecompleted']));
    }
}
