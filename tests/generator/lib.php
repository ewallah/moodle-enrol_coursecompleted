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
 * enrol_coursecompleted data generator.
 *
 * @package    enrol_coursecompleted
 * @category   test
 * @copyright  Renaat Debleu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * enrol_coursecompleted data generator class.
 *
 * @package    enrol_coursecompleted
 * @category   test
 * @copyright  Renaat Debleu.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_coursecompleted_generator extends testing_module_generator {

    /**
     * Create a course completed enrol instance.
     *
     * @param array|stdClass $record enrol instance.
     * @param array $options further, enrol-specific options to control how the instance is created.
     * @return stdClass the enrol_instance record that has just been created.
     */
    public function create_instance($record = null, array $options = null) {

        $record = (array)$record + ['name' => 'test'];
        return parent::create_instance($record, (array)$options);
    }
}
