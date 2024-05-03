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

namespace enrol_coursecompleted;

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
final class other_test extends \advanced_testcase {
    /**
     * Tests initial setup.
     */
    protected function setUp(): void {
        global $CFG;
        $CFG->enablecompletion = true;
        $this->resetAfterTest(true);
        $enabled = enrol_get_plugins(true);
        $enabled['coursecompleted'] = true;
        set_config('enrol_plugins_enabled', implode(',', array_keys($enabled)));
    }

    /**
     * Basic test.
     * @covers \enrol_coursecompleted_plugin
     */
    public function test_basics(): void {
        $this->assertTrue(enrol_is_enabled('coursecompleted'));
        $this->assertEquals(ENROL_EXT_REMOVED_SUSPENDNOROLES, get_config('enrol_coursecompleted', 'expiredaction'));
        $plugin = enrol_get_plugin('coursecompleted');
        $this->assertNotEmpty($plugin);
        $this->assertInstanceOf('\enrol_coursecompleted_plugin', $plugin);
    }

    /**
     * Test other files.
     * @covers \enrol_coursecompleted_plugin
     */
    public function test_files(): void {
        global $CFG;
        include($CFG->dirroot . '/enrol/coursecompleted/db/tasks.php');
        include($CFG->dirroot . '/enrol/coursecompleted/db/access.php');
        include($CFG->dirroot . '/enrol/coursecompleted/db/events.php');
    }

    /**
     * Test invalid instance.
     * @covers \enrol_coursecompleted_plugin
     */
    public function test_invalid_instance(): void {
        $plugin = enrol_get_plugin('coursecompleted');
        $tst = new \stdClass();
        $tst->enrol = 'wrong';
        $this->expectException('moodle_exception');
        $this->expectExceptionMessage('invalid enrol instance!');
        $this->assertCount(0, $plugin->get_action_icons($tst));
    }

    /**
     * Test disabled.
     * @covers \enrol_coursecompleted_plugin
     * @covers \enrol_coursecompleted\observer
     * @covers \enrol_coursecompleted\user_enrolment_callbacks
     */
    public function test_disabled(): void {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');
        $generator = $this->getDataGenerator();
        $course1 = $generator->create_course(['enablecompletion' => 1]);
        $course2 = $generator->create_course(['enablecompletion' => 1]);
        $plugin = enrol_get_plugin('coursecompleted');
        $this->setAdminUser();

        $student1 = $generator->create_and_enrol($course1, 'student')->id;
        $student2 = $generator->create_and_enrol($course1, 'student')->id;
        $plugin->add_instance($course1, ['customint1' => $course2->id, 'roleid' => 5, 'enrolstartdate' => time() + 66666666]);
        $compevent = \core\event\course_completed::create(
            [
                'objectid' => $course2->id,
                'relateduserid' => $student1,
                'context' => \context_course::instance($course2->id),
                'courseid' => $course2->id,
                'other' => ['relateduserid' => $student1],
            ]
        );
        $observer = new observer();

        $observer->enroluser($compevent);
        $plugin->add_instance($course1, ['customint1' => $course2->id, 'roleid' => 5, 'enrolenddate' => time() - 66666666]);
        $compevent = \core\event\course_completed::create(
            [
                'objectid' => $course2->id,
                'relateduserid' => $student2,
                'context' => \context_course::instance($course2->id),
                'courseid' => $course2->id,
                'other' => ['relateduserid' => $student2],
            ]
        );
        $observer = new observer();
        $observer->enroluser($compevent);
    }

    /**
     * Test static enrol from past.
     * @covers \enrol_coursecompleted_plugin
     */
    public function test_static_past(): void {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');
        $generator = $this->getDataGenerator();
        $course1 = $generator->create_course(['enablecompletion' => 1]);
        $course2 = $generator->create_course(['enablecompletion' => 1]);
        $studentid = $generator->create_and_enrol($course1, 'student')->id;

        $plugin = enrol_get_plugin('coursecompleted');
        $this->setAdminUser();
        $ccompletion = new \completion_completion(['course' => $course1->id, 'userid' => $studentid]);
        $ccompletion->mark_complete(time());
        $ccompletion = new \completion_completion(['course' => $course2->id, 'userid' => $studentid]);
        $ccompletion->mark_complete(time());
        mark_user_dirty($studentid);
        $plugin->add_instance($course1, ['customint1' => $course2->id, 'roleid' => 5, 'enrolperiod' => 300000]);
        \enrol_coursecompleted_plugin::enrol_past($course1->id);
        \enrol_coursecompleted_plugin::enrol_past($course2->id);
    }

    /**
     * Test invalid role.
     * @covers \enrol_coursecompleted_plugin
     * @covers \enrol_coursecompleted\observer
     * @covers \enrol_coursecompleted\user_enrolment_callbacks
     */
    public function test_invalid_role(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $plugin = enrol_get_plugin('coursecompleted');
        $course1 = $generator->create_course(['shortname' => 'B1', 'enablecompletion' => 1]);
        $course2 = $generator->create_course(['shortname' => 'B2', 'enablecompletion' => 1]);
        $this->setAdminUser();
        $plugin->add_instance($course1, ['customint1' => $course2->id, 'roleid' => 9999]);
        $instance = $DB->get_record('enrol', ['courseid' => $course1->id, 'enrol' => 'coursecompleted'], '*', MUST_EXIST);
        $this->assertCount(2, $plugin->build_course_path($instance));
        $studentid = $generator->create_and_enrol($course2, 'student')->id;
        $compevent = \core\event\course_completed::create(
            [
                'objectid' => $course2->id,
                'relateduserid' => $studentid,
                'context' => \context_course::instance($course2->id),
                'courseid' => $course2->id,
                'other' => ['relateduserid' => $studentid],
            ]
        );
        $observer = new observer();
        $observer->enroluser($compevent);
        $this->assertDebuggingCalled('Role does not exist');
    }

