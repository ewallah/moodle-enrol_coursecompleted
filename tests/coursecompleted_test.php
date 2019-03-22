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
 * @copyright 2017 eWallah (www.eWallah.net)
 * @author    Renaat Debleu (info@eWallah.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * coursecompleted enrolment plugin tests.
 *
 * @package   enrol_coursecompleted
 * @copyright 2017 eWallah (www.eWallah.net)
 * @author    Renaat Debleu (info@eWallah.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_coursecompleted_testcase extends advanced_testcase {

    /** @var stdClass Instance. */
    private $instance;

    /** @var stdClass Student. */
    private $student;

    /** @var stdClass First course. */
    private $course1;

    /** @var stdClass Second course. */
    private $course2;

    /** @var stdClass Plugin. */
    private $plugin;

    /**
     * Tests initial setup.
     *
     */
    protected function setUp() {
        global $CFG, $DB;
        $CFG->enablecompletion = true;
        $this->resetAfterTest(true);
        $this->enable_plugin();
        $generator = $this->getDataGenerator();
        $this->course1 = $generator->create_course(['shortname' => 'A1', 'enablecompletion' => 1]);
        $this->course2 = $generator->create_course(['shortname' => 'A2', 'enablecompletion' => 1]);
        $this->setAdminUser();
        $this->plugin = enrol_get_plugin('coursecompleted');
        $id = $this->plugin->add_instance($this->course1, ['customint1' => $this->course2->id, 'roleid' => 5, 'name' => 'test']);
        $this->instance = $DB->get_record('enrol', ['id' => $id]);
        $this->student = $generator->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $manualplugin = enrol_get_plugin('manual');
        $instance = $DB->get_record('enrol', ['courseid' => $this->course2->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $manualplugin->enrol_user($instance, $this->student->id, $studentrole->id);
        mark_user_dirty($this->student->id);
    }

    /**
     * Enable plugin.
     */
    protected function enable_plugin() {
        $enabled = enrol_get_plugins(true);
        $enabled['coursecompleted'] = true;
        set_config('enrol_plugins_enabled', implode(',', array_keys($enabled)));
    }

    /**
     * Basic test.
     */
    public function test_basics() {
        $enabled = enrol_get_plugins(true);
        unset($enabled['coursecompleted']);
        set_config('enrol_plugins_enabled', implode(',', array_keys($enabled)));
        $this->assertFalse(enrol_is_enabled('coursecompleted'));
        $this->enable_plugin();
        $this->assertTrue(enrol_is_enabled('coursecompleted'));
        $this->assertNotEmpty($this->plugin);
        $this->assertInstanceOf('enrol_coursecompleted_plugin', $this->plugin);
        $this->assertEquals(ENROL_EXT_REMOVED_SUSPENDNOROLES, get_config('enrol_coursecompleted', 'expiredaction'));
    }

    /**
     * Test if user is enrolled after completing a course.
     */
    public function test_enrolled() {
        global $CFG, $PAGE;
        require_once($CFG->dirroot . '/enrol/locallib.php');

        $this->setAdminUser();
        $PAGE->set_url('/enrol/editinstance.php');
        $manager1 = new course_enrolment_manager($PAGE, $this->course1);
        $this->assertCount(0, $manager1->get_user_enrolments($this->student->id));
        $manager2 = new course_enrolment_manager($PAGE, $this->course2);
        $this->assertCount(1, $manager2->get_user_enrolments($this->student->id));
        $compevent = \core\event\course_completed::create([
            'objectid' => $this->course2->id,
            'relateduserid' => $this->student->id,
            'context' => context_course::instance($this->course2->id),
            'courseid' => $this->course2->id,
            'other' => ['relateduserid' => $this->student->id]]);
        $observer = new enrol_coursecompleted_observer();
        $observer->enroluser($compevent);
        $this->assertTrue(is_enrolled(context_course::instance($this->course1->id), $this->student->id));
        $this->assertTrue(is_enrolled(context_course::instance($this->course2->id), $this->student->id));
        $this->assertCount(1, $manager1->get_user_enrolments($this->student->id));
    }

    /**
     * Test if user is enrolled for a specific time after completing a course.
     */
    public function test_time_enrolled() {
        global $CFG, $DB, $PAGE;
        require_once($CFG->dirroot . '/enrol/locallib.php');

        $generator = $this->getDataGenerator();
        $course1 = $generator->create_course(['shortname' => 'B1', 'enablecompletion' => 1]);
        $course2 = $generator->create_course(['shortname' => 'B2', 'enablecompletion' => 1]);
        $this->setAdminUser();
        $params = ['customint1' => $course2->id, 'roleid' => 5, 'name' => 'test', 'enrolperiod' => 10];
        $id = $this->plugin->add_instance($course1, $params);
        $instance = $DB->get_record('enrol', ['id' => $id]);
        $manualplugin = enrol_get_plugin('manual');
        $instance = $DB->get_record('enrol', ['courseid' => $course2->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $manualplugin->enrol_user($instance, $this->student->id);
        mark_user_dirty($this->student->id);
        $PAGE->set_url('/enrol/editinstance.php');
        $manager1 = new course_enrolment_manager($PAGE, $course1);
        $this->assertCount(0, $manager1->get_user_enrolments($this->student->id));
        $manager2 = new course_enrolment_manager($PAGE, $course2);
        $this->assertCount(1, $manager2->get_user_enrolments($this->student->id));
        $compevent = \core\event\course_completed::create([
            'objectid' => $course2->id,
            'relateduserid' => $this->student->id,
            'context' => context_course::instance($course2->id),
            'courseid' => $course2->id,
            'other' => ['relateduserid' => $this->student->id]]);
        $observer = new enrol_coursecompleted_observer();
        $observer->enroluser($compevent);
        $this->assertTrue(is_enrolled(context_course::instance($course1->id), $this->student->id));
        $this->assertTrue(is_enrolled(context_course::instance($course2->id), $this->student->id));
        $this->assertCount(1, $manager1->get_user_enrolments($this->student->id));
        $ueinstance = $DB->get_record('user_enrolments', ['enrolid' => $id, 'userid' => $this->student->id]);
        $this->assertEquals(0, $ueinstance->timestart);
        $this->assertNotEquals(0, $ueinstance->timeend);
    }

    /**
     * Test if user is enrolled after completing a course.
     */
    public function test_completion() {
        global $PAGE;
        $this->setAdminUser();
        $manager1 = new course_enrolment_manager($PAGE, $this->course1);
        $this->assertCount(0, $manager1->get_user_enrolments($this->student->id));
        $ccompletion = new completion_completion(['course' => $this->course2->id, 'userid' => $this->student->id]);
        $ccompletion->mark_complete(time());
        $this->assertEquals('100',
           \core_completion\progress::get_course_progress_percentage($this->course2, $this->student->id));
        $this->runAdhocTasks();
        $manager2 = new course_enrolment_manager($PAGE, $this->course1);
        $this->assertCount(1, $manager2->get_user_enrolments($this->student->id));
        $this->plugin->sync(new null_progress_trace());
    }

    /**
     * Test ue.
     */
    public function test_ue() {
        global $PAGE;
        $this->setAdminUser();
        $PAGE->set_url('/enrol/editinstance.php');
        $manager = new course_enrolment_manager($PAGE, $this->course2);
        $enrolments = $manager->get_user_enrolments($this->student->id);
        $ue = reset($enrolments);
        $actions = $this->plugin->get_user_enrolment_actions($manager, $ue);
        $this->assertCount(2, $actions);
    }

    /**
     * Test privacy.
     */
    public function test_privacy() {
        $privacy = new enrol_coursecompleted\privacy\provider();
        $this->assertEquals($privacy->get_reason(), 'privacy:metadata');
    }

    /**
     * Test invalid instance.
     */
    public function test_invalid_instance() {
        $tst = new stdClass();
        $tst->enrol = 'wrong';
        $this->expectException('moodle_exception');
        $this->expectExceptionMessage('invalid enrol instance!');
        $this->assertEquals(0, count($this->plugin->get_action_icons($tst)));
    }

    /**
     * Test invalid role.
     */
    public function test_invalid_role() {
        global $DB;
        $generator = $this->getDataGenerator();
        $course1 = $generator->create_course(['shortname' => 'B1', 'enablecompletion' => 1]);
        $course2 = $generator->create_course(['shortname' => 'B2', 'enablecompletion' => 1]);
        $this->setAdminUser();
        $id = $this->plugin->add_instance($course1, ['customint1' => $course2->id, 'roleid' => 9999, 'name' => 'invalidrole']);
        $instance = $DB->get_record('enrol', ['id' => $id]);
        $manualplugin = enrol_get_plugin('manual');
        $instance = $DB->get_record('enrol', ['courseid' => $course2->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $manualplugin->enrol_user($instance, $this->student->id);
        $compevent = \core\event\course_completed::create([
            'objectid' => $course2->id,
            'relateduserid' => $this->student->id,
            'context' => context_course::instance($course2->id),
            'courseid' => $course2->id,
            'other' => ['relateduserid' => $this->student->id]]);
        $observer = new enrol_coursecompleted_observer();
        $observer->enroluser($compevent);
        $this->assertDebuggingCalled("Role does not exist");
    }

    /**
     * Test library.
     */
    public function test_library() {
        $this->setAdminUser();
        $this->assertEquals($this->plugin->get_name(), 'coursecompleted');
        $this->assertEquals($this->plugin->get_config('enabled'), null);
        $this->assertTrue($this->plugin->roles_protected());
        $this->assertTrue($this->plugin->can_add_instance($this->course1->id));
        $this->assertTrue($this->plugin->allow_unenrol($this->instance));
        $this->assertTrue($this->plugin->allow_manage($this->instance));
        $this->assertTrue($this->plugin->can_hide_show_instance($this->instance));
        $this->assertTrue($this->plugin->can_delete_instance($this->instance));
        $this->assertTrue($this->plugin->show_enrolme_link($this->instance));
        $this->assertEquals(0, count($this->plugin->get_info_icons([$this->instance])));
        $this->assertEquals(2, count($this->plugin->get_action_icons($this->instance)));
        $this->assertEquals('After completing course: A2', $this->plugin->get_instance_name($this->instance));
        $this->assertEquals('Enrolment by completion of course with id ' . $this->course2->id,
           $this->plugin->get_description_text($this->instance));
        $this->assertContains('Test course 2', $this->plugin->enrol_page_hook($this->instance));
        $arr = ['status' => 0, 'enrolenddate' => time(), 'enrolstartdate' => time() + 10000];
        $tmp = $this->plugin->edit_instance_validation($arr, null, $this->instance, null);
        $this->assertEquals('The specified course does not exist', $tmp['customint']);
        $this->assertEquals('Enrolment end date cannot be earlier than start date', $tmp['enrolenddate']);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['shortname' => 'c1', 'enablecompletion' => 1]);
        $tmp = $this->plugin->edit_instance_validation(['status' => 0, 'customint1' => $course->id], null, $this->instance, null);
        $this->assertEquals([], $tmp);
        $this->setUser(1);
        $this->assertEquals('', $this->plugin->enrol_page_hook($this->instance));
        $this->assertEquals(0, count($this->plugin->get_info_icons([$this->instance])));
        $this->setUser($this->student);
        $this->assertEquals(1, count($this->plugin->get_info_icons([$this->instance])));
        $page = new moodle_page();
        $page->set_context(context_course::instance($this->course1->id));
        $page->set_course($this->course1);
        $page->set_pagelayout('standard');
        $page->set_pagetype('course-view');
        $page->set_url('/enrol/index.php?id=' . $this->course1->id);
        $this->assertfalse($this->plugin->can_add_instance($this->course1->id));
        $this->assertfalse($this->plugin->allow_unenrol($this->instance));
        $this->assertfalse($this->plugin->allow_manage($this->instance));
        $this->assertfalse($this->plugin->can_hide_show_instance($this->instance));
        $this->assertfalse($this->plugin->can_delete_instance($this->instance));
        $this->assertContains('Test course 2', $this->plugin->enrol_page_hook($this->instance));
        $compevent = \core\event\course_completed::create([
            'objectid' => $this->course2->id,
            'relateduserid' => $this->student->id,
            'context' => context_course::instance($this->course2->id),
            'courseid' => $this->course2->id,
            'other' => ['relateduserid' => $this->student->id]]);
        $observer = new enrol_coursecompleted_observer();
        $observer->enroluser($compevent);
        $tmp = $this->plugin->enrol_page_hook($this->instance);
        $this->assertContains('Test course 2', $tmp);
        $this->assertContains('You will be enrolled in this course when you complete course', $tmp);
        $this->assertEquals(1, count($this->plugin->get_info_icons([$this->instance])));
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $this->course2->id, 5);
        mark_user_dirty($student->id);
        $this->setUser($student);
        $this->assertEquals(1, count($this->plugin->get_info_icons([$this->instance])));
        $tmp = $this->plugin->enrol_page_hook($this->instance);
        $this->assertContains('Test course 2', $tmp);
        $this->assertContains('You will be enrolled in this course when you complete course', $tmp);
    }

    /**
     * Test form.
     */
    public function test_form() {
        $this->setAdminUser();
        $page = new moodle_page();
        $page->set_context(context_course::instance($this->course1->id));
        $page->set_course($this->course1);
        $page->set_pagelayout('standard');
        $page->set_pagetype('course-view');
        $page->set_url('/enrol/coursecompleted/manage.php?enrolid=' . $this->instance->id);
        $form = new temp_coursecompleted_form();
        $mform = $form->getform();
        $this->plugin->edit_instance_form($this->instance, $mform, context_course::instance($this->course1->id));
    }

    /**
     * Test access.
     */
    public function test_access() {
        global $DB;
        $this->setAdminUser();
        $context = context_course::instance($this->course2->id);
        $this->assertTrue(has_capability('enrol/coursecompleted:config', $context));
        $this->assertTrue(has_capability('enrol/coursecompleted:enrolpast', $context));
        $this->assertTrue(has_capability('enrol/coursecompleted:manage', $context));
        $this->assertTrue(has_capability('enrol/coursecompleted:unenrol', $context));
        $this->assertTrue(has_capability('enrol/coursecompleted:unenrolself', $context));
        $this->setUser($this->student);
        $this->assertFalse(has_capability('enrol/coursecompleted:config', $context));
        $this->assertFalse(has_capability('enrol/coursecompleted:enrolpast', $context));
        $this->assertFalse(has_capability('enrol/coursecompleted:manage', $context));
        $this->assertFalse(has_capability('enrol/coursecompleted:unenrol', $context));
        $this->assertFalse(has_capability('enrol/coursecompleted:unenrolself', $context));
        $editor = $this->getDataGenerator()->create_user();
        $editorroleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
        $this->getDataGenerator()->enrol_user($editor->id, $this->course2->id, $editorroleid);
        mark_user_dirty($editor->id);
        $this->setUser($editor);
        $this->assertTrue(has_capability('enrol/coursecompleted:config', $context));
        $this->assertFalse(has_capability('enrol/coursecompleted:enrolpast', $context));
        $this->assertTrue(has_capability('enrol/coursecompleted:manage', $context));
        $this->assertTrue(has_capability('enrol/coursecompleted:unenrol', $context));
        $this->assertFalse(has_capability('enrol/coursecompleted:unenrolself', $context));
    }

    /**
     * Test backup.
     */
    public function test_backup() {
        global $CFG;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        $this->setAdminUser();
        $ccompletion = new completion_completion(['course' => $this->course2->id, 'userid' => $this->student->id]);
        $ccompletion->mark_complete(time());
        $this->runAdhocTasks();
        $bc = new backup_controller(backup::TYPE_1COURSE, $this->course1->id, backup::FORMAT_MOODLE, backup::INTERACTIVE_NO,
            backup::MODE_GENERAL, 2);
        $bc->execute_plan();
        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $fp = get_file_packer('application/vnd.moodle.backup');
        $filepath = $CFG->dataroot . '/temp/backup/test-restore-course-event';
        $file->extract_to_pathname($fp, $filepath);
        $bc->destroy();
        $rc = new restore_controller('test-restore-course-event', $this->course1->id, backup::INTERACTIVE_NO,
            backup::MODE_GENERAL, 2, backup::TARGET_NEW_COURSE);
        $rc->execute_precheck();
        $rc->execute_plan();
        $newid = $rc->get_courseid();
        $rc->destroy();
        $this->assertTrue(is_enrolled(context_course::instance($newid), $this->student->id));
        $bc = new backup_controller(backup::TYPE_1COURSE, $this->course1->id, backup::FORMAT_MOODLE, backup::INTERACTIVE_NO,
            backup::MODE_GENERAL, 2);
        $bc->execute_plan();
        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $fp = get_file_packer('application/vnd.moodle.backup');
        $filepath = $CFG->dataroot . '/temp/backup/test-restore-course-event';
        $file->extract_to_pathname($fp, $filepath);
        $bc->destroy();
        $rc = new restore_controller('test-restore-course-event', $newid, backup::INTERACTIVE_NO,
            backup::MODE_GENERAL, 2, backup::TARGET_EXISTING_ADDING);
        $rc->execute_precheck();
        $rc->execute_plan();
        $rc->destroy();
        $this->assertTrue(is_enrolled(context_course::instance($newid), $this->student->id));
    }

    /**
     * Test deleted course.
     */
    public function test_deletedcourse() {
        delete_course($this->course2->id, false);
        $this->assertEquals('Deleted course ' . $this->course2->id, $this->plugin->get_instance_name($this->instance));
        $this->assertEquals('Enrolment by completion of course with id ' . $this->course2->id,
            $this->plugin->get_description_text($this->instance));
    }
}

/**
 * Form object to be used in test case.
 *
 * @package   enrol_coursecompleted
 * @copyright 2017 eWallah (www.eWallah.net)
 * @author    Renaat Debleu (info@eWallah.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class temp_coursecompleted_form extends moodleform {
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
        // Set submitted flag, to simulate submission.
        $mform->_flagSubmitted = true;
        return $mform;
    }
}
