# README

## Quick summary

Course completed enrolment handles the enrolment of users upon completion of a course.

## Enrolment on course completed

* With this plugin it is possible to create a chain of courses.  The moment a student completes a course, he/she
  is automatically enrolled in one or more courses. But it can also be used to give a user another role when he/she
  completes the course.
* Administrators can also enrol users who completed past courses.
* Administrators can bulk modify and delete users (works only when 1 instance is installed [MDL-66652](https://tracker.moodle.org/browse/MDL-66652)).
* When a user is part of a group in the first course, the user will also be part of the group with the same name in the second course.

## Course completion

Check the global documentation about course completion: https://docs.moodle.org/402/en/Course_completion

## Installation:

 1. Unpack the zip file into the enrol/ directory. A new directory will be created called coursecompleted.
 2. Go to Site administration > Notifications to complete the plugin installation.

## Requirements

This plugin requires Moodle 4.0+

## Troubleshooting

 1. Goto "Administration" > "Advanced features", and ensure that "Enable completion tracking" is set to yes.
 2. Make sure "Enable completion tracking" is set to "yes" in the course settings.
 3. Goto "Administration" > "Course administration" > "Course completion", and configure the the conditions required for course completion. Note: you must set some conditions, you cannot just set the "completion requirements" option at the top. Save.
 4. Goto "Administration" > "Course administration". Make sure you can now "Course completion" listed under "reports". If you cannot see this report then course completion has not been set correctly.
 5. Start enrolling

## Theme support

This plugin is developed and tested on Moodle Core's Boost theme and Boost child themes, including Moodle Core's Classic theme.

## Plugin repositories

This plugin will be published and regularly updated on Github: https://github.com/ewallah/moodle-enrol_coursecompleted

## Bug and problem reports / Support requests

This plugin is carefully developed and thoroughly tested, but bugs and problems can always appear.
Please report bugs and problems on Github: https://github.com/ewallah/moodle-enrol_coursecompleted/issues
We will do our best to solve your problems, but please note that due to limited resources we can't always provide per-case support.

## Feature proposals

Please issue feature proposals on Github: https://github.com/ewallah/moodle-enrol_coursecompleted/issues
Please create pull requests on Github: https://github.com/ewallah/moodle-enrol_coursecompleted/pulls
We are always interested to read about your feature proposals or even get a pull request from you, but please accept that we can handle your issues only as feature proposals and not as feature requests.

## Moodle release support

This plugin is maintained for the latest major releases of Moodle.

## Maturity

Stable

## Status

[![Build Status](https://github.com/ewallah/moodle-enrol_coursecompleted/workflows/Tests/badge.svg)](https://github.com/ewallah/moodle-enrol_coursecompleted/actions)
[![Coverage Status](https://coveralls.io/repos/github/ewallah/moodle-enrol_coursecompleted/badge.svg?branch=main)](https://coveralls.io/github/ewallah/moodle-enrol_coursecompleted?branch=main)

## Copyright

2023 eWallah.net

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
