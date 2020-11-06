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
 * coursecompleted enrolment manager tests.
 *
 * @package   enrol_coursecompleted
 * @copyright 2017 eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * coursecompleted enrolment manager tests.
 *
 * @package   enrol_coursecompleted
 * @copyright 2017 eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \enrol_coursecompleted_plugin
 */
class enrol_coursecompleted_manager_testcase extends \advanced_testcase {

    /** @var stdClass Instance. */
    private $instance;

    /** @var stdClass Student. */
    private $student;

    /** @var stdClass course. */
    private $course;

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
        $course = $generator->create_course(['shortname' => 'A1', 'enablecompletion' => 1]);
        $this->course = $generator->create_course(['shortname' => 'A2', 'enablecompletion' => 1]);
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->setAdminUser();
        $plugin = enrol_get_plugin('coursecompleted');
        $id = $plugin->add_instance($course, ['customint1' => $this->course->id, 'roleid' => $studentrole->id]);
        $this->instance = $DB->get_record('enrol', ['id' => $id]);
        $this->student = $generator->create_user();
        $manualplugin = enrol_get_plugin('manual');
        $instance = $DB->get_record('enrol', ['courseid' => $this->course->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $manualplugin->enrol_user($instance, $this->student->id, $studentrole->id);
        mark_user_dirty($this->student->id);
    }

    /**
     * Test missing enrolid param.
     */
    public function test_manager_empty_param() {
        global $CFG;
        chdir($CFG->dirroot . '/enrol/coursecompleted');
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('A required parameter (enrolid) was missing');
        include($CFG->dirroot . '/enrol/coursecompleted/manage.php');
    }

    /**
     * Test manager without permission.
     */
    public function test_manager_wrong_permission() {
        global $CFG;
        chdir($CFG->dirroot . '/enrol/coursecompleted');
        $this->setUser($this->student);
        $_POST['enrolid'] = $this->instance->id;
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('Sorry, but you do not currently have permissions to do that (Enrol users).');
        include($CFG->dirroot . '/enrol/coursecompleted/manage.php');
    }

    /**
     * Test manager wrong permission.
     */
    public function test_manager_wrong_permission2() {
        global $CFG, $DB;
        chdir($CFG->dirroot . '/enrol/coursecompleted');
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $role = $DB->get_record('role', ['shortname' => 'editingteacher']);
        $generator->enrol_user($user->id, $this->course->id, $role->shortname);
        $context = context_course::instance($this->course->id);
        assign_capability('enrol/coursecompleted:enrolpast', CAP_PROHIBIT, $role->id, $context);
        assign_capability('enrol/coursecompleted:unenrol', CAP_PROHIBIT, $role->id, $context);
        assign_capability('enrol/manual:enrol', CAP_ALLOW, $role->id, $context);
        \core\session\manager::init_empty_session();
        $this->setUser($user);
        $_POST['enrolid'] = $this->instance->id;
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('Sorry, but you do not currently have permissions to do that (Enrol users).');
        include($CFG->dirroot . '/enrol/coursecompleted/manage.php');
    }

    /**
     * Test manager bare.
     */
    public function test_manager_bare() {
        global $CFG;
        chdir($CFG->dirroot . '/enrol/coursecompleted');
        $_POST['enrolid'] = $this->instance->id;
        ob_start();
        include($CFG->dirroot . '/enrol/coursecompleted/manage.php');
        $html = ob_get_clean();
        $this->assertStringContainsString('No users found', $html);
    }

    /**
     * Test manager oldusers.
     */
    public function test_manager_oldusers() {
        global $CFG;
        $this->preventResetByRollback();
        $this->setAdminUser();
        $sink = $this->redirectEmails();
        $sank = $this->redirectMessages();
        $ccompletion = new \completion_completion(['course' => $this->course->id, 'userid' => $this->student->id]);
        $ccompletion->mark_complete(time());
        chdir($CFG->dirroot . '/enrol/coursecompleted');
        $_POST['enrolid'] = $this->instance->id;
        ob_start();
        include($CFG->dirroot . '/enrol/coursecompleted/manage.php');
        $html = ob_get_clean();
        $this->assertStringNotContainsString('No users found', $html);
        $sink->close();
        $sank->close();
    }

    /**
     * Test submit manager oldusers.
     */
    public function test_manager_submit() {
        global $CFG;
        $this->preventResetByRollback();
        $this->setAdminUser();
        set_config('messaging', false);
        $sink = $this->redirectEmails();
        $sank = $this->redirectMessages();
        $ccompletion = new \completion_completion(['course' => $this->course->id, 'userid' => $this->student->id]);
        $ccompletion->mark_complete(time());
        chdir($CFG->dirroot . '/enrol/coursecompleted');
        $_POST['enrolid'] = $this->instance->id;
        $_POST['action'] = 'enrol';
        $_POST['sesskey'] = sesskey();
        ob_start();
        include($CFG->dirroot . '/enrol/coursecompleted/manage.php');
        $html = ob_get_clean();
        $this->assertStringNotContainsString('No users found', $html);
        $sink->close();
        $sank->close();
    }

    /**
     * Tests settings.
     */
    public function test_enrol_courescompleted_settings() {
        global $ADMIN, $CFG;
        require_once($CFG->dirroot . '/lib/adminlib.php');
        $ADMIN = admin_get_root(true, true);
        $this->assertTrue($ADMIN->fulltree);
    }
}
