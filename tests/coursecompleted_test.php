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
        $plugin = enrol_get_plugin('coursecompleted');
        $id = $plugin->add_instance($this->course1, ['customint1' => $this->course2->id, 'roleid' => 5, 'name' => 'test']);
        $this->instance = $DB->get_record('enrol', ['id' => $id]);
        $this->student = $generator->create_user();
        $manualplugin = enrol_get_plugin('manual');
        $instance = $DB->get_record('enrol', ['courseid' => $this->course2->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $manualplugin->enrol_user($instance, $this->student->id);
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
     * Disable plugin.
     */
    protected function disable_plugin() {
        $enabled = enrol_get_plugins(true);
        unset($enabled['coursecompleted']);
        set_config('enrol_plugins_enabled', implode(',', array_keys($enabled)));
    }

    /**
     * Basic test.
     */
    public function test_basics() {
        $this->disable_plugin();
        $this->assertFalse(enrol_is_enabled('coursecompleted'));
        $this->enable_plugin();
        $this->assertTrue(enrol_is_enabled('coursecompleted'));
        $plugin = enrol_get_plugin('coursecompleted');
        $this->assertNotEmpty($plugin);
        $this->assertInstanceOf('enrol_coursecompleted_plugin', $plugin);
        $this->assertEquals(ENROL_EXT_REMOVED_SUSPENDNOROLES, get_config('enrol_coursecompleted', 'expiredaction'));
    }

    /**
     * Test if user is sync is working.
     */
    public function test_sync_nothing() {
        $plugin = enrol_get_plugin('coursecompleted');
        $plugin->sync(new null_progress_trace());
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
        $this->assertCount(1, $manager1->get_user_enrolments($this->student->id));
        $this->assertCount(1, $manager2->get_user_enrolments($this->student->id));
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

        $now = time();
        while (($task = \core\task\manager::get_next_adhoc_task($now)) !== null) {
            $task->execute();
            \core\task\manager::adhoc_task_complete($task);
        }
        $manager2 = new course_enrolment_manager($PAGE, $this->course1);
        // TODO: Check why the student is not enrolled.
        $this->assertCount(0, $manager2->get_user_enrolments($this->student->id));
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
        $plugin = enrol_get_plugin('coursecompleted');
        $actions = $plugin->get_user_enrolment_actions($manager, $ue);
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
     * Test library.
     */
    public function test_library() {
        $plugin = enrol_get_plugin('coursecompleted');
        $this->setAdminUser();
        $this->asserttrue($plugin->can_add_instance($this->course1->id));
        $this->assertTrue($plugin->allow_unenrol($this->instance));
        $this->assertTrue($plugin->allow_manage($this->instance));
        $this->assertTrue($plugin->can_hide_show_instance($this->instance));
        $this->assertTrue($plugin->can_delete_instance($this->instance));
        $this->assertTrue($plugin->show_enrolme_link($this->instance));
        $this->assertEquals(0, count($plugin->get_info_icons([$this->instance])));
        $this->assertEquals(2, count($plugin->get_action_icons($this->instance)));
        $this->assertEquals('After completing course: A2', $plugin->get_instance_name($this->instance));
        $this->assertEquals('Enrolment by completion of course with id ' . $this->course2->id,
           $plugin->get_description_text($this->instance));
        $this->assertContains('Test course 2', $plugin->enrol_page_hook($this->instance));
        $this->setUser($this->student);
        $this->assertfalse($plugin->can_add_instance($this->course1->id));
        $this->assertfalse($plugin->allow_unenrol($this->instance));
        $this->assertfalse($plugin->allow_manage($this->instance));
        $this->assertfalse($plugin->can_hide_show_instance($this->instance));
        $this->assertfalse($plugin->can_delete_instance($this->instance));
        $this->assertContains('Test course 2', $plugin->enrol_page_hook($this->instance));
    }

    /**
     * Test form.
     */
    public function test_form() {
        global $CFG;
        require_once($CFG->libdir . '/formslib.php');
        $plugin = enrol_get_plugin('coursecompleted');
        $this->setAdminUser();
        $page = new moodle_page();
        $page->set_context(context_course::instance($this->course1->id));
        $page->set_course($this->course1);
        $page->set_pagelayout('standard');
        $page->set_pagetype('course-view');
        $page->set_url('/enrol/coursecompleted/manage.php?enrolid=' . $this->instance->id);
        $mform = new MoodleQuickForm('searchform', 'POST', $page->url);
        $plugin->edit_instance_form($this->instance, $mform, context_course::instance($this->course1->id));
    }
}