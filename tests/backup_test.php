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
final class backup_test extends advanced_testcase {
    /** @var stdClass Student. */
    private $student;

    /** @var stdClass First course. */
    private $course1;

    /** @var stdClass Second course. */
    private $course2;

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
        $studentrole = $DB->get_field('role', 'id', ['shortname' => 'student']);
        $this->setAdminUser();
        $plugin = enrol_get_plugin('coursecompleted');
        $plugin->add_instance(
            $this->course2,
            ['customint1' => $this->course1->id, 'customint2' => 0, 'roleid' => $studentrole]
        );
        $this->student = $generator->create_and_enrol($this->course1, 'student');
    }

    /**
     * Test backup.
     * @covers \enrol_coursecompleted_plugin
     */
    public function test_backup_restore(): void {
        global $CFG, $DB, $PAGE;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        require_once($CFG->dirroot . '/enrol/locallib.php');

        $ccompletion = new \completion_completion(['course' => $this->course1->id, 'userid' => $this->student->id]);
        $ccompletion->mark_complete(time());
        $bc = new \backup_controller(
            \backup::TYPE_1COURSE,
            $this->course2->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            2
        );
        $bc->execute_plan();
        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $fp = get_file_packer('application/vnd.moodle.backup');
        $filepath = $CFG->dataroot . '/temp/backup/test-restore-course-event';
        $file->extract_to_pathname($fp, $filepath);
        $bc->destroy();
        $rc = new \restore_controller(
            'test-restore-course-event',
            $this->course2->id,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            2,
            \backup::TARGET_NEW_COURSE
        );
        $rc->execute_precheck();
        $rc->execute_plan();
        $newid = $rc->get_courseid();
        $rc->destroy();
        $this->assertEquals(2, $DB->count_records('enrol', ['enrol' => 'coursecompleted']));
        $this->assertTrue(is_enrolled(\context_course::instance($newid), $this->student->id));
        $url = new \moodle_url('/user/index.php', ['id' => $newid]);
        $PAGE->set_url($url);
        $course = get_course($newid);
        $manager = new \course_enrolment_manager($PAGE, $course);
        $enrolments = $manager->get_user_enrolments($this->student->id);
        $this->assertCount(2, $enrolments);
        $bc = new \backup_controller(
            \backup::TYPE_1COURSE,
            $this->course2->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            2
        );
        $bc->execute_plan();
        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $fp = get_file_packer('application/vnd.moodle.backup');
        $filepath = $CFG->dataroot . '/temp/backup/test-restore-course-event';
        $file->extract_to_pathname($fp, $filepath);
        $bc->destroy();
        $rc = new \restore_controller(
            'test-restore-course-event',
            $newid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            2,
            \backup::TARGET_EXISTING_ADDING
        );
        $rc->execute_precheck();
        $rc->execute_plan();
        $rc->destroy();
        $this->assertEquals(2, $DB->count_records('enrol', ['enrol' => 'coursecompleted']));
        $this->assertTrue(is_enrolled(\context_course::instance($newid), $this->student->id));
    }
}
