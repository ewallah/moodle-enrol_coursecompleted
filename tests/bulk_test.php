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
 * coursecompleted enrolment plugin bulk tests.
 *
 * @package   enrol_coursecompleted
 * @copyright 2017 eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * coursecompleted enrolment plugin bulk tests.
 *
 * @package   enrol_coursecompleted
 * @copyright 2017 eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_coursecompleted_bulk_testcase extends advanced_testcase {

    /**
     * Setup to ensure that fixtures are loaded.
     */
    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->libdir . '/formslib.php');
    }

    /**
     * Tests initial setup.
     */
    protected function setUp():void {
        global $CFG;
        $CFG->enablecompletion = true;
        $this->resetAfterTest(true);
        $enabled = enrol_get_plugins(true);
        $enabled['coursecompleted'] = true;
        set_config('enrol_plugins_enabled', implode(',', array_keys($enabled)));
    }

    /**
     * Test bulk deleted.
     * @coversDefaultClass enrol_coursecompleted_bulkdelete
     * @coversDefaultClass \enrol_coursecompleted\form\bulkdelete
     */
    public function test_bulkdeleted() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/enrol/coursecompleted/classes/enrol_coursecompleted_bulkdelete.php');
        require_once($CFG->dirroot . '/enrol/coursecompleted/classes/form/bulkdelete.php');
        $generator = $this->getDataGenerator();
        $plugin = enrol_get_plugin('coursecompleted');
        $studentid = $generator->create_user()->id;
        $course1 = $generator->create_course(['shortname' => 'A1', 'enablecompletion' => 1]);
        $course2 = $generator->create_course(['shortname' => 'B1', 'enablecompletion' => 1]);
        $generator->enrol_user($studentid, $course2->id);
        $id = $plugin->add_instance($course1, ['customint1' => $course2->id, 'roleid' => 5, 'customint2' => 0]);
        $instance = $DB->get_record('enrol', ['id' => $id]);
        $plugin->enrol_user($instance, $studentid);
        $page = new \moodle_page();
        $manager = new \course_enrolment_manager($page, $course1);
        $operation = new enrol_coursecompleted_bulkdelete($manager, $plugin);
        $this->assertEquals('deleteselectedusers', $operation->get_identifier());
        $this->assertEquals('Delete selected enrolments on course completion', $operation->get_title());
        $enr = new stdClass();
        $enr->enrolmentplugin = $plugin;
        $enr->enrolmentinstance = $instance;
        $user = new stdClass();
        $user->id = $studentid;
        $user->enrolments = [$enr];
        $this->assertfalse($operation->process($manager, [$user], new stdClass()));
        $this->setAdminUser();
        $this->assertTrue($operation->process($manager, [$user] , new stdClass()));
        try {
            $operation->get_form(null, null);
        } catch (Exception $e) {
            $this->assertEquals('Undefined index: users', $e->getmessage());
        }
    }

    /**
     * Test bulk edit.
     * @coversDefaultClass enrol_coursecompleted_bulkedit
     * @coversDefaultClass \enrol_coursecompleted\form\bulkedit
     */
    public function test_bulkedit() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/enrol/coursecompleted/classes/enrol_coursecompleted_bulkedit.php');
        require_once($CFG->dirroot . '/enrol/coursecompleted/classes/form/bulkedit.php');
        $generator = $this->getDataGenerator();
        $plugin = enrol_get_plugin('coursecompleted');
        $studentid = $generator->create_user()->id;
        $course1 = $generator->create_course(['shortname' => 'c1', 'enablecompletion' => 1]);
        $course2 = $generator->create_course(['shortname' => 'd1', 'enablecompletion' => 1]);
        $generator->enrol_user($studentid, $course2->id);
        $id = $plugin->add_instance($course1, ['customint1' => $course2->id, 'roleid' => 5]);
        $instance = $DB->get_record('enrol', ['id' => $id]);
        $plugin->enrol_user($instance, $studentid);
        $page = new \moodle_page();
        $manager = new \course_enrolment_manager($page, $course1);
        $operation = new enrol_coursecompleted_bulkedit($manager, $plugin);
        $this->assertEquals('editselectedusers', $operation->get_identifier());
        $this->assertEquals('Edit selected enrolments on course completion', $operation->get_title());
        $enr = new stdClass();
        $enr->enrolmentinstance = $instance;
        $enr->instance = $instance;
        $enr->id = $id;
        $user = new stdClass();
        $user->id = $studentid;
        $user->enrolments = [$enr];
        $properties = new stdClass();
        $properties->status = 1;
        $properties->timestart = time() - 100;
        $properties->timeend = time() + 1000;
        $this->assertfalse($operation->process($manager, [$user], $properties));
        $this->setAdminUser();
        $this->assertTrue($operation->process($manager, [$user] , $properties));
        $properties->status = 99;
        $properties->timestart = null;
        $properties->timeend = null;
        $this->assertTrue($operation->process($manager, [$user] , $properties));
        try {
            $operation->get_form(null, null);
        } catch (Exception $e) {
            $this->assertEquals('Undefined index: users', $e->getmessage());
        }
    }

}
