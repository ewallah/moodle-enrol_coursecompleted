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
     *
     */
    protected function setUp() {
        global $CFG;
        $CFG->enablecompletion = true;
        $this->resetAfterTest(true);
        $enabled = enrol_get_plugins(true);
        $enabled['coursecompleted'] = true;
        set_config('enrol_plugins_enabled', implode(',', array_keys($enabled)));
    }

    /**
     * Test other files.
     */
    public function test_files() {
        global $CFG;
        require($CFG->dirroot . '/enrol/coursecompleted/db/tasks.php');
        require($CFG->dirroot . '/enrol/coursecompleted/db/access.php');
        require($CFG->dirroot . '/enrol/coursecompleted/db/events.php');
        $options = include($CFG->dirroot . '/enrol/coursecompleted/tests/coverage.php');
        $this->assertIsObject($options);
    }

    /**
     * Test privacy.
     * @covers \enrol_coursecompleted\privacy\provider
     */
    public function test_privacy() {
        $privacy = new enrol_coursecompleted\privacy\provider();
        $this->assertEquals($privacy->get_reason(), 'privacy:metadata');
    }

    /**
     * Test invalid instance.
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
     * @covers \enrol_coursecompleted_observer
     */
    public function test_invalid_role() {
        global $DB;
        $generator = $this->getDataGenerator();
        $plugin = enrol_get_plugin('coursecompleted');
        $student = $generator->create_user();
        $course1 = $generator->create_course(['shortname' => 'B1', 'enablecompletion' => 1]);
        $course2 = $generator->create_course(['shortname' => 'B2', 'enablecompletion' => 1]);
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->setAdminUser();
        $plugin->add_instance($course1, ['customint1' => $course2->id, 'roleid' => 9999]);
        $manualplugin = enrol_get_plugin('manual');
        $instance = $DB->get_record('enrol', ['courseid' => $course2->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $manualplugin->enrol_user($instance, $student->id, $studentrole->id);
        $compevent = \core\event\course_completed::create([
            'objectid' => $course2->id,
            'relateduserid' => $student->id,
            'context' => context_course::instance($course2->id),
            'courseid' => $course2->id,
            'other' => ['relateduserid' => $student->id]]);
        $observer = new enrol_coursecompleted_observer();
        $observer->enroluser($compevent);
        $this->assertDebuggingCalled("Role does not exist");
    }

    /**
     * Test expiration task.
     * @covers \enrol_coursecompleted\task\process_expirations
     */
    public function test_task() {
        $task = new \enrol_coursecompleted\task\process_expirations;
        $this->assertEquals('Course completed enrolment expiry task', $task->get_name());
        ob_start();
        $task->execute();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertContains('No expired enrol_coursecompleted enrolments detected', $output);
    }

    /**
     * Test manage.
     */
    public function test_manage() {
        global $CFG, $DB, $PAGE;

        $generator = $this->getDataGenerator();
        $plugin = enrol_get_plugin('coursecompleted');
        $student = $generator->create_user();
        $course1 = $generator->create_course(['shortname' => 'B1', 'enablecompletion' => 1]);
        $course2 = $generator->create_course(['shortname' => 'B2', 'enablecompletion' => 1]);
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->setAdminUser();
        $plugin->add_instance($course1, ['customint1' => $course2->id, 'roleid' => $studentrole->id]);
        $instance = $DB->get_record('enrol', ['courseid' => $course1->id, 'enrol' => 'coursecompleted'], '*', MUST_EXIST);
        $url = new moodle_url('/enrol/coursecompleted/manage.php', ['enrolid' => $instance->id]);
        $PAGE->set_url($url->out());
        $page = new moodle_page();
        $page->set_context(context_course::instance($course1->id));
        $page->set_url($PAGE->url);
        $PAGE->initialise_theme_and_output();
        $manager = new course_enrolment_manager($PAGE, $course1);

        $userenrolments = $manager->get_user_enrolments($student->id);
        $this->assertCount(0, $userenrolments);
        $compevent = \core\event\course_completed::create([
            'objectid' => $course2->id,
            'relateduserid' => $student->id,
            'context' => context_course::instance($course2->id),
            'courseid' => $course2->id,
            'other' => ['relateduserid' => $student->id]]);
        $observer = new enrol_coursecompleted_observer();
        $observer->enroluser($compevent);
        $userenrolments = $manager->get_user_enrolments($student->id);
        $this->assertCount(1, $userenrolments);
        $ue = reset($userenrolments);
        $actions = $plugin->get_user_enrolment_actions($manager, $ue);
        $this->assertCount(3, $actions);
        $this->assertEquals('Edit enrolment', $actions[0]->get_title());
        $this->assertEquals('Unenrol', $actions[1]->get_title());
        $this->assertEquals('Course completion', $actions[2]->get_title());
    }
}