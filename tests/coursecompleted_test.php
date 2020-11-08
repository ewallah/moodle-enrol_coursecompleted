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
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * coursecompleted enrolment plugin tests.
 *
 * @package   enrol_coursecompleted
 * @copyright 2017 eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \enrol_coursecompleted_plugin
 */
class enrol_coursecompleted_testcase extends \advanced_testcase {

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
     * Tests initial setup.
     */
    protected function setUp():void {
        global $CFG, $DB;
        $CFG->enablecompletion = true;
        $this->resetAfterTest(true);
        $enabled = enrol_get_plugins(true);
        unset($enabled['guest']);
        unset($enabled['self']);
        $enabled['coursecompleted'] = true;
        set_config('enrol_plugins_enabled', implode(',', array_keys($enabled)));
        $generator = $this->getDataGenerator();
        $this->course1 = $generator->create_course(['shortname' => 'A1', 'enablecompletion' => 1]);
        $this->course2 = $generator->create_course(['shortname' => 'A2', 'enablecompletion' => 1]);
        $this->course3 = $generator->create_course(['shortname' => 'A3', 'enablecompletion' => 1]);
        $this->course4 = $generator->create_course(['shortname' => 'A4', 'enablecompletion' => 1]);
        $studentrole = $DB->get_field('role', 'id', ['shortname' => 'student']);
        $this->setAdminUser();
        $this->plugin = enrol_get_plugin('coursecompleted');
        $id = $this->plugin->add_instance($this->course2,
            ['customint1' => $this->course1->id, 'customint2' => 1, 'roleid' => $studentrole]);
        $this->instance = $DB->get_record('enrol', ['id' => $id]);
        $this->plugin->add_instance($this->course4, ['customint1' => $this->course3->id, 'roleid' => $studentrole]);
        $this->plugin->add_instance($this->course3, ['customint1' => $this->course2->id, 'roleid' => $studentrole]);
        $this->instance = $DB->get_record('enrol', ['id' => $id]);
        $this->student = $generator->create_user();
        $manualplugin = enrol_get_plugin('manual');
        $instance = $DB->get_record('enrol', ['courseid' => $this->course1->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $manualplugin->enrol_user($instance, $this->student->id, $studentrole);
        mark_user_dirty($this->student->id);
    }

    /**
     * Test if user is enrolled after completing a course.
     * @coversDefaultClass \enrol_coursecompleted_observer
     */
    public function test_enrolled() {
        global $CFG, $PAGE;
        require_once($CFG->dirroot . '/enrol/locallib.php');
        require_once($CFG->dirroot . '/enrol/coursecompleted/classes/enrol_coursecompleted_bulkdelete.php');

        $PAGE->set_url('/enrol/editinstance.php');
        $manager1 = new \course_enrolment_manager($PAGE, $this->course1);
        $this->assertCount(1, $manager1->get_user_enrolments($this->student->id));
        $this->assertfalse($this->plugin->has_bulk_operations($manager1));
        $this->assertCount(0, $this->plugin->get_bulk_operations($manager1));
        $manager2 = new \course_enrolment_manager($PAGE, $this->course2);
        $this->assertCount(0, $manager2->get_user_enrolments($this->student->id));
        $this->assertTrue($this->plugin->has_bulk_operations($manager2));
        $this->assertCount(2, $this->plugin->get_bulk_operations($manager2));
        $compevent = \core\event\course_completed::create([
            'objectid' => $this->course2->id,
            'relateduserid' => $this->student->id,
            'context' => \context_course::instance($this->course1->id),
            'courseid' => $this->course1->id,
            'other' => ['relateduserid' => $this->student->id]]);
        $observer = new \enrol_coursecompleted_observer();
        $observer->enroluser($compevent);
        $this->assertTrue(is_enrolled(\context_course::instance($this->course1->id), $this->student->id));
        $this->assertTrue(is_enrolled(\context_course::instance($this->course2->id), $this->student->id));
        $this->assertCount(1, $manager1->get_user_enrolments($this->student->id));
    }

    /**
     * Test if user is enrolled for a specific time after completing a course.
     * @covers \enrol_coursecompleted_plugin
     * @covers \enrol_coursecompleted_observer
     */
    public function test_time_enrolled() {
        global $CFG, $DB, $PAGE;
        require_once($CFG->dirroot . '/enrol/locallib.php');

        $generator = $this->getDataGenerator();
        $course1 = $generator->create_course(['shortname' => 'B1']);
        $course2 = $generator->create_course(['shortname' => 'B2']);
        $course3 = $generator->create_course(['shortname' => 'B3', 'enablecompletion' => 1]);
        $studentrole = $DB->get_field('role', 'id', ['shortname' => 'student']);
        $params = ['customint1' => $course3->id, 'roleid' => $studentrole, 'enrolperiod' => 1];
        $id1 = $this->plugin->add_instance($course1, $params);
        $params = ['customint1' => $course3->id, 'roleid' => $studentrole, 'enrolperiod' => 2];
        $id2 = $this->plugin->add_instance($course2, $params);
        $manualplugin = enrol_get_plugin('manual');
        $instance = $DB->get_record('enrol', ['courseid' => $course3->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $manualplugin->enrol_user($instance, $this->student->id, $studentrole);
        $this->assertFalse(is_enrolled(\context_course::instance($course1->id), $this->student->id));
        $this->assertFalse(is_enrolled(\context_course::instance($course2->id), $this->student->id));
        $this->assertTrue(is_enrolled(\context_course::instance($course3->id), $this->student->id));
        mark_user_dirty($this->student->id);
        rebuild_course_cache($course1->id);
        rebuild_course_cache($course2->id);
        rebuild_course_cache($course3->id);
        $PAGE->set_url('/enrol/editinstance.php');
        $manager1 = new \course_enrolment_manager($PAGE, $course1);
        $this->assertCount(0, $manager1->get_user_enrolments($this->student->id));
        $manager2 = new \course_enrolment_manager($PAGE, $course2);
        $this->assertCount(0, $manager2->get_user_enrolments($this->student->id));
        $manager3 = new \course_enrolment_manager($PAGE, $course3);
        $this->assertCount(1, $manager3->get_user_enrolments($this->student->id));
        $compevent = \core\event\course_completed::create([
            'objectid' => $course1->id,
            'relateduserid' => $this->student->id,
            'context' => \context_course::instance($course3->id),
            'courseid' => $course3->id,
            'other' => ['relateduserid' => $this->student->id]]);
        mark_user_dirty($this->student->id);
        $observer = new \enrol_coursecompleted_observer();
        $observer->enroluser($compevent);
        mark_user_dirty($this->student->id);
        rebuild_course_cache($course1->id);
        rebuild_course_cache($course2->id);
        mark_user_dirty($this->student->id);
        $this->assertTrue(is_enrolled(\context_course::instance($course1->id), $this->student->id));
        $this->assertTrue(is_enrolled(\context_course::instance($course2->id), $this->student->id));
        $this->assertCount(1, $manager1->get_user_enrolments($this->student->id));
        $ueinstance = $DB->get_record('user_enrolments', ['enrolid' => $id1, 'userid' => $this->student->id]);
        $this->assertEquals(0, $ueinstance->timestart);
        $this->assertNotEquals(0, $ueinstance->timeend);
        $ueinstance = $DB->get_record('user_enrolments', ['enrolid' => $id2, 'userid' => $this->student->id]);
        $this->assertEquals(0, $ueinstance->timestart);
        $this->assertGreaterThan(time(), $ueinstance->timeend);
        sleep(1);
        $trace = new \null_progress_trace();
        $this->plugin->sync($trace);
        mark_user_dirty($this->student->id);
        $this->assertFalse(is_enrolled(\context_course::instance($course1->id), $this->student->id, '', true));
        $manager1 = new \course_enrolment_manager($PAGE, $course1);
        $this->assertCount(1, $manager1->get_user_enrolments($this->student->id));
        $this->assertTrue(is_enrolled(\context_course::instance($course2->id), $this->student->id));
        $manager2 = new \course_enrolment_manager($PAGE, $course2);
        $this->assertCount(1, $manager2->get_user_enrolments($this->student->id));
        $this->plugin->set_config('expiredaction', ENROL_EXT_REMOVED_UNENROL);
        sleep(2);
        $this->plugin->sync($trace);
        mark_user_dirty($this->student->id);
        $this->assertFalse(is_enrolled(\context_course::instance($course2->id), $this->student->id));
        $manager2 = new \course_enrolment_manager($PAGE, $course2);
        $this->assertCount(0, $manager2->get_user_enrolments($this->student->id));
    }

    /**
     * Test if user is enrolled after completing a course.
     * @coversDefaultClass \enrol_coursecompleted_plugin
     */
    public function test_completion() {
        global $PAGE;
        $manager = new \course_enrolment_manager($PAGE, $this->course2);
        $this->assertCount(0, $manager->get_user_enrolments($this->student->id));
        $ccompletion = new \completion_completion(['course' => $this->course1->id, 'userid' => $this->student->id]);
        $ccompletion->mark_complete(time());
        $this->assertEquals('100',
           \core_completion\progress::get_course_progress_percentage($this->course1, $this->student->id));
        $this->runAdhocTasks();
        $manager = new \course_enrolment_manager($PAGE, $this->course2);
        $this->assertCount(1, $manager->get_user_enrolments($this->student->id));
    }

    /**
     * Test ue.
     * @coversDefaultClass \enrol_coursecompleted_plugin
     */
    public function test_ue() {
        global $PAGE;
        $ccompletion = new \completion_completion(['course' => $this->course1->id, 'userid' => $this->student->id]);
        $ccompletion->mark_complete(time());
        $this->assertEquals('100',
           \core_completion\progress::get_course_progress_percentage($this->course1, $this->student->id));
        $this->runAdhocTasks();
        $this->setAdminUser();
        $context = context_course::instance($this->course1->id);
        $this->assertTrue(has_capability('report/completion:view', $context));
        $url = new moodle_url('/user/index.php', ['id' => $this->course2->id]);
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
            }
        }
    }

    /**
     * Test instances.
     * @covers \enrol_coursecompleted_plugin
     */
    public function test_instances() {
        global $DB;
        $records = $DB->get_records('enrol', ['enrol' => 'coursecompleted']);
        foreach ($records as $record) {
            $this->assertCount(4, $this->plugin->build_course_path($record));
        }
    }

    /**
     * Test library.
     * @coversDefaultClass \enrol_coursecompleted_plugin
     * @coversDefaultClass \enrol_coursecompleted_observer
     */
    public function test_library() {
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
        $this->assertCount(4, $this->plugin->build_course_path($this->instance));
        $this->assertCount(1, $this->plugin->get_info_icons([$this->instance]));
        $this->assertCount(2, $this->plugin->get_action_icons($this->instance));
        $this->assertEquals('After completing course: A1', $this->plugin->get_instance_name($this->instance));
        $this->assertEquals('Enrolment by completion of course with id ' . $this->course1->id,
           $this->plugin->get_description_text($this->instance));
        $this->assertStringContainsString('Test course 1', $this->plugin->enrol_page_hook($this->instance));
        $arr = ['status' => 0, 'enrolenddate' => time(), 'enrolstartdate' => time() + 10000];
        $tmp = $this->plugin->edit_instance_validation($arr, null, $this->instance, null);
        $this->assertEquals('The specified course does not exist', $tmp['customint']);
        $this->assertEquals('Enrolment end date cannot be earlier than start date', $tmp['enrolenddate']);
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
        $compevent = \core\event\course_completed::create([
            'objectid' => $this->course2->id,
            'relateduserid' => $this->student->id,
            'context' => \context_course::instance($this->course2->id),
            'courseid' => $this->course2->id,
            'other' => ['relateduserid' => $this->student->id]]);
        $observer = new \enrol_coursecompleted_observer();
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
        $tmp = $this->plugin->enrol_page_hook($this->instance);
        $this->assertStringContainsString('Test course 1', $tmp);
        $this->assertStringContainsString('You will be enrolled in this course when you complete course', $tmp);
    }

    /**
     * Test form.
     * @covers \enrol_coursecompleted_plugin
     */
    public function test_form() {
        $page = new \moodle_page();
        $context = \context_course::instance($this->course1->id);
        $page->set_context($context);
        $page->set_course($this->course1);
        $page->set_pagelayout('standard');
        $page->set_pagetype('course-view');
        $page->set_url('/enrol/coursecompleted/manage.php?enrolid=' . $this->instance->id);
        $form = new temp_coursecompleted_form();
        $mform = $form->getform();
        $this->plugin->edit_instance_form($this->instance, $mform, $context);
        $this->assertStringContainsString('Required field', $mform->getReqHTML());
        ob_start();
        $mform->display();
        $html = ob_get_clean();
        $this->assertStringContainsString('Required field', $html);
        $this->assertStringContainsString('Help with Completed course', $html);
    }

    /**
     * Test access.
     * @coversDefaultClass \enrol_coursecompleted_plugin
     */
    public function test_access() {
        global $DB;
        $context = \context_course::instance($this->course2->id);
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
     * @coversDefaultClass \enrol_coursecompleted_plugin
     */
    public function test_backup() {
        global $CFG, $DB, $PAGE;
        $this->assertEquals(3, $DB->count_records('enrol', ['enrol' => 'coursecompleted']));
        $ccompletion = new \completion_completion(['course' => $this->course1->id, 'userid' => $this->student->id]);
        $ccompletion->mark_complete(time());
        $this->runAdhocTasks();
        $bc = new backup_controller(backup::TYPE_1COURSE, $this->course2->id, backup::FORMAT_MOODLE, backup::INTERACTIVE_NO,
            backup::MODE_GENERAL, 2);
        $bc->execute_plan();
        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $fp = get_file_packer('application/vnd.moodle.backup');
        $filepath = $CFG->dataroot . '/temp/backup/test-restore-course-event';
        $file->extract_to_pathname($fp, $filepath);
        $bc->destroy();
        $rc = new restore_controller('test-restore-course-event', $this->course2->id, backup::INTERACTIVE_NO,
            backup::MODE_GENERAL, 2, backup::TARGET_NEW_COURSE);
        $rc->execute_precheck();
        $rc->execute_plan();
        $newid = $rc->get_courseid();
        $rc->destroy();
        $this->assertEquals(4, $DB->count_records('enrol', ['enrol' => 'coursecompleted']));
        $this->assertTrue(is_enrolled(\context_course::instance($newid), $this->student->id));
        $url = new moodle_url('/user/index.php', ['id' => $newid]);
        $PAGE->set_url($url);
        $course = get_course($newid);
        $manager = new \course_enrolment_manager($PAGE, $course);
        $enrolments = $manager->get_user_enrolments($this->student->id);
        $this->assertCount(2, $enrolments);
        $this->assertCount(3, $manager->get_enrolment_instance_names());
        $bc = new backup_controller(backup::TYPE_1COURSE, $this->course2->id, backup::FORMAT_MOODLE, backup::INTERACTIVE_NO,
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
        $this->assertEquals(4, $DB->count_records('enrol', ['enrol' => 'coursecompleted']));
        $this->assertTrue(is_enrolled(\context_course::instance($newid), $this->student->id));
    }

    /**
     * Test deleted course.
     * @covers \enrol_coursecompleted_observer
     * @covers \enrol_coursecompleted_plugin
     */
    public function test_deletedcourse() {
        $sink = $this->redirectEvents();
        ob_start();
        delete_course($this->course1->id, false);
        ob_end_clean();
        $events = $sink->get_events();
        $sink->close();
        $this->assertEquals('Deleted course ' . $this->course1->id, $this->plugin->get_instance_name($this->instance));
        $this->assertEquals('Enrolment by completion of course with id ' . $this->course1->id,
            $this->plugin->get_description_text($this->instance));
        $event = array_pop($events);
        $this->assertInstanceOf('\core\event\course_deleted', $event);
        $observer = new \enrol_coursecompleted_observer();
        $observer->coursedeleted($event);
    }
}

/**
 * Form object to be used in test case.
 *
 * @package   enrol_coursecompleted
 * @copyright 2017 eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class temp_coursecompleted_form extends \moodleform {
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
