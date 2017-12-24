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
        $this->resetAfterTest();

        $this->enable_plugin();
        $plugin = enrol_get_plugin('coursecompleted');

        // Just make sure the sync does not throw any errors when nothing to do.
        $plugin->sync(new null_progress_trace());
    }

    /**
     * Test if user is enrolled after completing a course.
     */
    public function test_enrolled() {
        global $CFG, $DB, $PAGE;
        $this->resetAfterTest();
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
        $manager1 = new course_enrolment_manager($PAGE, $course1);
        $userenrolment1 = $manager1->get_user_enrolments($student->id);
        $this->assertCount(1, $userenrolment1);
        $manager2 = new course_enrolment_manager($PAGE, $course2);
        $userenrolment2 = $manager2->get_user_enrolments($student->id);
        $this->assertCount(0, $userenrolment2);
        $plugin = enrol_get_plugin('coursecompleted');
        $this->assertNotEmpty($plugin);
        $plugin->add_instance($course2, ['customint1' => $course1->id]);
        $completion = new completion_completion(['course' => $course1->id, 'userid' => $student->id]);
        $completion->mark_complete();
        $courseevent = \core\event\course_completion_updated::create(['courseid' => $course1->id, 'context' => $context1]);

        // Mark course as complete and get triggered event.
        $sink = $this->redirectEvents();
        $courseevent->trigger();
        $sink->close();
        $userenrolment2 = $manager2->get_user_enrolments($student->id);
        $this->assertCount(0, $userenrolment2);
        $comptask = new \core\task\completion_regular_task();
        $eventstask = new \core\task\events_cron_task();
        ob_start();
        $comptask->execute();
        $eventstask->execute();
        ob_end_clean();
        $generator->enrol_user($student->id, $course2->id, 'student', 'coursecompleted');
        $userenrolment2 = $manager2->get_user_enrolments($student->id);
        // TODO: User is not enrolled.
        $this->assertCount(0, $userenrolment2);
    }
}
