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
 * coursecompleted access plugin settings and presets.
 *
 * @package   enrol_coursecompleted
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    $plugin = 'enrol_coursecompleted';

    $settings->add(
        new admin_setting_heading(
            'enrol_coursecompleted_settings',
            '',
            get_string('pluginname_desc', $plugin),
        )
    );

    $settings->add(
        new admin_setting_heading(
            'enrol_coursecompleted_defaults',
            get_string('enrolinstancedefaults', 'admin'),
            get_string('enrolinstancedefaults_desc', 'admin'),
        )
    );

    $settings->add(
        new admin_setting_configcheckbox(
            'enrol_coursecompleted/defaultenrol',
            get_string('defaultenrol', 'enrol'),
            get_string('defaultenrol_desc', 'enrol'),
            0
        )
    );

    if (!during_initial_install()) {
        $roptions = [
            ENROL_EXT_REMOVED_KEEP => get_string('extremovedkeep', 'enrol'),
            ENROL_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'enrol'),
            ENROL_EXT_REMOVED_UNENROL => get_string('extremovedunenrol', 'enrol'),
        ];

        $options = get_default_enrol_roles(context_system::instance());
        $student = get_archetype_roles('student');
        $student = reset($student);

        $settings->add(
            new admin_setting_configselect(
                name: 'enrol_coursecompleted/expiredaction',
                visiblename: get_string(
                    identifier: 'expiredaction',
                    component: 'enrol_fee',
                ),
                description: get_string(
                    identifier: 'expiredaction_help',
                    component: 'enrol_fee',
                ),
                defaultsetting: ENROL_EXT_REMOVED_SUSPENDNOROLES,
                choices: $roptions,
            )
        );

        $settings->add(
            new admin_setting_configselect(
                name: 'enrol_coursecompleted/roleid',
                visiblename: get_string(
                    identifier: 'defaultrole',
                    component: $plugin,
                ),
                description: get_string(
                    identifier: 'defaultrole_desc',
                    component: $plugin,
                ),
                defaultsetting: $student->id,
                choices: $options,
            )
        );
    }

    $settings->add(
        new admin_setting_configduration(
            'enrol_coursecompleted/enrolperiod',
            get_string('enrolperiod', 'enrol_fee'),
            get_string('enrolperiod_desc', 'enrol_fee'),
            0,
        )
    );
    $settings->add(
        new admin_setting_configselect(
            name: 'enrol_coursecompleted/welcome',
            visiblename: get_string(
                identifier: 'welcome',
                component: $plugin,
            ),
            description: get_string(
                identifier: 'welcome_help',
                component: $plugin,
            ),
            defaultsetting: ENROL_SEND_EMAIL_FROM_COURSE_CONTACT,
            choices: enrol_coursecompleted_plugin::email_options(),
        )
    );

    $settings->add(
        new admin_setting_configcheckbox(
            'enrol_coursecompleted/svglearnpath',
            get_string('svglearnpath', $plugin),
            get_string('svglearnpath_help', $plugin),
            1
        )
    );

    $settings->add(
        new admin_setting_configcheckbox(
            'enrol_coursecompleted/keepgroup',
            get_string('keepgroup', $plugin),
            get_string('keepgroup_help', $plugin),
            1
        )
    );
}
