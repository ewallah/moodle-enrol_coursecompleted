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
 * Coursecompleted enrolment form tests.
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
use moodle_page;
use moodle_url;
use stdClass;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Coursecompleted enrolment form tests.
 *
 * @package   enrol_coursecompleted
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\enrol_coursecompleted_plugin::class)]
final class form_test extends advanced_testcase {
    /** @var stdClass Instance. */
    private $instance;

    /** @var stdClass Plugin. */
    private $plugin;

    /**
     * Setup to ensure that forms and locallib are loaded.
     */
    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->libdir . '/formslib.php');
        require_once($CFG->dirroot . '/enrol/locallib.php');
        parent::setUpBeforeClass();
    }

    /**
     * Tests initial setup.
     */
    protected function setUp(): void {
        global $CFG, $DB;
        parent::setUp();

        $CFG->enablecompletion = true;
        $this->resetAfterTest(true);

        $this->plugin = enrol_get_plugin('coursecompleted');
        $generator = $this->getDataGenerator();
        $course1 = $generator->create_course(['startdate' => time() - 3600, 'shortname' => 'F1', 'enablecompletion' => 1]);
        $course2 = $generator->create_course(['shortname' => 'F2', 'enablecompletion' => 1]);
        $generator->create_course(['enablecompletion' => 1]);
        $generator->create_course(['enablecompletion' => 0]);

        $id = $this->plugin->add_instance(
            $course2,
            [
                'status' => ENROL_INSTANCE_ENABLED,
                'customint1' => $course1->id,
                'customint2' => ENROL_SEND_EMAIL_FROM_NOREPLY,
                'customint4' => 100,
                'roleid' => 3,
            ]
        );
        $this->instance = $DB->get_record('enrol', ['id' => $id]);
    }

    #[\core\attribute\label('Test edit instance validation')]
    public function test_edit_instance_validation(): void {
        $arr = ['status' => 0, 'customint1' => 666, 'enrolenddate' => time(), 'enrolstartdate' => time() + 10000];
        $tmp = $this->plugin->edit_instance_validation($arr, null, $this->instance, null);
        $this->assertEquals('The specified course does not exist', $tmp['customint1']);
        $this->assertEquals('The enrolment end date cannot be earlier than the start date.', $tmp['enrolenddate']);

        $arr = [
            'status' => 1,
            'customint1' => $this->instance->customint1,
            'enrolenddate' => time() + HOURSECS - 1,
            'enrolstartdate' => time(),
        ];
        $tmp = $this->plugin->edit_instance_validation($arr, null, $this->instance, null);
        $this->assertEquals('The enrolment end date cannot be earlier than the start date.', $tmp['enrolenddate']);

        $arr = ['customint1' => $this->instance->customint1, 'enrolenddate' => time() + HOURSECS, 'enrolstartdate' => time()];
        $tmp = $this->plugin->edit_instance_validation($arr, null, $this->instance, null);
        $this->assertEquals('The enrolment end date cannot be earlier than the start date.', $tmp['enrolenddate']);

        $arr = ['customint1' => $this->instance->customint1, 'enrolenddate' => time() + HOURSECS + 1, 'enrolstartdate' => time()];
        $tmp = $this->plugin->edit_instance_validation($arr, null, $this->instance, null);
        $this->assertEquals([], $tmp);

        $arr = ['roleid' => 3, 'status' => 0, 'customint1' => $this->instance->courseid];
        $tmp = $this->plugin->edit_instance_validation($arr, null, $this->instance, null);
        $this->assertEquals([], $tmp);
    }

    #[\core\attribute\label('Test form with default values')]
    public function test_form_default_values(): void {
        $page = new moodle_page();
        $context = context_course::instance($this->instance->courseid);
        $course = get_course($this->instance->courseid);
        $page->set_context($context);
        $page->set_course($course);
        $page->set_pagelayout('standard');
        $page->set_pagetype('course-view');
        $page->set_url('/enrol/coursecompleted/manage.php?enrolid=' . $this->instance->id);

        $form = $this->tempform();
        $mform = $form->getform();
        $this->plugin->edit_instance_form($this->instance, $mform, $context);
        $this->assertStringContainsString('Required field', $mform->getReqHTML());

        $html = $form->render();
        $cleaned = preg_replace('/\s+/', '', $html);

        $strs = [
            'optionvalue="604800"selected',
            '<optionvalue="2">Fromthekeyholder',
        ];

        foreach ($strs as $str) {
            $this->assertStringNotContainsString($str, $cleaned);
        }

        $strs = [
            '-select"name="status"id="id_status"><optionvalue="0">Yes</option>',
            '<optionvalue="5"selected>Student</option>',
            'optionvalue="86400"selected',
            'Ifdisabled,theenrolmentdurationwillbeunlimited',
            '<legendclass="visually-hidden">Enrolmentduration</legend>',
            '<optionvalue="0">No</option>',
            'd="id_enrolstartdate_enabled"value="1">Enable</label>',
            'cols="60"rows="8"',
            'name="customint3"class="form-check-input"value="1"id="id_customint3"',
            'fieldsetdata-fieldtype="date_time"class="m-0p-0border-0"id="id_customint4"',
            'name="customint4[enabled]"',
            'name="customint5"class="form-check-input"value="1"id="id_customint5"',
            '-select"name="status"id="id_status"><optionvalue="0">Yes</option>',
            '-select"name="customint2"id="id_customint2">',
            '<optionvalue="1"selected>Fromthecoursecontact',
            '<optionvalue="3">Fromtheno-replyaddress',
            '-select"name="roleid"id="id_roleid"><optionvalue="5"selected>Student',
            '<iclass="iconfafa-circle-exclamationtext-dangerfa-fw"aria-hidden="true"title="Requiredfield"></i>Required</div>',
            '<divclass="fdescriptionrequired"aria-hidden="true">',
            '<inputname="sesskey"type="hidden"value="',
            '<labelid="id_roleid_label"class="d-inlineword-break"for="id_roleid">Assignrole</label>',
        ];

        foreach ($strs as $str) {
            $this->assertStringContainsString($str, $cleaned);
        }

        $arr = [
            'Enrolmentdate',
            'Enrolmentduration',
            'Completedcourse',
            'Keepgroup',
            'Sendcoursewelcomemessage',
            'Customwelcomemessage',
            'Startdate',
            'Enddate',
        ];
        foreach ($arr as $value) {
            $this->assertStringContainsString('title="Helpwith' . $value . '"role="img"', $cleaned);
        }

        $strm = get_string_manager();
        $arr = ['compcourse', 'customwelcome', 'enrolenddate', 'enrolstartdate', 'group'];
        foreach ($arr as $value) {
            if ($strm->string_exists($value, 'enrol_coursecompleted')) {
                $this->assertStringContainsString(get_string($value, 'enrol_coursecompleted'), $html);
            }

            if ($strm->string_exists($value . '_desc', 'enrol_coursecompleted')) {
                $this->assertStringContainsString(get_string($value . '_desc', 'enrol_coursecompleted'), $html);
            }
        }
    }

    #[\core\attribute\label('Test form with other values')]
    public function test_form_other_config(): void {
        $plugin = enrol_get_plugin('coursecompleted');
        $plugin->set_config('status', 1);
        $plugin->set_config('roleid', 3);
        $plugin->set_config('expiredaction', ENROL_EXT_REMOVED_UNENROL);
        $plugin->set_config('welcome', ENROL_SEND_EMAIL_FROM_NOREPLY);
        $plugin->set_config('keepgroup', true);
        $plugin->set_config('enrolperiod', 3000);
        $plugin->set_config('svglearnpath', true);
        $this->assertEquals(get_config('enrol_coursecompleted', 'enrolperiod'), 3000);
        $this->assertEquals(get_config('enrol_coursecompleted', 'roleid'), 3);
        $this->assertEquals(get_config('enrol_coursecompleted', 'keepgroup'), 1);

        $page = new \moodle_page();
        $context = context_course::instance($this->instance->courseid);
        $course = get_course($this->instance->courseid);
        $page->set_context($context);
        $page->set_course($course);
        $page->set_pagelayout('standard');
        $page->set_pagetype('course-view');
        $page->set_url('/enrol/coursecompleted/manage.php?enrolid=' . $this->instance->id);

        $form = $this->tempform();
        $mform = $form->getform();
        $plugin->edit_instance_form($this->instance, $mform, $context);
        $this->assertStringContainsString('Required field', $mform->getReqHTML());
        ob_start();
        $mform->display();
        $html = ob_get_clean();
        $cleaned = preg_replace('/\s+/', '', $html);
        $this->assertStringContainsString(
            '<inputtype="checkbox"name="customint4[enabled]"class="form-check-input"id="id_customint4_enabled"value="1">',
            $cleaned
        );
        $strs = [
            '<optionvalue="5"selected>Student</option>',
        ];

        foreach ($strs as $str) {
            $this->assertStringNotContainsString($str, $cleaned);
        }

        $strs = [
            '-select"name="status"id="id_status"><optionvalue="0">Yes</option>',
            '-select"name="customint2"id="id_customint2">',
            'name="customint3"class="form-check-input"value="1"id="id_customint3"',
            '<optionvalue="1"selected>No</option>',
            '<optionvalue="0">No</option>',
            '<optionvalue="1">Fromthecoursecontact</option>',
            '<optionvalue="3"selected>Fromtheno-replyaddress</option>',
            '<optionvalue="86400"selected>days</option>',
            '<optionvalue="3"selected>Teacher</option>',
            'cols="60"rows="8"',
            'HelpwithCompletedcourse',
            'HelpwithEnrolmentdate',
            'HelpwithEnrolmentduration',
            'HelpwithUnenroluserfromcompletedcourse',
        ];
        foreach ($strs as $str) {
            $this->assertStringContainsString($str, $cleaned);
        }
    }

    /**
     * Test form.
     */
    private function tempform(): \moodleform {
        /**
         * Coursecompleted enrolment form tests.
         *
         * @package   enrol_coursecompleted
         * @copyright eWallah (www.eWallah.net)
         * @author    Renaat Debleu <info@eWallah.net>
         * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
         */
        return new class extends \moodleform {
            /**
             * Form definition.
             */
            public function definition(): void {
                // No definition required.
            }

            /**
             * Returns form reference
             * @return MoodleQuickForm
             */
            public function getform() {
                $mform = $this->_form;
                // Simulate submission.
                $mform->_flagSubmitted = true;
                return $mform;
            }
        };
    }
}
