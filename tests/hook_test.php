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
 * coursecompleted enrolment plugin hook tests.
 *
 * @package   enrol_coursecompleted
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_coursecompleted;

use stdClass;

/**
 * coursecompleted enrolment plugin hook tests.
 *
 * @package   enrol_coursecompleted
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class hook_test extends \advanced_testcase {

    /** @var stdClass First course. */
    private $course1;

    /** @var stdClass Second course. */
    private $course2;

    /** @var stdClass Student. */
    private $student;

    /** @var \core\event\course_completed Event. */
    private $event;

    /** @var stdClass plugin. */
    private $plugin;

    /**
     * Setup to ensure that forms and locallib are loaded.
     */
    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');
        require_once($CFG->libdir . '/formslib.php');
        require_once($CFG->libdir . '/grouplib.php');
        require_once($CFG->dirroot . '/group/lib.php');
        parent::setUpBeforeClass();
    }

    /**
     * Tests initial setup.
     */
    protected function setUp(): void {
        global $CFG;
        parent::setUp();
        $CFG->enablecompletion = true;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $this->course1 = $generator->create_course(['enablecompletion' => 1]);
        $this->course2 = $generator->create_course(['enablecompletion' => 1]);
        $course3 = $generator->create_course(['enablecompletion' => 0]);
        $generator->create_and_enrol($this->course1, 'student');
        $generator->create_and_enrol($this->course2, 'student');
        $generator->create_and_enrol($course3, 'student');
        $generator->create_and_enrol($this->course1, 'teacher');
        $generator->create_and_enrol($this->course2, 'teacher');
        $this->student = $generator->create_and_enrol($this->course2, 'student');
        $this->plugin = enrol_get_plugin('coursecompleted');
        $this->event = \core\event\course_completed::create(
            [
                'objectid' => $this->course2->id,
                'relateduserid' => $this->student->id,
                'context' => \context_course::instance($this->course2->id),
                'courseid' => $this->course2->id,
                'other' => ['relateduserid' => $this->student->id],
            ]
        );
    }

    /**
     * Test disabled.
     * @covers \enrol_coursecompleted\hook_listener
     */
    public function test_disabled(): void {
        $sink = $this->redirectMessages();
        $this->plugin->add_instance(
            $this->course1,
            [
                'status' => ENROL_INSTANCE_DISABLED,
                'roleid' => 5,
                'customint1' => $this->course2->id,
                'customint2' => ENROL_DO_NOT_SEND_EMAIL,
                'customint4' => time() + 20,
            ]
        );
        $this->event->trigger();
        $messages = $sink->get_messages_by_component_and_type('moodle', 'enrolcoursewelcomemessage');
        $this->assertCount(0, $messages);
        $sink->close();
    }

    /**
     * Test enabled.
     * @covers \enrol_coursecompleted\hook_listener
     */
    public function test_enabled(): void {
        $sink = $this->redirectMessages();
        $this->plugin->add_instance(
            $this->course1,
            [
                'status' => ENROL_INSTANCE_ENABLED,
                'roleid' => 5,
                'customint1' => $this->course2->id,
                'customint2' => ENROL_SEND_EMAIL_FROM_NOREPLY,
            ]
        );
        $this->event->trigger();
        $context = \context_course::instance($this->course1->id);
        $this->assertTrue(user_has_role_assignment($this->student->id, 5, $context->id));
        $messages = $sink->get_messages_by_component_and_type('moodle', 'enrolcoursewelcomemessage');
        $this->assertCount(1, $messages);
        $this->assertEquals('Test course 1', $messages[0]->contexturlname);
        $this->assertStringContainsString('After successfully completing Test course 2', $messages[0]->fullmessagehtml);
        $sink->close();
    }

    /**
     * Test custommessage.
     * @covers \enrol_coursecompleted\hook_listener
     */
    public function test_custommessage(): void {
        $sink = $this->redirectMessages();
        $this->plugin->add_instance(
            $this->course1,
            [
                'status' => ENROL_INSTANCE_ENABLED,
                'roleid' => 5,
                'customint1' => $this->course2->id,
                'customint2' => ENROL_SEND_EMAIL_FROM_NOREPLY,
                'customtext1' => 'You completed {$a->completed}',
            ]
        );
        $this->event->trigger();
        $context = \context_course::instance($this->course1->id);
        $this->assertTrue(user_has_role_assignment($this->student->id, 5, $context->id));
        $messages = $sink->get_messages_by_component_and_type('moodle', 'enrolcoursewelcomemessage');
        $this->assertCount(1, $messages);
        $this->assertEquals('Test course 1', $messages[0]->contexturlname);
        $this->assertStringContainsString('You completed Test course 2', $messages[0]->fullmessagehtml);
        $sink->close();
    }

    /**
     * Test enabled no messages.
     * @covers \enrol_coursecompleted\hook_listener
     */
    public function test_enabled_nomessages(): void {
        $sink = $this->redirectMessages();
        $this->plugin->add_instance(
            $this->course1,
            [
                'status' => ENROL_INSTANCE_ENABLED,
                'roleid' => 5,
                'customint1' => $this->course2->id,
                'customint2' => ENROL_DO_NOT_SEND_EMAIL,
            ]
        );
        $this->event->trigger();
        $context = \context_course::instance($this->course1->id);
        $this->assertTrue(user_has_role_assignment($this->student->id, 5, $context->id));
        $messages = $sink->get_messages_by_component_and_type('moodle', 'enrolcoursewelcomemessage');
        $this->assertCount(0, $messages);
        $sink->close();
    }

    /**
     * Test enabled later messages.
     * @covers \enrol_coursecompleted\hook_listener
     */
    public function test_later_messages(): void {
        global $DB;
        $this->plugin->add_instance(
            $this->course1,
            [
                'status' => ENROL_INSTANCE_ENABLED,
                'roleid' => 5,
                'customint1' => $this->course2->id,
                'customint2' => ENROL_SEND_EMAIL_FROM_COURSE_CONTACT,
                'customint4' => time() + 66666666,
            ]
        );
        $this->event->trigger();
        $context = \context_course::instance($this->course1->id);
        $this->assertFalse(user_has_role_assignment($this->student->id, 5, $context->id));
        $this->assertEquals(4, $DB->count_records('course', []));
        delete_course($this->course2, false);
        delete_course($this->course1, false);
        $this->assertEquals(2, $DB->count_records('course', []));
    }

    /**
     * Test role.
     * @covers \enrol_coursecompleted\hook_listener
     */
    public function test_role(): void {
        $this->plugin->add_instance(
            $this->course1,
            [
                'status' => ENROL_INSTANCE_ENABLED,
                'roleid' => 6,
                'customint1' => $this->course2->id,
            ]
        );
        $this->event->trigger();
        $context = \context_course::instance($this->course1->id);
        $this->assertTrue(user_has_role_assignment($this->student->id, 6, $context->id));
    }

    /**
     * Test group.
     * @covers \enrol_coursecompleted\hook_listener
     */
    public function test_group(): void {
        [$groupid1, $groupid2] = $this->create_groups();
        $this->plugin->add_instance(
            $this->course1,
            [
                'status' => ENROL_INSTANCE_ENABLED,
                'roleid' => 5,
                'customint1' => $this->course2->id,
                'customint3' => true,
            ]
        );
        $this->event->trigger();
        $this->assertTrue(groups_is_member($groupid1, $this->student->id));
        $this->assertTrue(groups_is_member($groupid2, $this->student->id));
    }

    /**
     * Create groups.
     * @return array
     */
    private function create_groups(): array {
        $data = new \stdClass();
        $data->courseid = $this->course1->id;
        $data->idnumber = $this->course1->id . 'A';
        $data->name = 'A group';
        $data->description = '';
        $data->descriptionformat = FORMAT_HTML;
        $groupid1 = groups_create_group($data);
        $data = new \stdClass();
        $data->courseid = $this->course1->id;
        $data->idnumber = $this->course1->id . 'b';
        $data->name = 'B group';
        $data->description = '';
        $data->descriptionformat = FORMAT_HTML;
        groups_create_group($data);
        $data = new \stdClass();
        $data->courseid = $this->course2->id;
        $data->idnumber = $this->course2->id . 'A';
        $data->name = 'A group';
        $data->description = '';
        $data->descriptionformat = FORMAT_HTML;
        $groupid2 = groups_create_group($data);
        $this->getDataGenerator()->create_group_member(['groupid' => $groupid2, 'userid' => $this->student->id]);
        return [$groupid1, $groupid2];
    }
    /**
     * Test non group.
     * @covers \enrol_coursecompleted\hook_listener
     */
    public function test_non_group(): void {
        [$groupid1, $groupid2] = $this->create_groups();
        $this->plugin->add_instance(
            $this->course1,
            [
                'status' => ENROL_INSTANCE_ENABLED,
                'roleid' => 5,
                'customint1' => $this->course2->id,
                'customint3' => false,
            ]
        );
        $this->event->trigger();
        $this->assertFalse(groups_is_member($groupid1, $this->student->id));
        $this->assertTrue(groups_is_member($groupid2, $this->student->id));
    }

    /**
     * Test delete.
     * @covers \enrol_coursecompleted\hook_listener
     */
    public function test_delete(): void {
        global $DB;
        $this->plugin->add_instance(
            $this->course1,
            [
                'status' => ENROL_INSTANCE_ENABLED,
                'roleid' => 5,
                'customint1' => $this->course2->id,
            ]
        );
        $this->plugin->add_instance(
            $this->course1,
            [
                'status' => ENROL_INSTANCE_ENABLED,
                'roleid' => 5,
                'customint1' => $this->course1->id,
            ]
        );
        $this->event->trigger();
        $this->assertEquals(2, $DB->count_records('enrol', ['enrol' => 'coursecompleted']));
        $this->assertEquals(4, $DB->count_records('course', []));
        delete_course($this->course1, false);
        $this->assertEquals(3, $DB->count_records('course', []));
        delete_course($this->course2, false);
        $this->assertEquals(2, $DB->count_records('course', []));
        $this->assertEquals(0, $DB->count_records('enrol', ['enrol' => 'coursecompleted']));
    }

    /**
     * Test future delete.
     * @covers \enrol_coursecompleted\hook_listener
     */
    public function test_future_delete(): void {
        global $DB;
        $next = time() + 66666666;
        $this->plugin->add_instance(
            $this->course1,
            [
                'status' => ENROL_INSTANCE_ENABLED,
                'roleid' => 5,
                'customint1' => $this->course2->id,
                'customint4' => $next,
            ]
        );
        $this->event->trigger();
        $this->assertEquals(1, $DB->count_records('enrol', ['enrol' => 'coursecompleted']));
        $recs = $DB->get_records('task_adhoc', ['component' => 'enrol_coursecompleted']);
        $this->assertEquals(1, count($recs));
        foreach ($recs as $rec) {
            $this->assertEquals($rec->component, 'enrol_coursecompleted');
            $this->assertEquals($rec->classname, '\enrol_coursecompleted\task\process_future');
            $this->assertEquals($rec->nextruntime, $next);
            $this->assertEquals($rec->userid, $this->student->id);
            $this->assertEquals($rec->attemptsavailable, 12);
        }
        $this->assertEquals(4, $DB->count_records('course', []));
        delete_course($this->course2, false);
        $this->assertEquals(3, $DB->count_records('course', []));
        delete_course($this->course1, false);
        $this->assertEquals(2, $DB->count_records('course', []));
        $this->assertEquals(3, $DB->count_records('enrol', []));
        $this->assertEquals(1, $DB->count_records('user_enrolments', []));
        $this->assertEquals(0, $DB->count_records('task_adhoc', ['component' => 'enrol_coursecompleted']));
    }
}
