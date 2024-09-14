# README

## Quick summary

Course completed enrolment handles the enrolment of users upon completion of a course.

## Enrolment on course completed

* With this plugin it is possible to create a chain of courses.  The moment a student completes a course, the person
  is automatically enrolled in one or more courses. But it can also be used to give a user another role after completing
  the course.
* Administrators can also enrol users who completed past courses.
* Administrators can bulk modify and delete users (works only when 1 instance is installed [MDL-66652](https://tracker.moodle.org/browse/MDL-66652)).
* When a user is part of a group in the first course, the user will also be part of the group with the same name in the second course.
* When you want to enrol all users only after a particular date, configure this date as part of the course completion.
* When you want that this plugin only works for a limited period, configure enrolment start and end date. Before and after this date, this plugin will do nothing.
* Now you can also enrol a user in the future (the welcome message will also be sent only the moment the user is enrolled).

## Warning

This plugin is 100% open source and has NOT been tested in Moodle Workplace, Totara, or any other proprietary software system.
As long as the latter do not reward plugin developers, you can use this plugin only in 100% open source environments.

## Course completion

Check the global documentation about course completion: https://docs.moodle.org/404/en/Course_completion

## Installation:

 1. Unpack the zip file into the enrol/ directory. A new directory will be created called coursecompleted.
 2. Go to Site administration > Notifications to complete the plugin installation.

## Requirements

This plugin requires Moodle 4.4+

## Troubleshooting

 1. Goto "Administration" > "Advanced features", and ensure that "Enable completion tracking" is set to yes.
 2. Make sure "Enable completion tracking" is set to "yes" in the course settings.
 3. Goto "Administration" > "Course administration" > "Course completion", and configure the the conditions required for course completion. Note: you must set some conditions, you cannot just set the "completion requirements" option at the top. Save.
 4. Goto "Administration" > "Course administration". Make sure you can now "Course completion" listed under "reports". If you cannot see this report then course completion has not been set correctly.
 5. Make sure the enrolment start date is disabled or < now AND enrolment end date is disabled or > now.
 6. Check the Adhod tasks ("Server > Tasks > Ad hoc tasks" in site administration) and detect postponed enrolments.

## Theme support

This plugin is developed and tested on Moodle Core's Boost theme and Boost child themes, including Moodle Core's Classic theme.

## Database support

This plugin is developed and tested using

* MYSQL
* MariaDB
* PostgreSQL

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
![Mutation score](https://badgen.net/badge/Mutation%20Score%20Indicator/87)

## Copyright

eWallah.net

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
