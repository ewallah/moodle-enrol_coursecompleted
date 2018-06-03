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

    /**
     * Tests initial setup.
     *
     */
    protected function setUp() {
        $this->resetAfterTest(true);
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
        $this->assertFalse(enrol_is_enabled('coursecompleted'));
        $plugin = enrol_get_plugin('coursecompleted');
        $this->assertInstanceOf('enrol_coursecompleted_plugin', $plugin);
        $this->assertEquals(ENROL_EXT_REMOVED_SUSPENDNOROLES, get_config('enrol_coursecompleted', 'expiredaction'));
    }

    /**
     * Test if user is sync is working.
     */
    public function test_sync_nothing() {
        $this->enable_plugin();
        $plugin = enrol_get_plugin('coursecompleted');
        $plugin->sync(new null_progress_trace());
    }

    /**
     * Test if user is enrolled after completing a course.
     */
    public function test_enrolled() {
        global $CFG, $DB, $PAGE;
        require_once($CFG->dirroot . '/enrol/locallib.php');

        $PAGE->set_url('/enrol/editinstance.php');
        $this->enable_plugin();
        $manualplugin = enrol_get_plugin('manual');
        $this->assertNotEmpty($manualplugin);
        $generator = $this->getDataGenerator();
        $course1 = $generator->create_course(['enablecompletion' => 1]);
        $context1 = context_course::instance($course1->id);
        $course2 = $generator->create_course();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->assertNotEmpty($studentrole);
        $student = $generator->create_user();
        $instance1 = $DB->get_record('enrol', ['courseid' => $course1->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $manualplugin->enrol_user($instance1, $student->id);
        $manager = new course_enrolment_manager($PAGE, $course1);
        $this->assertCount(1, $manager->get_user_enrolments($student->id));
        $manager = new course_enrolment_manager($PAGE, $course2);
        $this->assertCount(0, $manager->get_user_enrolments($student->id));
        $plugin = enrol_get_plugin('coursecompleted');
        $this->assertNotEmpty($plugin);
        $this->setAdminUser();
        $plugin->add_instance($course2, ['customint1' => $course1->id, 'roleid' => 5, 'name' => 'test']);
        $compevent = \core\event\course_completed::create([
            'objectid' => $course1->id,
            'relateduserid' => $student->id,
            'context' => $context1,
            'courseid' => $course1->id,
            'other' => ['relateduserid' => $student->id]]);
        $observer = new enrol_coursecompleted_observer();
        $observer->enroluser($compevent);
        $manager = new course_enrolment_manager($PAGE, $course2);
        $this->assertCount(1, $manager->get_user_enrolments($student->id));
    }

    /**
     * Test observer.
     */
    public function test_observer() {
        global $USER;
        $this->enable_plugin();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1]);
        $context = context_course::instance($course->id);
        $event = \core\event\course_completed::create([
            'objectid' => $course->id,
            'relateduserid' => $USER->id,
            'context' => $context,
            'courseid' => $course->id,
            'other' => ['relateduserid' => $USER->id]]);
        $observer = new enrol_coursecompleted_observer();
        $observer->enroluser($event);
    }

    /**
     * Test privacy.
     */
    public function test_privacy() {
        $this->enable_plugin();
        $privacy = new enrol_coursecompleted\privacy\provider();
        $this->assertEquals($privacy->get_reason(), 'privacy:metadata');
    }

    /**
     * Test library.
     */
    public function test_library() {
        global $DB;
        $this->enable_plugin();
        $plugin = enrol_get_plugin('coursecompleted');
        $generator = $this->getDataGenerator();
        $course1 = $generator->create_course(['shortname' => 'A1', 'enablecompletion' => 1]);
        $course2 = $generator->create_course(['shortname' => 'A2', 'enablecompletion' => 1]);
        $this->assertfalse($plugin->can_add_instance($course1->id));
        $this->setAdminUser();
        $this->asserttrue($plugin->can_add_instance($course1->id));
        $id = $plugin->add_instance($course1, ['customint1' => $course2->id, 'roleid' => 5, 'name' => 'test']);
        $manualplugin = enrol_get_plugin('manual');
        $student = $generator->create_user();
        $instance1 = $DB->get_record('enrol', ['courseid' => $course2->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $manualplugin->enrol_user($instance1, $student->id);
        $instance = $DB->get_record('enrol', ['id' => $id]);
        $this->assertTrue($plugin->allow_unenrol($instance));
        $this->assertTrue($plugin->allow_manage($instance));
        $this->assertTrue($plugin->can_hide_show_instance($instance));
        $this->assertTrue($plugin->can_delete_instance($instance));
        $this->assertEquals(0, count($plugin->get_info_icons([$instance])));
        $this->assertEquals(2, count($plugin->get_action_icons($instance)));
        $this->assertEquals('After completing course: A2', $plugin->get_instance_name($instance));
        $this->assertEquals('Enrolment by completion of course with id ' . $course2->id, $plugin->get_description_text($instance));
        $this->assertEquals('<div class="box generalbox"></div>', $plugin->enrol_page_hook($instance));
        $this->setUser($student);
        $this->assertfalse($plugin->can_add_instance($course1->id));
        $this->assertfalse($plugin->allow_unenrol($instance));
        $this->assertfalse($plugin->allow_manage($instance));
        $this->assertfalse($plugin->can_hide_show_instance($instance));
        $this->assertfalse($plugin->can_delete_instance($instance));
        $this->assertEquals('<div class="box generalbox"></div>', $plugin->enrol_page_hook($instance));
    }

    /**
     * Test manage.
     */
    public function test_manage() {
        global $DB, $PAGE;
        $this->enable_plugin();
        $plugin = enrol_get_plugin('coursecompleted');
        $generator = $this->getDataGenerator();
        $course1 = $generator->create_course(['shortname' => 'A1', 'enablecompletion' => 1]);
        $course2 = $generator->create_course(['shortname' => 'A2', 'enablecompletion' => 1]);
        $this->setAdminUser();
        $id = $plugin->add_instance($course1, ['customint1' => $course2->id, 'roleid' => 5, 'name' => 'test']);
        $manualplugin = enrol_get_plugin('manual');
        $student = $generator->create_user();
        $instance1 = $DB->get_record('enrol', ['courseid' => $course2->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $manualplugin->enrol_user($instance1, $student->id);
        $instance = $DB->get_record('enrol', ['id' => $id]);
        $PAGE->set_url('/enrol/coursecompleted/manage.php?enrolid=');
    }
}