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
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
declare(strict_types=1);

namespace enrol_coursecompleted;

use advanced_testcase;
use context_course;
use Generator;
use PHPUnit\Framework\Attributes\{DataProvider, CoversClass};

/**
 * Coursecompleted enrolment plugin tests.
 *
 * @package   enrol_coursecompleted
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\enrol_coursecompleted_plugin::class)]
#[CoversClass(hook_listener::class)]
#[CoversClass(observer::class)]
#[CoversClass(task\process_future::class)]
final class time_enrolled_test extends advanced_testcase {
    #[\core\attribute\label('Initial setup')]
    protected function setUp(): void {
        global $CFG;
        require_once($CFG->dirroot . '/enrol/locallib.php');
        parent::setUp();
        $CFG->enablecompletion = true;
        $this->resetAfterTest(true);
    }

    #[\core\attribute\label('Test adhoc task')]
    public function test_adhoc_task(): void {
        global $DB;
        $gen = $this->getDataGenerator();
        $course1 = $gen->create_course();
        $course2 = $gen->create_course();
        $plugin = enrol_get_plugin('coursecompleted');
        $student = $gen->create_and_enrol($course1, 'student');
        $input = ['customint1' => $course1->id, 'roleid' => 5, 'customint4' => time() + 666];
        $plugin->add_instance($course2, $input);
        $instance = $DB->get_record(
            'enrol',
            [
                    'courseid' => $course2->id,
                    'customint1' => $course1->id,
                    'enrol' => 'coursecompleted',
                ],
            '*',
            MUST_EXIST
        );
        $adhock = new task\process_future();
        $adhock->set_userid($student->id);
        $adhock->set_custom_data($instance);
        $adhock->set_component('enrol_coursecompleted');
        $adhock->execute();
        \core\task\manager::queue_adhoc_task($adhock);
        \phpunit_util::run_all_adhoc_tasks();
        delete_course($course1, false);
        delete_course($course2, false);
    }

    #[\core\attribute\label('Test if user is enrolled for a specific time after completing a course')]
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
        $params = ['customint1' => $course3->id, 'roleid' => $studentrole, 'customint4' => time() + 300];
        $plugin->add_instance($course3, $params);
        $student = $generator->create_and_enrol($course3, 'student');
        $this->assertFalse(is_enrolled(context_course::instance($course1->id), $student->id));
        $this->assertFalse(is_enrolled(context_course::instance($course2->id), $student->id));
        $this->assertTrue(is_enrolled(context_course::instance($course3->id), $student->id));
        mark_user_dirty((int)$student->id);
        rebuild_course_cache((int)$course1->id);
        rebuild_course_cache((int)$course2->id);
        rebuild_course_cache((int)$course3->id);
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
                'context' => context_course::instance($course3->id),
                'courseid' => $course3->id,
                'other' => ['relateduserid' => $student->id],
            ]
        );
        mark_user_dirty((int)$student->id);
        $observer = new observer();
        $observer->enroluser($compevent);
        mark_user_dirty((int)$student->id);
        rebuild_course_cache((int)$course1->id);
        rebuild_course_cache((int)$course2->id);
        mark_user_dirty((int)$student->id);
        $this->assertTrue(is_enrolled(context_course::instance($course1->id), $student->id));
        $this->assertTrue(is_enrolled(context_course::instance($course2->id), $student->id));
        $this->assertCount(1, $manager1->get_user_enrolments($student->id));
        $ueinstance = $DB->get_record('user_enrolments', ['enrolid' => $id1, 'userid' => $student->id]);
        $this->assertNotEquals(0, $ueinstance->timestart);
        $this->assertEquals(0, $ueinstance->timeend);
        $ueinstance = $DB->get_record('user_enrolments', ['enrolid' => $id2, 'userid' => $student->id]);
        $this->assertNotEquals(0, $ueinstance->timestart);
        $this->assertGreaterThan(time(), $ueinstance->timeend);
        sleep(1);
        $trace = new \null_progress_trace();
        $this->assertEquals(0, $plugin->sync($trace));
        mark_user_dirty((int)$student->id);
        $this->assertTrue(is_enrolled(context_course::instance($course1->id), $student->id, '', true));
        $manager1 = new \course_enrolment_manager($PAGE, $course1);
        $this->assertCount(1, $manager1->get_user_enrolments($student->id));
        $this->assertTrue(is_enrolled(context_course::instance($course2->id), $student->id));
        $manager2 = new \course_enrolment_manager($PAGE, $course2);
        $this->assertCount(1, $manager2->get_user_enrolments($student->id));
        $plugin->set_config('expiredaction', ENROL_EXT_REMOVED_UNENROL);
        sleep(2);
        $this->assertEquals(0, $plugin->sync($trace));
        mark_user_dirty((int)$student->id);
        $this->assertFalse(is_enrolled(context_course::instance($course2->id), $student->id));
        $manager2 = new \course_enrolment_manager($PAGE, $course2);
        $this->assertCount(0, $manager2->get_user_enrolments($student->id));
        \phpunit_util::run_all_adhoc_tasks();
    }

    /**
     * Time provider.
     * return Generator
     */
    public static function enroltime_provider(): Generator {
        $plus = time() + 100000;
        $minus = time() - 100000;
        yield 'Not set' => [[], true];
        yield 'Start date' => [['enrolstartdate' => time()], true];
        yield 'Start date null' => [['enrolstartdate' => null], true];
        yield 'Start date zero' => [['enrolstartdate' => 0], true];
        yield 'Start date later' => [['enrolstartdate' => $plus], false];
        yield 'Start date sooner' => [['enrolstartdate' => $minus], true];
        yield 'End date' => [['enrolenddate' => time()], false];
        yield 'End date later' => [['enrolenddate' => $plus], true];
        yield 'End date sooner' => [['enrolenddate' => $minus], false];
        yield 'Enrolment date' => [['customint4' => time()], true];
        yield 'Enrolment date null' => [['customint4' => null], true];
        yield 'Enrolment date later' => [['customint4' => $plus], false];
        yield 'Enrolment date sooner' => [['customint4' => $minus], true];
        yield 'Duration only' => [['enrolperiod' => 3000], true];
        yield 'Duration and enddate' => [['enrolperiod' => 3000, 'customint4' => $plus], false];
    }

    /**
     * Test enrol time variation.
     *
     * @dataProvider enroltime_provider
     */
    #[DataProvider('enroltime_provider')]
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
                'context' => context_course::instance($course1->id),
                'courseid' => $course1->id,
                'other' => ['relateduserid' => $student->id],
            ]
        );
        $observer = new observer();
        $observer->enroluser($compevent);
        $this->assertEquals($isenrolled, is_enrolled(context_course::instance($course2->id), $student->id));
    }
}
