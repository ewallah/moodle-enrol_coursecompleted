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
 * @copyright 2017 eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    // General settings.
    $settings->add(new admin_setting_heading('enrol_coursecompleted_settings', '',
         get_string('pluginname_desc', 'enrol_coursecompleted')));
    // Enrol instance defaults.
    $settings->add(new admin_setting_heading('enrol_coursecompleted_defaults',
        get_string('enrolinstancedefaults', 'admin'), get_string('enrolinstancedefaults_desc', 'admin')));
    $settings->add(new admin_setting_configcheckbox('enrol_coursecompleted/defaultenrol',
        get_string('defaultenrol', 'enrol'), get_string('defaultenrol_desc', 'enrol'), 0));

    if (!during_initial_install()) {
        $options = [ENROL_EXT_REMOVED_KEEP           => get_string('extremovedkeep', 'enrol'),
                    ENROL_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'enrol'),
                    ENROL_EXT_REMOVED_UNENROL        => get_string('extremovedunenrol', 'enrol')];
        $settings->add(new admin_setting_configselect('enrol_coursecompleted/expiredaction',
                                                       get_string('expiredaction', 'enrol_paypal'),
                                                       get_string('expiredaction_help', 'enrol_paypal'),
                                                       ENROL_EXT_REMOVED_SUSPENDNOROLES,
                                                       $options));

        $options = get_default_enrol_roles(context_system::instance());
        $student = get_archetype_roles('student');
        $student = reset($student);
        $settings->add(new admin_setting_configselect('enrol_coursecompleted/roleid',
                                                      get_string('defaultrole', 'enrol_paypal'),
                                                      get_string('defaultrole_desc', 'enrol_paypal'),
                                                      $student->id,
                                                      $options));
    }
    $settings->add(new admin_setting_configduration('enrol_coursecompleted/enrolperiod',
                                                    get_string('enrolperiod', 'enrol_paypal'),
                                                    get_string('enrolperiod_desc', 'enrol_paypal'),
                                                    0));
    $settings->add(new admin_setting_configcheckbox('enrol_coursecompleted/welcome',
        get_string('welcome', 'enrol_coursecompleted'), get_string('welcome_help', 'enrol_coursecompleted'), 1));

    $settings->add(new admin_setting_configcheckbox('enrol_coursecompleted/svglearnpath',
        get_string('svglearnpath', 'enrol_coursecompleted'), get_string('svglearnpath_help', 'enrol_coursecompleted'), 1));
    $settings->add(new admin_setting_configcheckbox('enrol_coursecompleted/showcontinuebutton',
        get_string('showcontinuebutton', 'enrol_coursecompleted'), get_string('showcontinuebutton_help', 'enrol_coursecompleted'), 0));
    $settings->add(new admin_setting_configcheckbox('enrol_coursecompleted/keepgroup',
        get_string('keepgroup', 'enrol_coursecompleted'), get_string('keepgroup_help', 'enrol_coursecompleted'), 1));
}
