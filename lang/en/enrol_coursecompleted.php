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
 * Strings for component 'enrol_coursecompleted', language 'en'.
 *
 * @package   enrol_coursecompleted
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['aftercourse'] = 'After completing course: {$a}';
$string['cachedef_compcourses'] = 'Enrolment on course completion cache';
$string['compcourse'] = 'Completed course';
$string['compcourse_help'] = 'Which course have to be completed.';
$string['confirmbulkdeleteenrolment'] = 'Are you sure you want to delete these user enrolments?';
$string['confirmbulkediteenrolment'] = 'Are you sure you want to change these user enrolments?';
$string['coursecompleted:config'] = 'Configure enrol coursecompletion instances';
$string['coursecompleted:enrolpast'] = 'Enrol users who completed courses in the past';
$string['coursecompleted:manage'] = 'Manage enrolled users';
$string['coursecompleted:unenrol'] = 'Unenrol users from course';
$string['coursecompleted:unenrolself'] = 'Unenrol self from the course';
$string['customwelcome'] = 'Custom welcome message';
$string['customwelcome_help'] = 'A custom welcome message may be added as plain text or Moodle-auto format, including HTML tags and multi-lang tags.

The following placeholders may be included in the message:

* Course name {$a->coursename}
* Completed course name {$a->completed}
* Link to user\'s profile page {$a->profileurl}
* User email {$a->email}
* User fullname {$a->fullname}';
$string['defaultrole'] = 'Default role assignment';
$string['defaultrole_desc'] = 'Select the role to assign to users when they are enrolled.';
$string['deleteselectedusers'] = 'Delete selected enrolments on course completion';
$string['editselectedusers'] = 'Edit selected enrolments on course completion';
$string['editusers'] = 'Change user enrolments';
$string['enroldate'] = 'Enrolment date';
$string['enroldate_help'] = 'If enabled, users will be automatically enrolled on a specific moment in the future.';
$string['enrolenddate'] = 'End date';
$string['enrolenddate_help'] = 'If enabled, users will be automatically enrolled until this date only. All course completions after this date will be ignored.';
$string['enrolperiod'] = 'Enrolment duration';
$string['enrolperiod_desc'] = 'Default length of time that the enrolment is valid. If set to zero, the enrolment duration will be unlimited by default.';
$string['enrolperiod_help'] = 'Length of time that the enrolment is valid, starting with the moment the user is enrolled. If disabled, the enrolment duration will be unlimited.';
$string['enrolstartdate'] = 'Start date';
$string['enrolstartdate_help'] = 'If enabled, users will only be enrolled automatically from this date onwards. All course completions before this date will be ignored.';
$string['group'] = 'Keep group';
$string['group_help'] = 'Try to add users to a group with the same name';
$string['keepgroup'] = 'Default keep group setting';
$string['keepgroup_help'] = 'Default try to add users to a group with the same name';
$string['pluginname'] = 'Course completed enrolment';
$string['pluginname_desc'] = 'Course completed enrol plugin grants access to courses upon coursecompleted of a course.';
$string['privacy:metadata'] = 'The Course completed enrolment plugin does not store any personal data.';
$string['processexpirationstask'] = 'Course completed enrolment expiry task';
$string['status'] = 'Enabled';
$string['status_desc'] = 'Allow enrolment by coursecompleted by default.';
$string['status_help'] = 'This setting determines if the course completed enrolment is enabled.';
$string['status_link'] = 'enrol/coursecompleted';
$string['svglearnpath'] = 'Display learning path';
$string['svglearnpath_help'] = 'Display (possible) learning path using svg icons.';
$string['unenrolusers'] = 'Unenrol users';
$string['uponcompleting'] = 'Upon completing course {$a}';
$string['usersenrolled'] = '{$a} Users enrolled';
$string['welcome'] = 'Send course welcome message';
$string['welcome_help'] = 'When a user is enrolled in a course by completing another course, a welcome message email may be sent.';
$string['welcometocourse'] = 'Welcome to {$a->coursename}!

Congratulations!

After successfully completing {$a->completed}, you are now automatically enrolled in the following course {$a->coursename}.';
$string['willbeenrolled'] = 'You will be enrolled in this course when you complete course {$a}';
