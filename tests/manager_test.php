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
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_coursecompleted;

use advanced_testcase;
use context_course;
use moodle_exception;
use stdClass;

/**
 * coursecompleted enrolment manager tests.
 *
 * @package   enrol_coursecompleted
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \enrol_coursecompleted_plugin
 */
final class manager_test extends advanced_testcase {
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
        $this->expectException(moodle_exception::class);
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
        $this->expectException(moodle_exception::class);
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
        $context = context_course::instance($this->course->id);
        assign_capability('enrol/coursecompleted:enrolpast', CAP_PROHIBIT, $role->id, $context);
        assign_capability('enrol/coursecompleted:unenrol', CAP_PROHIBIT, $role->id, $context);
        assign_capability('enrol/manual:enrol', CAP_ALLOW, $role->id, $context);
        \core\session\manager::init_empty_session();
        $this->setUser($user);
        $_POST['enrolid'] = $this->instance->id;
        $this->expectException(moodle_exception::class);
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
        $cc = new stdClass();
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
        $this->assertStringContainsString(fullname($this->student), $html);
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
        $cc = new stdClass();
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

    /**
     * Test settings page.
     * @covers \enrol_coursecompleted_plugin
     */
    public function test_settings_page(): void {
        global $CFG, $OUTPUT, $PAGE;
        $this->preventResetByRollback();
        $this->setAdminUser();
        ob_start();
        chdir($CFG->dirroot . '/admin');
        $_POST['section'] = 'enrolsettingscoursecompleted';
        $_POST['sesskey'] = sesskey();
        include($CFG->dirroot . '/admin/settings.php');
        $html = ob_get_clean();
        $this->assertStringContainsString('Default: No', $html);
        $this->assertStringContainsString('value="3" selected>Disable course enrolment and remove role', $html);
        $this->assertStringContainsString('<option value="5" selected>Student</option>', $html);
        $this->assertStringContainsString('value="604800" selected>weeks', $html);
        $this->assertStringContainsString('value="86400" selected>days', $html);
        $this->assertStringContainsString('value="1" selected>From the course contact', $html);
        $this->assertStringContainsString('id_s_enrol_coursecompleted_svglearnpath" checked', $html);
        $this->assertStringContainsString('id_s_enrol_coursecompleted_keepgroup" checked', $html);
        // Small hack to avoid warnings.
        $this->assertNotEmpty($PAGE);
        $this->assertNotEmpty($OUTPUT);
    }

    /**
     * Test access.
     * @covers \enrol_coursecompleted_plugin
     */
    public function test_access(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1]);
        $student = $generator->create_and_enrol($course, 'student');
        $editor = $generator->create_and_enrol($course, 'editingteacher');
        $this->setAdminUser();
        $context = context_course::instance($course->id);
        $this->assertTrue(has_capability('enrol/coursecompleted:config', $context));
        $this->assertTrue(has_capability('enrol/coursecompleted:enrolpast', $context));
        $this->assertTrue(has_capability('enrol/coursecompleted:manage', $context));
        $this->assertTrue(has_capability('enrol/coursecompleted:unenrol', $context));
        $this->assertTrue(has_capability('enrol/coursecompleted:unenrolself', $context));
        $this->setUser($student);
        $this->assertFalse(has_capability('enrol/coursecompleted:config', $context));
        $this->assertFalse(has_capability('enrol/coursecompleted:enrolpast', $context));
        $this->assertFalse(has_capability('enrol/coursecompleted:manage', $context));
        $this->assertFalse(has_capability('enrol/coursecompleted:unenrol', $context));
        $this->assertFalse(has_capability('enrol/coursecompleted:unenrolself', $context));
        $this->setUser($editor);
        $this->assertTrue(has_capability('enrol/coursecompleted:config', $context));
        $this->assertFalse(has_capability('enrol/coursecompleted:enrolpast', $context));
        $this->assertTrue(has_capability('enrol/coursecompleted:manage', $context));
        $this->assertTrue(has_capability('enrol/coursecompleted:unenrol', $context));
        $this->assertFalse(has_capability('enrol/coursecompleted:unenrolself', $context));
        assign_capability('enrol/coursecompleted:enrolpast', CAP_ALLOW, 3, $context);
        assign_capability('enrol/coursecompleted:unenrolself', CAP_ALLOW, 3, $context);
        $this->assertTrue(has_capability('enrol/coursecompleted:enrolpast', $context));
        $this->assertTrue(has_capability('enrol/coursecompleted:unenrolself', $context));
    }
}
