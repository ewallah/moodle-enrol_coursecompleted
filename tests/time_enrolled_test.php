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
 * Coursecompleted enrolment plugin tests.
 *
 * @package   enrol_coursecompleted
 * @copyright 2017 eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_coursecompleted;

/**
 * oursecompleted enrolment plugin tests.
 *
 * @package   enrol_coursecompleted
 * @copyright 2017 eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \enrol_coursecompleted_plugin
 */
final class time_enrolled_test extends \advanced_testcase {
    /**
     * Tests initial setup.
     */
    protected function setUp(): void {
        global $CFG;
        require_once($CFG->dirroot . '/enrol/locallib.php');
        parent::setUp();
        $CFG->enablecompletion = true;
        $this->resetAfterTest(true);
        $enabled = enrol_get_plugins(true);
        unset($enabled['guest']);
        unset($enabled['self']);
        $enabled['coursecompleted'] = true;
        set_config('enrol_plugins_enabled', implode(',', array_keys($enabled)));
    }

    /**
     * Test if user is enrolled for a specific time after completing a course.
     * @covers \enrol_coursecompleted_plugin
     * @covers \enrol_coursecompleted\observer
     */
    public function test_time_enrolled(): void {
        global $DB, $PAGE;
        $generator = $this->getDataGenerator();
        $course1 = $generator->create_course();
        $course2 = $generator->create_course();
        $course3 = $generator->create_course(['enablecompletion' => 1]);
        $studentrole = $DB->get_field('role', 'id', ['shortname' => 'student']);
        $params = ['customint1' => $course3->id, 'roleid' => $studentrole];
        $plugin = enrol_get_plugin('coursecompleted');
        $id1 = $plugin->add_instance($course1, $params);
        $params = ['customint1' => $course3->id, 'roleid' => $studentrole, 'enrolperiod' => 2];
        $id2 = $plugin->add_instance($course2, $params);
        $params = ['customint1' => $course3->id, 'roleid' => $studentrole, 'enrolstartdate' => 100, 'enrolenddate' => 200];
        $plugin->add_instance($course3, $params);
        $student = $generator->create_and_enrol($course3, 'student');
        $this->assertFalse(is_enrolled(\context_course::instance($course1->id), $student->id));
        $this->assertFalse(is_enrolled(\context_course::instance($course2->id), $student->id));
        $this->assertTrue(is_enrolled(\context_course::instance($course3->id), $student->id));
        mark_user_dirty($student->id);
        rebuild_course_cache($course1->id);
        rebuild_course_cache($course2->id);
        rebuild_course_cache($course3->id);
        $PAGE->set_url('/enrol/editinstance.php');
        $manager1 = new \course_enrolment_manager($PAGE, $course1);
        $this->assertCount(0, $manager1->get_user_enrolments($student->id));
        $manager2 = new \course_enrolment_manager($PAGE, $course2);
        $this->assertCount(0, $manager2->get_user_enrolments($student->id));
        $manager3 = new \course_enrolment_manager($PAGE, $course3);
        $this->assertCount(1, $manager3->get_user_enrolments($student->id));
        $compevent = \core\event\course_completed::create(
            [
                'objectid' => $course1->id,
                'relateduserid' => $student->id,
                'context' => \context_course::instance($course3->id),
                'courseid' => $course3->id,
                'other' => ['relateduserid' => $student->id],
            ]
        );
        mark_user_dirty($student->id);
        $observer = new \enrol_coursecompleted\observer();
        $observer->enroluser($compevent);
        mark_user_dirty($student->id);
        rebuild_course_cache($course1->id);
        rebuild_course_cache($course2->id);
        mark_user_dirty($student->id);
        $this->assertTrue(is_enrolled(\context_course::instance($course1->id), $student->id));
        $this->assertTrue(is_enrolled(\context_course::instance($course2->id), $student->id));
        $this->assertCount(1, $manager1->get_user_enrolments($student->id));
        $ueinstance = $DB->get_record('user_enrolments', ['enrolid' => $id1, 'userid' => $student->id]);
        $this->assertEquals(0, $ueinstance->timestart);
        $this->assertEquals(0, $ueinstance->timeend);
        $ueinstance = $DB->get_record('user_enrolments', ['enrolid' => $id2, 'userid' => $student->id]);
        $this->assertEquals(0, $ueinstance->timestart);
        $this->assertGreaterThan(time(), $ueinstance->timeend);
        sleep(1);
        $trace = new \null_progress_trace();
        $plugin->sync($trace);
        mark_user_dirty($student->id);
        $this->assertTrue(is_enrolled(\context_course::instance($course1->id), $student->id, '', true));
        $manager1 = new \course_enrolment_manager($PAGE, $course1);
        $this->assertCount(1, $manager1->get_user_enrolments($student->id));
        $this->assertTrue(is_enrolled(\context_course::instance($course2->id), $student->id));
        $manager2 = new \course_enrolment_manager($PAGE, $course2);
        $this->assertCount(1, $manager2->get_user_enrolments($student->id));
        $plugin->set_config('expiredaction', ENROL_EXT_REMOVED_UNENROL);
        sleep(2);
        $plugin->sync($trace);
        mark_user_dirty($student->id);
        $this->assertFalse(is_enrolled(\context_course::instance($course2->id), $student->id));
        $manager2 = new \course_enrolment_manager($PAGE, $course2);
        $this->assertCount(0, $manager2->get_user_enrolments($student->id));
    }

    /**
     * Time provider.
     */
    public static function enroltime_provider(): array {
        $plus = time() + 10000;
        $minus = time() - 10000;
        return [
            'Not set' => [[], true],
            'Start date later' => [['enrolstartdate' => $plus], false],
            'Start date sooner' => [['enrolstartdate' => $minus], true],
            'End date later' => [['enrolenddate' => $plus], false],
            'End date sooner' => [['enrolenddate' => $minus], true],
            'Duration only' => [['enrolenddate' => $minus, 'enrolperiod' => 300], true],
        ];
    }

    /**
     * Test enrol time variation.
     *
     * @covers \enrol_coursecompleted_plugin
     * @dataProvider enroltime_provider
     * @param array $input
     * @param bool $isenrolled
     */
    public function test_enroltime_with_provider(array $input, bool $isenrolled): void {
        $generator = $this->getDataGenerator();
        $course1 = $generator->create_course();
        $course2 = $generator->create_course();
        $plugin = enrol_get_plugin('coursecompleted');
        $student = $generator->create_and_enrol($course1, 'student');
        $input = array_merge($input, ['customint1' => $course1->id, 'roleid' => 5]);
        $plugin->add_instance($course2, $input);
        $compevent = \core\event\course_completed::create(
            [
                'objectid' => $course2->id,
                'relateduserid' => $student->id,
                'context' => \context_course::instance($course1->id),
                'courseid' => $course1->id,
                'other' => ['relateduserid' => $student->id],
            ]
        );
        $observer = new observer();
        $observer->enroluser($compevent);
        $this->assertEquals($isenrolled, is_enrolled(\context_course::instance($course2->id), $student->id));
    }
}
