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
use context_course;
use moodle_page;
use moodle_url;
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

    /** @var moodle_page Page. */
    private $page;

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
                'customint2' => ENROL_SEND_EMAIL_FROM_NOREPLY,
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

        $this->page = new moodle_page();
        $this->page->set_context(context_course::instance($this->course1->id));
        $this->page->set_course($this->course1);
        $this->page->set_pagelayout('admin');
        $url = new moodle_url('/enrol/editinstance.php',
            [
                'courseid' => $this->course1->id,
                'type' => 'coursecompleted',
                'id' => $this->instance->id,
            ]
        );
        $this->page->set_url($url);
    }

    /**
     * Test if user is enrolled after completing a course.
     * @covers \enrol_coursecompleted\observer
     * @covers \enrol_coursecompleted\hook_listener
     */
    public function test_event_enrolled(): void {
        $manager1 = new \course_enrolment_manager($this->page, $this->course1);
        $this->assertCount(1, $manager1->get_user_enrolments($this->student->id));
        $this->assertFalse($this->plugin->has_bulk_operations($manager1));
        $this->assertCount(0, $this->plugin->get_bulk_operations($manager1));
        $manager2 = new \course_enrolment_manager($this->page, $this->course2);
        $this->assertCount(0, $manager2->get_user_enrolments($this->student->id));
        $this->assertTrue($this->plugin->has_bulk_operations($manager2));
        $this->assertCount(2, $this->plugin->get_bulk_operations($manager2));
        $compevent = \core\event\course_completed::create(
            [
                'objectid' => $this->course2->id,
                'relateduserid' => $this->student->id,
                'context' => context_course::instance($this->course1->id),
                'courseid' => $this->course1->id,
                'other' => ['relateduserid' => $this->student->id],
            ]
        );
        $observer = new observer();
        $observer->enroluser($compevent);
        $this->assertTrue(is_enrolled(context_course::instance($this->course1->id), $this->student->id));
        $this->assertTrue(is_enrolled(context_course::instance($this->course2->id), $this->student->id));
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
        $context = context_course::instance($this->course1->id);
        $this->setUser($this->student);
        $this->assertFalse(has_capability('report/completion:view', $context));
        $this->setAdminUser();
        $this->assertTrue(has_capability('report/completion:view', $context));
        $url = new moodle_url('/course/view.php', ['id' => $this->course2->id]);
        $PAGE->set_url($url);
        $manager = new \course_enrolment_manager($PAGE, $this->course2);
        $enrolments = $manager->get_user_enrolments($this->student->id);
        $this->assertCount(1, $enrolments);
        foreach ($enrolments as $enrolment) {
            if ($enrolment->enrolmentinstance->enrol == 'coursecompleted') {
                $arr = ['id' => $this->course2->id, 'ue' => $enrolment->id];
                $actions = $this->plugin->get_user_enrolment_actions($manager, $enrolment);
                $this->assertCount(3, $actions);
                $this->assertEquals('Edit enrolment', $actions[0]->get_title());
                $url = new moodle_url('/enrol/editenrolment.php', $arr);
                $this->assertEquals($url, $actions[0]->get_url());
                $attr = [
                    'class' => 'editenrollink',
                    'rel' => $enrolment->id,
                    'data-action' => 'editenrolment',
                    'title' => 'Edit enrolment',
                ];
                $this->assertEquals($attr, $actions[0]->get_attributes());

                $this->assertEquals('Unenrol', $actions[1]->get_title());
                $url = new moodle_url('/enrol/unenroluser.php', $arr);
                $this->assertEquals($url, $actions[1]->get_url());
                $attr = [
                    'class' => 'unenrollink',
                    'rel' => $enrolment->id,
                    'data-action' => 'unenrol',
                    'title' => 'Unenrol',
                ];
                $this->assertEquals($attr, $actions[1]->get_attributes());

                $this->assertEquals('Course completion', $actions[2]->get_title());
                $url = new moodle_url('/report/completion/index.php', ['course' => $this->course1->id]);
                $this->assertEquals($url, $actions[2]->get_url());
                $attr = ['class' => 'originlink', 'rel' => $enrolment->id, 'title' => 'Course completion'];
                $this->assertEquals($attr, $actions[2]->get_attributes());

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
        $this->assertEquals($this->plugin->get_name(), 'coursecompleted');
        $this->assertEquals($this->plugin->get_config('enabled'), null);
        $this->assertTrue($this->plugin->roles_protected());
        $this->assertTrue($this->plugin->can_add_instance($this->course2->id));
        $this->assertTrue($this->plugin->allow_unenrol($this->instance));
        $this->assertTrue($this->plugin->allow_manage($this->instance));
        $this->assertTrue($this->plugin->can_hide_show_instance($this->instance));
        $this->assertTrue($this->plugin->can_delete_instance($this->instance));
        $this->assertTrue($this->plugin->show_enrolme_link($this->instance));
        $icons = $this->plugin->get_info_icons([$this->instance]);
        $this->assertCount(1, $icons);
        $this->assertEquals($icons[0]->pix, 'icon');
        $this->assertEquals($icons[0]->component, 'enrol_coursecompleted');
        $this->assertEquals($icons[0]->attributes['alt'], 'After completing course: Test course 1');
        $this->assertEquals($icons[0]->attributes['title'], 'After completing course: Test course 1');
        $icons = $this->plugin->get_action_icons($this->instance);
        $this->assertCount(2, $icons);
        $this->assertStringContainsString('icon fa fa-', $icons[0]);
        $this->assertStringContainsString('icon fa fa-', $icons[1]);
        $this->assertStringContainsString(
            '<a href="https://www.example.com/moodle/enrol/editinstance.php?courseid=' . $this->course2->id,
            $icons[0]
        );
        $this->assertStringContainsString(
            '<a href="https://www.example.com/moodle/enrol/coursecompleted/manage.php?enrolid=' . $this->instance->id,
            $icons[1]
        );
        $this->assertStringContainsString('title="Edit"', $icons[0]);
        $this->assertStringContainsString('title="Enrol users"', $icons[1]);
        $this->assertEquals('After completing course: A1', $this->plugin->get_instance_name($this->instance));
        $this->assertEquals('Deleted course unknown', $this->plugin->get_instance_name(null));
        $this->assertEquals(
            'Enrolment by completion of course with id ' . $this->course1->id,
            $this->plugin->get_description_text($this->instance)
        );
        $this->plugin->set_config('svglearnpath', 0);
        $out = $this->plugin->enrol_page_hook($this->instance);
        $cleaned0 = preg_replace('/\s+/', '', $out);
        $this->plugin->set_config('svglearnpath', 1);
        $out = $this->plugin->enrol_page_hook($this->instance);
        $cleaned = preg_replace('/\s+/', '', $out);
        $this->assertStringContainsString('title="Testcourse1"', $cleaned);
        $arr = [
            $this->course1->id,
            $this->course3->id,
            $this->course4->id,
        ];
        foreach ($arr as $value) {
            $this->assertStringContainsString(
                "https://www.example.com/moodle/course/view.php?id=$value",
                $cleaned
            );
        }
        $this->assertStringContainsString('Test course 1</a>', $out);
        $this->assertStringContainsString('<strong class="fa-stack-1x">1</strong>', $out);
        $this->assertStringNotContainsString('class="fa-stack-1x">1</strong>', $cleaned0);
        $this->assertStringContainsString('<strong class="fa-stack-1x text-light">2</strong>', $out);
        $this->assertStringContainsString('<strong class="fa-stack-1x">3</strong>', $out);
        $this->assertStringContainsString('<strong class="fa-stack-1x">4</strong>', $out);
        $this->assertStringContainsString('<strong class="fa-stack-1x">5</strong>', $out);
        $this->assertStringContainsString('<strong class="fa-stack-1x">6</strong>', $out);
    }

    /**
     * Test library 2.
     * @covers \enrol_coursecompleted_plugin
     * @covers \enrol_coursecompleted\observer
     * @covers \enrol_coursecompleted\task\process_future
     */
    public function test_library_other_functionality(): void {
        global $DB;
        $studentrole = $DB->get_field('role', 'id', ['shortname' => 'student']);
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
                'context' => context_course::instance($this->course2->id),
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
        $page = new moodle_page();
        $context = context_course::instance($this->course1->id);
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
        $cleaned = preg_replace('/\s+/', '', $html);
        $this->assertStringContainsString('<optionvalue="1">No</option></select>', $cleaned);
        $this->assertStringContainsString('optionvalue="86400"selected', $cleaned);
        $this->assertStringContainsString('<optionvalue="0">No</option>', $cleaned);
        $this->assertStringContainsString('d="id_enrolstartdate_enabled"value="1">Enable</label>', $cleaned);
        $this->assertStringContainsString('cols="60"rows="8"', $cleaned);
        $this->assertStringContainsString('name="customint3"class="form-check-input"value="1"id="id_customint3"', $cleaned);
        $this->assertStringContainsString(
            '<selectclass="custom-select"name="status"id="id_status"><optionvalue="0">Yes</option>',
            $cleaned
        );
        $this->assertStringContainsString('<selectclass="custom-select"name="customint2"id="id_customint2">', $cleaned);
        $this->assertStringContainsString('<optionvalue="1"selected>Fromthecoursecontact</option>', $cleaned);
        $this->assertStringNotContainsString('<optionvalue="2">Fromthekeyholder</option>', $cleaned);
        $this->assertStringContainsString('<optionvalue="3">Fromtheno-replyaddress</option>', $cleaned);
        $this->assertStringContainsString(
            '<selectclass="custom-select"name="roleid"id="id_roleid"><optionvalue="5"selected>Student</option>',
            $cleaned
        );

        $arr = [
            'Enrolmentdate',
            'Enrolmentduration',
            'Completedcourse',
            'Keepgroup',
            'Sendcoursewelcomemessage',
            'Customwelcomemessage',
            'Startdate',
            'Enddate',
        ];
        foreach ($arr as $value) {
            $this->assertStringContainsString('title="Helpwith' . $value . '"role="img"', $cleaned);
        }
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
     * Test other config.
     * @covers \enrol_coursecompleted_plugin
     */
    public function test_other_config(): void {
        global $DB;
        $this->plugin->set_config('status', 1);
        $this->plugin->set_config('roleid', 3);
        $this->plugin->set_config('expiredaction', ENROL_EXT_REMOVED_UNENROL);
        $this->plugin->set_config('welcome', ENROL_SEND_EMAIL_FROM_NOREPLY);
        $this->plugin->set_config('keepgroup', 0);
        $this->plugin->set_config('enrolperiod', 3000);
        $this->plugin->set_config('svglearnpath', 0);
        $this->assertEquals(get_config('enrol_coursecompleted', 'enrolperiod'), 3000);
        $this->assertEquals(get_config('enrol_coursecompleted', 'roleid'), 3);
        $this->assertEquals(get_config('enrol_coursecompleted', 'keepgroup'), 0);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1]);
        // Add teacher.
        $generator->create_and_enrol($this->course1, 'teacher');
        $generator->create_and_enrol($this->course2, 'teacher');

        $this->setAdminUser();
        $id = $this->plugin->add_instance(
            $course,
            [
                'customint1' => $course->id,
                'customint2' => ENROL_SEND_EMAIL_FROM_COURSE_CONTACT,
                'roleid' => 3,
            ]
        );
        $instance = $DB->get_record('enrol', ['id' => $id]);
        $page = new \moodle_page();
        $context = context_course::instance($course->id);
        $page->set_context($context);
        $page->set_course($course);
        $page->set_pagelayout('standard');
        $page->set_pagetype('course-view');
        $page->set_url('/enrol/coursecompleted/manage.php?enrolid=' . $instance->id);
        $form = $this->tempform();
        $mform = $form->getform();
        $this->plugin->edit_instance_form($instance, $mform, $context);
        $this->assertStringContainsString('Required field', $mform->getReqHTML());
        ob_start();
        $mform->display();
        $html = ob_get_clean();
        $cleaned = preg_replace('/\s+/', '', $html);
        $this->assertStringContainsString(
            '<selectclass="custom-select"name="status"id="id_status"><optionvalue="0">Yes</option>',
            $cleaned
        );
        $this->assertStringContainsString('<optionvalue="1"selected>No</option>', $cleaned);
        $this->assertStringContainsString('cols="60"rows="8"', $cleaned);
        $this->assertStringContainsString('<selectclass="custom-select"name="customint2"id="id_customint2">', $cleaned);
        $this->assertStringContainsString('name="customint3"class="form-check-input"value="1"id="id_customint3"', $cleaned);
        $this->assertStringContainsString(
            '<inputtype="checkbox"name="customint4[enabled]"class="form-check-input"id="id_customint4_enabled"value="1">',
            $cleaned
        );
        $this->assertStringContainsString('<optionvalue="0">No</option>', $cleaned);
        $this->assertStringContainsString('<optionvalue="1">Fromthecoursecontact</option>', $cleaned);
        $this->assertStringContainsString('<optionvalue="3"selected>Fromtheno-replyaddress</option>', $cleaned);
        $this->assertStringContainsString('<optionvalue="3"selected>Teacher</option>', $cleaned);
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