    /**
     * Test group member.
     * @covers \enrol_coursecompleted\observer
     * @covers \enrol_coursecompleted\user_enrolment_callbacks
     * @covers \enrol_coursecompleted_plugin
     */
    public function test_groups_child(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $plugin = enrol_get_plugin('coursecompleted');
        $course1 = $generator->create_course(['shortname' => 'B1', 'enablecompletion' => 1]);
        $data = new \stdClass();
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
        $studentid = $generator->create_and_enrol($course2)->id;
        groups_add_member($groupid2, $studentid);
        rebuild_course_cache($course2->id, true);
        $compevent = \core\event\course_completed::create(
            [
                'objectid' => $course2->id,
                'relateduserid' => $studentid,
                'context' => \context_course::instance($course2->id),
                'courseid' => $course2->id,
                'other' => ['relateduserid' => $studentid],
            ]
        );
        $observer = new observer();
        $observer->enroluser($compevent);
        $this->assertTrue(groups_is_member($groupid2, $studentid));
        rebuild_course_cache($course1->id, true);
        rebuild_course_cache($course2->id, true);
        // TODO: Why is hook not working?
        $this->assertTrue(groups_is_member($groupid1, $studentid));
    }

    /**
     * Test expiration task.
     * @covers \enrol_coursecompleted\task\process_expirations
     */
    public function test_task(): void {
        $task = new \enrol_coursecompleted\task\process_expirations();
        $this->assertEquals('Course completed enrolment expiry task', $task->get_name());
        ob_start();
        $task->execute();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertStringContainsString('No expired enrol_coursecompleted enrolments detected', $output);
    }

    /**
     * Test welcome sending of welcome messages.
     * @covers \enrol_coursecompleted\user_enrolment_callbacks
     */
    public function test_email_welcome_message(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $messagesink = $this->redirectMessages();
        $plugin = enrol_get_plugin('coursecompleted');
        $student = $generator->create_user();
        $course1 = $generator->create_course(['shortname' => 'B1', 'enablecompletion' => 1]);
        $course2 = $generator->create_course(['shortname' => 'B2', 'enablecompletion' => 1]);
        $course3 = $generator->create_course(['shortname' => 'B3', 'enablecompletion' => 1]);
        $plugin->add_instance($course1, ['customint1' => $course2->id, 'roleid' => 5, 'customint2' => 1, 'customtext1' => 'boe']);
        $plugin->add_instance($course2, ['customint1' => $course3->id, 'roleid' => 5, 'customint2' => 1]);
        $plugin->add_instance($course3, ['customint1' => $course1->id, 'roleid' => 5, 'customint2' => 1, 'customtext1' => '
{$a->fullname} <b>boe</b>
<a>another line</a>']
        );
        $instances = $DB->get_records('enrol', ['enrol' => 'coursecompleted']);
        foreach ($instances as $instance) {
            $plugin->enrol_user($instance, $student->id);
        }
        $messages = $messagesink->get_messages_by_component_and_type(
            'moodle',
            'enrolcoursewelcomemessage',
        );
        $this->assertNotEmpty($messages);
        $this->assertStringContainsString($course1->fullname, $messages[0]->subject);
        $this->assertStringContainsString($course2->fullname, $messages[1]->subject);
        $this->assertStringContainsString($course3->fullname, $messages[2]->subject);
        $this->assertEquals('boe', $messages[0]->fullmessagehtml);
        $this->assertStringContainsString($course3->fullname, $messages[1]->fullmessagehtml);
        $this->assertStringContainsString(fullname($student), $messages[2]->fullmessagehtml);
        $messagesink->close();
    }

    /**
     * Test access.
     * @covers \enrol_coursecompleted_plugin
     */
    public function test_access(): void {
        global $CFG;
        $CFG->enablecompletion = true;
        $this->resetAfterTest(true);
        $enabled = enrol_get_plugins(true);
        $enabled['coursecompleted'] = true;
        set_config('enrol_plugins_enabled', implode(',', array_keys($enabled)));
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1]);
        $student = $generator->create_and_enrol($course, 'student');
        $editor = $generator->create_and_enrol($course, 'editingteacher');
        $this->setAdminUser();
        $context = \context_course::instance($course->id);
        $this->assertTrue(has_capability('enrol/coursecompleted:config', $context));
        $this->assertTrue(has_capability('enrol/coursecompleted:enrolpast', $context));
        $this->assertTrue(has_capability('enrol/coursecompleted:manage', $context));
        $this->assertTrue(has_capability('enrol/coursecompleted:unenrol', $context));
        $this->assertTrue(has_capability('enrol/coursecompleted:unenrolself', $context));
        $this->setUser($student);
        $this->assertFalse(has_capability('enrol/coursecompleted:config', $context));
        $this->assertFalse(has_capability('enrol/coursecompleted:enrolpast', $context));
        $this->assertFalse(has_capability('enrol/coursecompleted:manage', $context));
        $this->assertFalse(has_capability('enrol/coursecompleted:unenrol', $context));
        $this->assertFalse(has_capability('enrol/coursecompleted:unenrolself', $context));
        $this->setUser($editor);
        $this->assertTrue(has_capability('enrol/coursecompleted:config', $context));
        $this->assertFalse(has_capability('enrol/coursecompleted:enrolpast', $context));
        $this->assertTrue(has_capability('enrol/coursecompleted:manage', $context));
        $this->assertTrue(has_capability('enrol/coursecompleted:unenrol', $context));
        $this->assertFalse(has_capability('enrol/coursecompleted:unenrolself', $context));
    }
}
