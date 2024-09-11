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

namespace enrol_coursecompleted;

/**
 * coursecompleted enrolment manager tests.
 *
 * @package   enrol_coursecompleted
 * @copyright 2017 eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \enrol_coursecompleted_plugin
 */
final class manager_test extends \advanced_testcase {
    /** @var stdClass Instance. */
    private $instance;

    /** @var stdClass Student. */
    private $student;

    /** @var stdClass course. */
    private $course;

    /**
     * Tests initial setup.
     */
    protected function setUp(): void {
        global $CFG, $DB;
        parent::setUp();
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
        $this->student = $generator->create_and_enrol($this->course, 'student');
        mark_user_dirty($this->student->id);
    }

    /**
     * Test missing enrolid param.
     * @covers \enrol_coursecompleted_plugin
     */
    public function test_manager_empty_param(): void {
        global $CFG;
        chdir($CFG->dirroot . '/enrol/coursecompleted');
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('A required parameter (enrolid) was missing');
        include($CFG->dirroot . '/enrol/coursecompleted/manage.php');
    }

    /**
     * Test manager without permission.
     * @covers \enrol_coursecompleted_plugin
     */
    public function test_manager_without_permission(): void {
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
     * @covers \enrol_coursecompleted_plugin
     */
    public function test_manager_wrong_permission(): void {
        global $CFG, $DB;
        chdir($CFG->dirroot . '/enrol/coursecompleted');
        $generator = $this->getDataGenerator();
        $user = $generator->create_and_enrol($this->course, 'editingteacher');
        $role = $DB->get_record('role', ['shortname' => 'editingteacher']);
        $context = \context_course::instance($this->course->id);
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
     * @covers \enrol_coursecompleted_plugin
     * @covers \enrol_coursecompleted\form\bulkedit
     * @covers \enrol_coursecompleted\form\bulkdelete
     */
    public function test_manager_bare(): void {
        global $CFG;
        chdir($CFG->dirroot . '/enrol/coursecompleted');
        $_POST['enrolid'] = $this->instance->id;
        ob_start();
        include($CFG->dirroot . '/enrol/coursecompleted/manage.php');
        $html = ob_get_clean();
        $this->assertStringContainsString('No users found', $html);
    }

    /**
     * Test manager old users.
     * @covers \enrol_coursecompleted_plugin
     */
    public function test_manager_old_users(): void {
        global $CFG, $DB;
        $this->preventResetByRollback();
        $this->setAdminUser();
        $cc = new \stdClass();
        $cc->userid = $this->student->id;
        $cc->course = $this->course->id;
        $cc->timestarted = time() - 100;
        $cc->timeenrolled = 0;
        $cc->timecompleted = time() - 50;
        $DB->insert_record('course_completions', $cc);
        chdir($CFG->dirroot . '/enrol/coursecompleted');
        $_POST['enrolid'] = $this->instance->id;
        ob_start();
        include($CFG->dirroot . '/enrol/coursecompleted/manage.php');
        $html = ob_get_clean();
        $this->assertStringNotContainsString('No users found', $html);
    }

    /**
     * Test submit manager oldusers.
     * @covers \enrol_coursecompleted_plugin
     */
    public function test_manager_submit(): void {
        global $CFG, $DB;
        $this->preventResetByRollback();
        $this->setAdminUser();
        set_config('messaging', false);
        $cc = new \stdClass();
        $cc->userid = $this->student->id;
        $cc->course = $this->course->id;
        $cc->timestarted = time() - 100;
        $cc->timeenrolled = 0;
        $cc->timecompleted = time() - 50;
        $DB->insert_record('course_completions', $cc);

        chdir($CFG->dirroot . '/enrol/coursecompleted');
        $_POST['enrolid'] = $this->instance->id;
        $_POST['action'] = 'enrol';
        $_POST['sesskey'] = sesskey();
        ob_start();
        include($CFG->dirroot . '/enrol/coursecompleted/manage.php');
        $html = ob_get_clean();
        $this->assertStringContainsString('1 Users enrolled', $html);
    }

    /**
     * Tests settings.
     * @covers \enrol_coursecompleted_plugin
     */
    public function test_enrol_courescompleted_settings(): void {
        global $ADMIN, $CFG;
        require_once($CFG->dirroot . '/lib/adminlib.php');
        $ADMIN = admin_get_root(true, true);
        $this->assertTrue($ADMIN->fulltree);
    }
}
