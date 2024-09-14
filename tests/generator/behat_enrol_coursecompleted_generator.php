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
 * Enrol coursecompleted generator.
 *
 * @package    enrol_coursecompleted
 * @copyright  eWallah.net
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Enrol coursecompleted generator.
 *
 * @package    enrol_coursecompleted
 * @copyright  eWallah.net
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_enrol_coursecompleted_generator extends behat_generator_base {

    /**
     * Functions supported.
     *
     * @return array
     */
    protected function get_creatable_entities(): array {
        return [
            'coursecompletions' => [
                'singular' => 'coursecompletion',
                'datagenerator' => 'coursecompletion',
                'required' => ['course', 'user'],
            ],
            'courseenrolments' => [
                'singular' => 'courseenrolment',
                'datagenerator' => 'courseenrolment',
                'required' => ['course', 'required'],
            ],
        ];
    }
}
