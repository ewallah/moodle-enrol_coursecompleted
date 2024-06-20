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
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_coursecompleted;

use context_course;
use moodle_page;
use moodle_url;
use stdClass;

/**
 * coursecompleted enrolment plugin bulk tests.
 *
 * @package   enrol_coursecompleted
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \enrol_coursecompleted
 */
final class bulk_test extends \advanced_testcase {

    /** @var stdClass First course. */
    private $course1;

    /** @var stdClass Second course. */
    private $course2;

    /** @var stdClass Student. */
    private $student;

    /** @var stdClass Instance. */
    private $instance;

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
        $plugin = enrol_get_plugin('coursecompleted');
        $generator = $this->getDataGenerator();
        $this->course1 = $generator->create_course(['enablecompletion' => 1]);
        $this->course2 = $generator->create_course(['enablecompletion' => 1]);
        $suspendedid = $generator->create_and_enrol($this->course2, 'student')->id;
        $DB->set_field('user', 'suspended', 1, ['id' => $suspendedid]);
        $generator->create_and_enrol($this->course1, 'student');
        $generator->create_and_enrol($this->course1, 'teacher');
        $generator->create_and_enrol($this->course2, 'teacher');
        $this->student = $generator->create_and_enrol($this->course2, 'student');
        $id = $plugin->add_instance($this->course1, ['name' => 'A', 'customint1' => $this->course2->id, 'roleid' => 5]);
        $this->instance = $DB->get_record('enrol', ['id' => $id]);
        $plugin->enrol_user($this->instance, $this->student->id);
        $plugin->enrol_user($this->instance, $suspendedid);
    }

    /**
     * Test bulk delete.
     * @covers \enrol_coursecompleted\bulkdelete
     * @covers \enrol_coursecompleted\form\bulkdelete
     */
    public function test_bulk_delete(): void {
        $plugin = enrol_get_plugin('coursecompleted');
        $page = new moodle_page();
        $manager = new \course_enrolment_manager($page, $this->course1);
        $operation = new bulkdelete($manager, $plugin);
        $this->assertEquals('deleteselectedusers', $operation->get_identifier());
        $this->assertEquals('Delete selected enrolments on course completion', $operation->get_title());
        $enr1 = new stdClass();
        $enr1->status = true;
        $enr1->enrolmentplugin = $plugin;
        $enr1->enrolmentinstance = $this->instance;
        $enr2 = new stdClass();
        $enr2->status = true;
        $enr2->enrolmentplugin = $plugin;
        $enr2->enrolmentinstance = $this->instance;
        $user = new stdClass();
        $user->id = $this->student->id;
        $user->enrolments = [$enr1, $enr2];
        $this->assertFalse($operation->process($manager, [$user], new stdClass()));
        $this->assertTrue(user_has_role_assignment($this->student->id, 5, context_course::instance($this->course2->id)->id));
        $this->assertTrue(user_has_role_assignment($this->student->id, 5, context_course::instance($this->course1->id)->id));
        $this->setAdminUser();
        $this->assertTrue($operation->process($manager, [$user], new stdClass()));
        $this->assertTrue(user_has_role_assignment($this->student->id, 5, context_course::instance($this->course2->id)->id));
        $this->assertFalse(user_has_role_assignment($this->student->id, 5, context_course::instance($this->course1->id)->id));

        $form = $operation->get_form('Delete', ['users' => [$user]]);
        ob_start();
        $form->display();
        $html = ob_get_clean();
        $this->assertStringContainsString('<th class="header c0" style="" scope="col">Name</th>', $html);
        $this->assertStringContainsString('<th class="header c1" style="" scope="col">Status</th>', $html);
        $this->assertStringContainsString('<th class="header c2" style="" scope="col">Enrolment starts</th>', $html);
        $this->assertStringContainsString('<th class="header c3 lastcol" style="" scope="col">Enrolment ends</th>', $html);
    }

    /**
     * Test bulk delete.
     * @covers \enrol_coursecompleted\bulkdelete
     * @covers \enrol_coursecompleted\form\bulkdelete
     */
    public function test_bulk_delete2(): void {
        $this->setAdminUser();
        $plugin = enrol_get_plugin('coursecompleted');
        $page = new moodle_page();
        $manager = new \course_enrolment_manager($page, $this->course2);
        $operation = new bulkdelete($manager, $plugin);
        $enr = new stdClass();
        $enr->status = true;
        $enr->enrolmentplugin = $plugin;
        $enr->enrolmentinstance = $this->instance;
        $user = new stdClass();
        $user->id = $this->student->id;
        $user->enrolments = [$enr];
        $form = $operation->get_form('Delete', ['users' => [$user]]);
        ob_start();
        $form->display();
        $html = ob_get_clean();
        $this->assertStringContainsString('<th class="header c0" style="" scope="col">Name</th>', $html);
        $this->assertStringContainsString('<th class="header c1" style="" scope="col">Status</th>', $html);
        $this->assertStringContainsString('<th class="header c2" style="" scope="col">Enrolment starts</th>', $html);
        $this->assertStringContainsString('<th class="header c3 lastcol" style="" scope="col">Enrolment ends</th>', $html);
    }

    /**
     * Test bulk edit.
     * @covers \enrol_coursecompleted\bulkedit
     * @covers \enrol_coursecompleted\form\bulkedit
     */
    public function test_bulk_edit(): void {
        $this->assertTrue(user_has_role_assignment($this->student->id, 5, context_course::instance($this->course2->id)->id));
        $this->assertTrue(user_has_role_assignment($this->student->id, 5, context_course::instance($this->course1->id)->id));
        $plugin = enrol_get_plugin('coursecompleted');
        $page = new moodle_page();
        $manager = new \course_enrolment_manager($page, $this->course1);
        $operation = new bulkedit($manager, $plugin);
        $this->assertEquals('editselectedusers', $operation->get_identifier());
        $this->assertEquals('Edit selected enrolments on course completion', $operation->get_title());
        $enr = new stdClass();
        $enr->status = true;
        $enr->enrolmentinstance = $this->instance;
        $enr->instance = $this->instance;
        $enr->id = $this->instance->id;
        $user = new stdClass();
        $user->id = $this->student->id;
        $user->enrolments = [$enr];
        $properties = new stdClass();
        $properties->status = ENROL_USER_SUSPENDED;
        $properties->timestart = time() - 100;
        $properties->timeend = time() + 1000;
        $this->assertfalse($operation->process($manager, [$user], $properties));
        $this->setAdminUser();
        $this->assertTrue($operation->process($manager, [$user], $properties));
        $properties = new stdClass();
        $properties->status = ENROL_USER_ACTIVE;
        $sink = $this->redirectEvents();
        $this->assertTrue($operation->process($manager, [$user], $properties));
        $events = $sink->get_events();
        $sink->close();
        $this->assertCount(1, $events);
        foreach ($events as $eventinfo) {
            $this->assertTrue($eventinfo instanceof \core\event\user_enrolment_updated);
        }
        $properties = new stdClass();
        $this->assertTrue($operation->process($manager, [$user], $properties));
        $form = $operation->get_form(null, ['users' => [$user]]);
        ob_start();
        $form->display();
        $html = ob_get_clean();
        // TODO: suspended user shown but student not listed.
        $this->assertStringContainsString('<th class="header c0" style="" scope="col">Name</th>', $html);
        $this->assertStringContainsString('<th class="header c1" style="" scope="col">Status</th>', $html);
        $this->assertStringContainsString('<th class="header c2" style="" scope="col">Enrolment starts</th>', $html);
        $this->assertStringContainsString('<th class="header c3 lastcol" style="" scope="col">Enrolment ends</th>', $html);
    }
}
