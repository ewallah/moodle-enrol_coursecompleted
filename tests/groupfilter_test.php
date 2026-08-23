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
 * Tests for the source-course group filter.
 *
 * @package   enrol_coursecompleted
 * @copyright Danimarqz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace enrol_coursecompleted;

use advanced_testcase;
use context_course;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the source-course group filter.
 *
 * @package   enrol_coursecompleted
 * @copyright Danimarqz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\enrol_coursecompleted_plugin::class)]
final class groupfilter_test extends advanced_testcase {
    /**
     * Only members of the selected source-course groups get enrolled;
     * an empty filter enrols everyone.
     */
    public function test_group_filter(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/group/lib.php');

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        $source = $generator->create_course(['shortname' => 'src', 'enablecompletion' => 1]);
        $target = $generator->create_course(['shortname' => 'tgt']);
        $filtered = $generator->create_course(['shortname' => 'flt']);
        $studentrole = $DB->get_field('role', 'id', ['shortname' => 'student']);

        // Group AGOSTO in the source course; only $inuser belongs to it.
        // create_group() returns the record; the plugin stores group ids, so keep the id.
        $agosto = (int)$generator->create_group(['courseid' => $source->id, 'name' => 'AGOSTO'])->id;
        $generator->create_group(['courseid' => $source->id]);
        $generator->create_group(['courseid' => $source->id]);
        $tmpgroup = (int)$generator->create_group(['courseid' => $source->id])->id;
        $inuser = $generator->create_and_enrol($source, 'student');
        $outuser = $generator->create_and_enrol($source, 'student');
        groups_add_member($agosto, $inuser->id);
        groups_add_member($tmpgroup, $inuser->id);

        $plugin = enrol_get_plugin('coursecompleted');

        // Instance restricted to AGOSTO.
        $id = $plugin->add_instance($target, [
            'status'      => ENROL_INSTANCE_ENABLED,
            'customint1'  => $source->id,
            'customint2'  => ENROL_DO_NOT_SEND_EMAIL,
            'customint3'  => 0,
            'roleid'      => $studentrole,
            'groupfilter' => [$agosto, $agosto, (int)$inuser->id],
        ]);
        $instance = $DB->get_record('enrol', ['id' => $id], '*', MUST_EXIST);
        $this->assertSame(json_encode([$agosto, $agosto, (int)$inuser->id]), $instance->customtext2);

        $plugin->enrol_user($instance, $inuser->id);
        $plugin->enrol_user($instance, $outuser->id);

        $targetctx = context_course::instance($target->id);
        $this->assertTrue(is_enrolled($targetctx, $inuser->id));
        $this->assertFalse(is_enrolled($targetctx, $outuser->id));

        // Instance with no filter enrols everyone.
        $id2 = $plugin->add_instance($filtered, [
            'status'     => ENROL_INSTANCE_ENABLED,
            'customint1' => $source->id,
            'customint2' => ENROL_DO_NOT_SEND_EMAIL,
            'customint3' => 0,
            'roleid'     => $studentrole,
        ]);
        $instance2 = $DB->get_record('enrol', ['id' => $id2], '*', MUST_EXIST);
        $this->assertSame('[]', $instance2->customtext2);
        $this->assertFalse($plugin->passes_group_filter($instance, 0));

        $plugin->enrol_user($instance2, $inuser->id);
        $plugin->enrol_user($instance2, $outuser->id);

        $filteredctx = context_course::instance($filtered->id);
        $this->assertTrue(is_enrolled($filteredctx, $inuser->id));
        $this->assertTrue(is_enrolled($filteredctx, $outuser->id));
    }

    /**
     * The enrolment page only promises enrolment to users who pass the group filter.
     */
    public function test_enrol_page_hook_respects_group_filter(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/group/lib.php');

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        $source = $generator->create_course(['shortname' => 'src', 'enablecompletion' => 1]);
        $target = $generator->create_course(['shortname' => 'tgt']);
        $studentrole = $DB->get_field('role', 'id', ['shortname' => 'student']);

        $agosto = (int)$generator->create_group(['courseid' => $source->id, 'name' => 'AGOSTO'])->id;
        $inuser = $generator->create_and_enrol($source, 'student');
        $outuser = $generator->create_and_enrol($source, 'student');
        groups_add_member($agosto, $inuser->id);

        $plugin = enrol_get_plugin('coursecompleted');
        $id = $plugin->add_instance($target, [
            'status'      => ENROL_INSTANCE_ENABLED,
            'customint1'  => $source->id,
            'customint2'  => ENROL_DO_NOT_SEND_EMAIL,
            'customint3'  => 0,
            'roleid'      => $studentrole,
            'groupfilter' => [$agosto],
        ]);
        $instance = $DB->get_record('enrol', ['id' => $id], '*', MUST_EXIST);

        $this->setUser($inuser);
        $this->assertNotEmpty($plugin->enrol_page_hook($instance));

        $this->setUser($outuser);
        $this->assertSame('', $plugin->enrol_page_hook($instance));
    }
}
