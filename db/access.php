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
 * Capabilities for coursecompleted access plugin.
 *
 * @package   enrol_coursecompleted
 * @copyright 2017 eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$caps = [
    'captype' => 'write',
    'contextlevel' => CONTEXT_COURSE,
    'archetypes' => [
        'manager' => CAP_ALLOW,
        'editingteacher' => CAP_ALLOW,
    ],
];
$capabilities = [
    'enrol/coursecompleted:config' => $caps,
    'enrol/coursecompleted:enrolpast' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => ['manager' => CAP_ALLOW],
    ],
    'enrol/coursecompleted:manage' => $caps,
    'enrol/coursecompleted:unenrol' => $caps,
    'enrol/coursecompleted:unenrolself' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [],
    ],
];
