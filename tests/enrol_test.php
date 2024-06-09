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
 * coursecompleted enrolment plugin tests.
 *
 * @package   enrol_coursecompleted
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_coursecompleted;

use advanced_testcase;
use stdClass;

/**
 * coursecompleted enrolment plugin tests.
 *
 * @package   enrol_coursecompleted
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \enrol_coursecompleted_plugin
 */
final class enrol_test extends advanced_testcase {
    /** @var stdClass Instance. */
    private $instance;

    /** @var stdClass Student. */
    private $student;

    /** @var stdClass First course. */
    private $course1;

    /** @var stdClass Second course. */
    private $course2;

    /** @var stdClass Third course. */
    private $course3;

    /** @var stdClass Last course. */
    private $course4;

    /** @var stdClass Plugin. */
    private $plugin;

    /**
     * Setup to ensure that forms and locallib are loaded.
     */
    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->libdir . '/formslib.php');
        require_once($CFG->dirroot . '/enrol/locallib.php');
        parent::setUpBeforeClass();
    }

    /**
     * Tests initial setup.
     */
    protected function setUp(): void {
        global $CFG, $DB;
        parent::setUp();

        $CFG->enablecompletion = true;
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();
        $this->course1 = $generator->create_course(['shortname' => 'A1', 'enablecompletion' => 1]);
        $this->course2 = $generator->create_course(['shortname' => 'A2', 'enablecompletion' => 1]);
        $this->course3 = $generator->create_course(['shortname' => 'A3', 'enablecompletion' => 1]);
        $this->course4 = $generator->create_course(['shortname' => 'A4', 'enablecompletion' => 1]);
        $course5 = $generator->create_course(['shortname' => 'A5', 'enablecompletion' => 1]);
        $course6 = $generator->create_course(['shortname' => 'A6', 'enablecompletion' => 1]);
        $studentrole = $DB->get_field('role', 'id', ['shortname' => 'student']);
        $this->setAdminUser();
        $this->plugin = enrol_get_plugin('coursecompleted');
        $id = $this->plugin->add_instance(
            $this->course2,
            [
                'status' => ENROL_INSTANCE_ENABLED,
                'customint1' => $this->course1->id,
                'customint2' => 1,
                'roleid' => $studentrole,
            ]
        );
        $this->instance = $DB->get_record('enrol', ['id' => $id]);
        $this->plugin->add_instance(
            $this->course3,
            [
                'customint1' => $this->course2->id,
                'roleid' => $studentrole,
            ]
        );
        $this->plugin->add_instance(
            $this->course4,
            [
                'customint1' => $this->course3->id,
                'roleid' => $studentrole,
            ]
        );
        $this->plugin->add_instance(
            $course5,
            [
                'customint1' => $this->course4->id,
                'roleid' => $studentrole,
            ]
        );
        $this->plugin->add_instance(
            $course6,
            [
                'customint1' => $course5->id,
                'roleid' => $studentrole,
            ]
        );
        $this->plugin->add_instance(
            $this->course4,
            [
                'customint1' => $course6->id,
                'roleid' => $studentrole,
            ]
        );

        $this->student = $generator->create_and_enrol($this->course1, 'student');
    }

    /**
     * Test if user is enrolled after completing a course.
     * @covers \enrol_coursecompleted\observer
     * @covers \enrol_coursecompleted\hook_listener
     */
    public function test_event_enrolled(): void {
        global $PAGE;
        $PAGE->set_url('/enrol/editinstance.php');
        $manager1 = new \course_enrolment_manager($PAGE, $this->course1);
        $this->assertCount(1, $manager1->get_user_enrolments($this->student->id));
        $this->assertfalse($this->plugin->has_bulk_operations($manager1));
        $this->assertCount(0, $this->plugin->get_bulk_operations($manager1));
        $manager2 = new \course_enrolment_manager($PAGE, $this->course2);
        $this->assertCount(0, $manager2->get_user_enrolments($this->student->id));
        $this->assertTrue($this->plugin->has_bulk_operations($manager2));
        $this->assertCount(2, $this->plugin->get_bulk_operations($manager2));
        $compevent = \core\event\course_completed::create(
            [
                'objectid' => $this->course2->id,
                'relateduserid' => $this->student->id,
                'context' => \context_course::instance($this->course1->id),
                'courseid' => $this->course1->id,
                'other' => ['relateduserid' => $this->student->id],
            ]
        );
        $observer = new observer();
        $observer->enroluser($compevent);
        $this->assertTrue(is_enrolled(\context_course::instance($this->course1->id), $this->student->id));
        $this->assertTrue(is_enrolled(\context_course::instance($this->course2->id), $this->student->id));
        $this->assertCount(1, $manager1->get_user_enrolments($this->student->id));
        $this->assertCount(1, $manager2->get_user_enrolments($this->student->id));
    }

    /**
     * Test if user is enrolled after completing a course.
     * @covers \enrol_coursecompleted_plugin
     * @covers \enrol_coursecompleted\hook_listener
     */
    public function test_enrolled_after_completion(): void {
        global $PAGE;
        $manager = new \course_enrolment_manager($PAGE, $this->course2);
        $this->assertCount(0, $manager->get_user_enrolments($this->student->id));
        $ccompletion = new \completion_completion(['course' => $this->course1->id, 'userid' => $this->student->id]);
        $ccompletion->mark_complete(time());
        $this->assertEquals(
            '100',
            \core_completion\progress::get_course_progress_percentage($this->course1, $this->student->id)
        );
        $manager = new \course_enrolment_manager($PAGE, $this->course2);
        $this->assertCount(1, $manager->get_user_enrolments($this->student->id));
    }

    /**
     * Test ue.
     * @covers \enrol_coursecompleted_plugin
     */
    public function test_user_edit(): void {
        global $PAGE;
        $ccompletion = new \completion_completion(['course' => $this->course1->id, 'userid' => $this->student->id]);
        $ccompletion->mark_complete(time());
        $this->assertEquals(
            '100',
            \core_completion\progress::get_course_progress_percentage($this->course1, $this->student->id)
        );
        $this->setAdminUser();
        $context = \context_course::instance($this->course1->id);
        $this->assertTrue(has_capability('report/completion:view', $context));
        $url = new \moodle_url('/user/index.php', ['id' => $this->course2->id]);
        $PAGE->set_url($url);
        $manager = new \course_enrolment_manager($PAGE, $this->course2);
        $enrolments = $manager->get_user_enrolments($this->student->id);
        $this->assertCount(1, $enrolments);
        foreach ($enrolments as $enrolment) {
            if ($enrolment->enrolmentinstance->enrol == 'coursecompleted') {
                $actions = $this->plugin->get_user_enrolment_actions($manager, $enrolment);
                $this->assertCount(3, $actions);
                $this->assertEquals('Edit enrolment', $actions[0]->get_title());
                $this->assertEquals('Unenrol', $actions[1]->get_title());
                $this->assertEquals('Course completion', $actions[2]->get_title());
                $this->assertTrue($this->plugin->has_bulk_operations($manager));
                $operations = $this->plugin->get_bulk_operations($manager, null);
                $this->assertCount(2, $operations);
            }
        }
    }

    /**
     * Test builld course path.
     * @covers \enrol_coursecompleted_plugin
     */
    public function test_build_course_path(): void {
        global $DB;
        $plug = enrol_get_plugin('coursecompleted');
        $instances = $DB->get_records('enrol', ['enrol' => 'coursecompleted']);
        foreach ($instances as $instance) {
            $path = \phpunit_util::call_internal_method($plug, 'build_course_path', [$instance], '\enrol_coursecompleted_plugin');
            $this->assertGreaterThan(4, $path);
        }
    }

    /**
     * Test library.
     * @covers \enrol_coursecompleted_plugin
     * @covers \enrol_coursecompleted\observer
     * @covers \enrol_coursecompleted\task\process_future
     */
    public function test_library_functions(): void {
        global $DB;
        $studentrole = $DB->get_field('role', 'id', ['shortname' => 'student']);
        $this->assertEquals($this->plugin->get_name(), 'coursecompleted');
        $this->assertEquals($this->plugin->get_config('enabled'), null);
        $this->assertTrue($this->plugin->roles_protected());
        $this->assertTrue($this->plugin->can_add_instance($this->course2->id));
        $this->assertTrue($this->plugin->allow_unenrol($this->instance));
        $this->assertTrue($this->plugin->allow_manage($this->instance));
        $this->assertTrue($this->plugin->can_hide_show_instance($this->instance));
        $this->assertTrue($this->plugin->can_delete_instance($this->instance));
        $this->assertTrue($this->plugin->show_enrolme_link($this->instance));
        $this->assertCount(1, $this->plugin->get_info_icons([$this->instance]));
        $this->assertCount(2, $this->plugin->get_action_icons($this->instance));
        $this->assertEquals('After completing course: A1', $this->plugin->get_instance_name($this->instance));
        $this->assertEquals('Deleted course unknown', $this->plugin->get_instance_name(null));
        $this->assertEquals(
            'Enrolment by completion of course with id ' . $this->course1->id,
            $this->plugin->get_description_text($this->instance)
        );
        $this->assertStringContainsString('Test course 1', $this->plugin->enrol_page_hook($this->instance));
        $arr = ['status' => 0, 'customint4' => 666, 'enrolenddate' => time(), 'enrolstartdate' => time() + 10000];
        $tmp = $this->plugin->edit_instance_validation($arr, null, $this->instance, null);
        $this->assertEquals('The specified course does not exist', $tmp['customint1']);
        $this->assertEquals('The enrolment end date cannot be earlier than the start date.', $tmp['enrolenddate']);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['shortname' => 'c1', 'enablecompletion' => 1]);
        $tmp = $this->plugin->edit_instance_validation(['status' => 0, 'customint1' => $course->id], null, $this->instance, null);
        $this->assertEquals([], $tmp);
        $this->setUser(1);
        $this->assertStringContainsString('Test course 1', $this->plugin->enrol_page_hook($this->instance));
        $this->assertCount(1, $this->plugin->get_info_icons([$this->instance]));
        $this->setUser($this->student);
        $this->assertCount(1, $this->plugin->get_info_icons([$this->instance]));
        $page = new \moodle_page();
        $page->set_context(\context_course::instance($this->course1->id));
        $page->set_course($this->course1);
        $page->set_pagelayout('standard');
        $page->set_pagetype('course-view');
        $page->set_url('/enrol/index.php?id=' . $this->course1->id);
        $this->assertfalse($this->plugin->can_add_instance($this->course1->id));
        $this->assertfalse($this->plugin->allow_unenrol($this->instance));
        $this->assertfalse($this->plugin->allow_manage($this->instance));
        $this->assertfalse($this->plugin->can_hide_show_instance($this->instance));
        $this->assertfalse($this->plugin->can_delete_instance($this->instance));
        $this->assertStringContainsString('Test course 1', $this->plugin->enrol_page_hook($this->instance));
        $compevent = \core\event\course_completed::create(
            [
                'objectid' => $this->course2->id,
                'relateduserid' => $this->student->id,
                'context' => \context_course::instance($this->course2->id),
                'courseid' => $this->course2->id,
                'other' => ['relateduserid' => $this->student->id],
            ]
        );
        $observer = new observer();
        $observer->enroluser($compevent);
        $tmp = $this->plugin->enrol_page_hook($this->instance);
        $this->assertStringContainsString('Test course 1', $tmp);
        $this->assertStringContainsString('You will be enrolled in this course when you complete course', $tmp);
        $this->assertCount(1, $this->plugin->get_info_icons([$this->instance]));
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $this->course2->id, $studentrole);
        mark_user_dirty($student->id);
        $this->setUser($student);
        $this->assertCount(1, $this->plugin->get_info_icons([$this->instance]));
        $this->assertCount(0, $this->plugin->get_action_icons($this->instance));
        $tmp = $this->plugin->enrol_page_hook($this->instance);
        $this->assertStringContainsString('Test course 1', $tmp);
        $this->assertStringContainsString('You will be enrolled in this course when you complete course', $tmp);
        $this->instance->enrolstartdate = time() + 6666;
        $tmp = $this->plugin->enrol_page_hook($this->instance);
        $this->assertStringNotContainsString('Test course 1', $tmp);
        $this->assertStringNotContainsString('You will be enrolled in this course when you complete course', $tmp);
    }

    /**
     * Test form.
     * @covers \enrol_coursecompleted_plugin
     */
    public function test_form(): void {
        $page = new \moodle_page();
        $context = \context_course::instance($this->course1->id);
        $page->set_context($context);
        $page->set_course($this->course1);
        $page->set_pagelayout('standard');
        $page->set_pagetype('course-view');
        $page->set_url('/enrol/coursecompleted/manage.php?enrolid=' . $this->instance->id);
        $form = $this->tempform();
        $mform = $form->getform();
        $this->plugin->edit_instance_form($this->instance, $mform, $context);
        $this->assertStringContainsString('Required field', $mform->getReqHTML());
        ob_start();
        $mform->display();
        $html = ob_get_clean();
        $strm = get_string_manager();
        $arr = ['compcourse', 'customwelcome', 'enrolenddate', 'enrolstartdate', 'group'];
        foreach ($arr as $value) {
            if ($strm->string_exists($value, 'enrol_coursecompleted')) {
                $this->assertStringContainsString(get_string($value, 'enrol_coursecompleted'), $html);
            }
            if ($strm->string_exists($value . '_desc', 'enrol_coursecompleted')) {
                $this->assertStringContainsString(get_string($value . '_desc', 'enrol_coursecompleted'), $html);
            }
        }
    }

    /**
     * Test form.
     * @covers \enrol_coursecompleted_plugin
     * @return \moodleform
     */
    private function tempform() {
        /**
         * coursecompleted enrolment form tests.
         *
         * @package   enrol_coursecompleted
         * @copyright eWallah (www.eWallah.net)
         * @author    Renaat Debleu <info@eWallah.net>
         * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
         */
        return new class extends \moodleform {
            /**
             * Form definition.
             */
            public function definition() {
                // No definition required.
            }
            /**
             * Returns form reference
             * @return MoodleQuickForm
             */
            public function getform() {
                $mform = $this->_form;
                // Simulate submission.
                $mform->_flagSubmitted = true;
                return $mform;
            }
        };
    }
}
