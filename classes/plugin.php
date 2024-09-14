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
 * Enrol coursecompleted plugin
 *
 * @package   enrol_coursecompleted
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Enrol coursecompleted plugin
 *
 * @package   enrol_coursecompleted
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_coursecompleted_plugin extends enrol_plugin {

    /**
     * Returns localised name of enrol instance
     *
     * @param object $instance (null is accepted too)
     * @return string
     */
    public function get_instance_name($instance) {
        $tmp = is_null($instance) ? 'unknown' : $instance->customint1;
        if (
            $tmp !== 'unknown' &&
            $context = context_course::instance($tmp, IGNORE_MISSING)
        ) {
            $formatter = \core\di::get(\core\formatting::class);
            $name = $formatter->format_string(
                get_course($tmp)->shortname,
                context: $context,
                filter: false
            );
            return get_string('aftercourse', 'enrol_coursecompleted', $name);
        }
        return get_string('coursedeleted', '', $tmp);
    }

    /**
     * Returns optional enrolment information icons.
     *
     * @param array $instances all enrol instances of this type in one course
     * @return array of pix_icon
     */
    public function get_info_icons(array $instances) {
        $arr = [];
        $formatter = \core\di::get(\core\formatting::class);
        foreach ($instances as $instance) {
            if (
                $this->is_active($instance) &&
                $context = context_course::instance($instance->customint1, IGNORE_MISSING)
            ) {
                $name = $formatter->format_string(get_course($instance->customint1)->fullname, context: $context);
                $arr[] = new pix_icon('icon', get_string('aftercourse', 'enrol_coursecompleted', $name), 'enrol_coursecompleted');
            }
        }
        return $arr;
    }

    /**
     * Returns optional enrolment instance description text.
     *
     * @param object $instance
     * @return string short html text
     */
    public function get_description_text($instance) {
        $id = $instance->customint1;
        return "Enrolment by completion of course with id $id";
    }

    /**
     * Add information for people who want to enrol.
     *
     * @param stdClass $instance
     * @return string html text, usually a form in a text box
     */
    public function enrol_page_hook(stdClass $instance) {
        global $OUTPUT;
        if (!$this->is_active($instance)) {
            return '';
        }

        $data = [];
        $formatter = \core\di::get(\core\formatting::class);
        if ($this->get_config('svglearnpath')) {
            $items = $this->build_course_path($instance);
            $i = 1;
            foreach ($items as $item) {
                $name = $formatter->format_string(get_course($item)->fullname, context: context_course::instance($item));
                $data[] =
                    [
                        'first' => ($i == 1),
                        'course' => ($item == $instance->courseid),
                        'title' => $name,
                        'href' => new moodle_url('/course/view.php', ['id' => $item]),
                        'seqnumber' => $i,
                    ];
                $i++;
            }
        }
        $hasdata = count($data) >= 2;
        $name = $formatter->format_string(
            get_course($instance->customint1)->fullname,
            context: context_course::instance($instance->customint1)
        );
        $rdata =
            [
                'coursetitle' => $name,
                'courseurl' => new moodle_url('/course/view.php', ['id' => $instance->customint1]),
                'hasdata' => $hasdata,
                'items' => $data,
            ];
        $str = $OUTPUT->render_from_template('enrol_coursecompleted/learnpath', $rdata);
        return $OUTPUT->box($str);
    }

    /**
     * Gets an array of the user enrolment actions
     *
     * @param \course_enrolment_manager $manager
     * @param stdClass $ue A user enrolment object
     * @return array An array of user_enrolment_actions
     */
    public function get_user_enrolment_actions(\course_enrolment_manager $manager, $ue) {
        $actions = parent::get_user_enrolment_actions($manager, $ue);
        $id = $ue->enrolmentinstance->customint1;
        if (context_course::instance($id, IGNORE_MISSING)) {
            $actions[] = new user_enrolment_action(
                new pix_icon('a/search', ''),
                get_string('pluginname', 'report_completion'),
                new moodle_url('/report/completion/index.php', ['course' => $id]),
                ['class' => 'originlink', 'rel' => $ue->id]
            );
        }
        return $actions;
    }

    /**
     * Returns edit icons for the page with list of instances
     * @param stdClass $instance
     * @return array
     */
    public function get_action_icons(stdClass $instance) {
        global $OUTPUT;
        if ($instance->enrol !== 'coursecompleted') {
            throw new coding_exception('invalid enrol instance!');
        }
        $context = context_course::instance($instance->courseid);
        $icons = [];
        if (has_capability('enrol/coursecompleted:enrolpast', $context)) {
            $managelink = new moodle_url('/enrol/coursecompleted/manage.php', ['enrolid' => $instance->id]);
            $icon = new pix_icon('t/enrolusers', get_string('enrolusers', 'enrol_manual'), 'core', ['class' => 'iconsmall']);
            $icons[] = $OUTPUT->action_icon($managelink, $icon);
        }
        return array_merge(parent::get_action_icons($instance), $icons);
    }

    /**
     * Restore instance and map settings.
     *
     * @param \restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $course
     * @param int $oldid
     */
    public function restore_instance(\restore_enrolments_structure_step $step, stdClass $data, $course, $oldid) {
        global $DB;
        if ($step->get_task()->get_target() == backup::TARGET_NEW_COURSE) {
            $merge = false;
        } else {
            $merge = [
                'courseid' => $course->id,
                'enrol' => 'coursecompleted',
                'roleid' => $data->roleid,
                'customint1' => $data->customint1,
            ];
        }
        if ($merge && $instances = $DB->get_records('enrol', $merge, 'id')) {
            $instance = reset($instances);
            $instanceid = $instance->id;
        } else {
            $instanceid = $this->add_instance($course, (array)$data);
        }
        $step->set_mapping('enrol', $oldid, $instanceid);
    }

    /**
     * Enrol user into course via enrol instance.
     *
     * @param stdClass $instance
     * @param int $userid
     * @param int $roleid optional role id
     * @param int $timestart 0 means unknown
     * @param int $timeend 0 means forever
     * @param int $status default to ENROL_USER_ACTIVE for new enrolments, no change by default in updates
     * @param bool $recovergrades restore grade history
     * @return void
     */
    public function enrol_user(
        stdClass $instance,
        $userid,
        $roleid = null,
        $timestart = 0,
        $timeend = 0,
        $status = null,
        $recovergrades = null
    ) {
        global $DB;
        if ($this->is_active($instance)) {
            // We ignore the role, timestart, timeend and status parameters and fall back on the instance settings.
            $roleid = $instance->roleid ?? $this->get_config('roleid');
            if (
                $DB->record_exists('role', ['id' => $roleid]) &&
                context_course::instance($instance->customint1, IGNORE_MISSING) &&
                context_course::instance($instance->courseid, IGNORE_MISSING)
            ) {
                $timestart = time();
                $timeend = 0;
                if (isset($instance->customint4) && $instance->customint4 > 0) {
                    $timestart = $instance->customint4;
                }
                if (isset($instance->enrolperiod) && $instance->enrolperiod > 0) {
                    $timeend = $timestart + $instance->enrolperiod;
                }
                parent::enrol_user($instance, $userid, $roleid, $timestart, $timeend, $status, $recovergrades);
            } else {
                debugging('Role does not exist', DEBUG_DEVELOPER);
            }
        }
    }

    /**
     * Restore user enrolment.
     *
     * @param \restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $instance
     * @param int $userid
     * @param int $oldstatus
     */
    public function restore_user_enrolment(\restore_enrolments_structure_step $step, $data, $instance, $userid, $oldstatus) {
        if ($step->get_task()->get_target() == backup::TARGET_NEW_COURSE) {
            $this->enrol_user($instance, $userid);
        }
    }

    /**
     * Is it possible to add enrol instance via standard UI?
     *
     * @param int $courseid id of the course to add the instance to
     * @return boolean
     */
    public function can_add_instance($courseid) {
        return has_capability('enrol/coursecompleted:manage', context_course::instance($courseid));
    }

    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param object $instance
     * @return bool
     */
    public function can_delete_instance($instance) {
        return has_capability('enrol/coursecompleted:manage', context_course::instance($instance->courseid));
    }

    /**
     * Is it possible to hide/show enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance): bool {
        return has_capability('enrol/coursecompleted:manage', context_course::instance($instance->courseid));
    }

    /**
     * Does this plugin allow manual unenrolment of all users?
     *
     * @param stdClass $instance course enrol instance
     * @return bool - true means user with 'enrol/xxx:unenrol' may unenrol others freely
     */
    public function allow_unenrol(stdClass $instance): bool {
        return has_capability('enrol/coursecompleted:manage', context_course::instance($instance->courseid));
    }

    /**
     * Does this plugin allow manual changes in user_enrolments table?
     *
     * @param stdClass $instance course enrol instance
     * @return bool - true means it is possible to change enrol period and status in user_enrolments table
     */
    public function allow_manage(stdClass $instance): bool {
        return has_capability('enrol/coursecompleted:manage', context_course::instance($instance->courseid));
    }

    /**
     * Does this plugin shows enrol me?
     *
     * @param stdClass $instance course enrol instance
     * @return bool - true means it is possible to enrol.
     */
    public function show_enrolme_link(stdClass $instance): bool {
        return ($instance->status == ENROL_INSTANCE_ENABLED);
    }

    /**
     * Execute synchronisation.
     * @param progress_trace $trace
     * @return int exit code, 0 means ok
     */
    public function sync(progress_trace $trace): int {
        $this->process_expirations($trace);
        return 0;
    }

    /**
     * We are a good plugin and don't invent our own UI/validation code path.
     *
     * @return bool
     */
    public function use_standard_editing_ui(): bool {
        return true;
    }

    /**
     * Add elements to the edit instance form.
     *
     * @param stdClass $instance
     * @param \MoodleQuickForm $mform
     * @param context $context
     * @return bool
     */
    public function edit_instance_form($instance, \MoodleQuickForm $mform, $context) {
        $options = [ENROL_INSTANCE_ENABLED => get_string('yes'), ENROL_INSTANCE_DISABLED => get_string('no')];
        $mform->addElement('select', 'status', get_string('enabled', 'admin'), $options);
        $mform->setDefault('status', $this->get_config('status'));

        $role = $this->get_config('roleid');
        $start = time();
        if ($instance) {
            if (isset($instance->roleid)) {
                $role = $instance->roleid;
            }
            if (isset($instance->customint1)) {
                $start = get_course($instance->customint1)->startdate;
            }
        }
        $roles = get_default_enrol_roles($context, $role);
        $mform->addElement('select', 'roleid', get_string('assignrole', 'enrol_fee'), $roles);
        $mform->setDefault('roleid', $this->get_config('roleid'));

        $arr = ['optional' => true, 'defaulttime' => $start];
        $mform->addElement('date_time_selector', 'customint4', get_string('enroldate', 'enrol_coursecompleted'), $arr);
        $mform->addHelpButton('customint4', 'enroldate', 'enrol_coursecompleted');

        $arr = ['optional' => true, 'defaultunit' => 86400];
        $mform->addElement('duration', 'enrolperiod', get_string('enrolperiod', 'enrol_coursecompleted'), $arr);
        $mform->setDefault('enrolperiod', $this->get_config('enrolperiod'));
        $mform->addHelpButton('enrolperiod', 'enrolperiod', 'enrol_coursecompleted');

        $conditions = ['onlywithcompletion' => true, 'multiple' => false, 'includefrontpage' => false];
        $mform->addElement('course', 'customint1', get_string('course'), $conditions);
        $mform->addRule('customint1', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('customint1', 'compcourse', 'enrol_coursecompleted');

        $mform->addElement('advcheckbox', 'customint3', get_string('groups'), get_string('group', 'enrol_coursecompleted'));
        $mform->addHelpButton('customint3', 'group', 'enrol_coursecompleted');
        $mform->setDefault('customint3', $this->get_config('keepgroup'));

        $options = self::email_options();
        $mform->addElement('select', 'customint2', get_string('categoryemail', 'admin'), $options);

        $mform->addHelpButton('customint2', 'welcome', 'enrol_coursecompleted');
        $mform->setDefault('customint2', $this->get_config('welcome'));

        $arr = ['cols' => '60', 'rows' => '8'];
        $mform->addElement('textarea', 'customtext1', get_string('customwelcome', 'enrol_coursecompleted'), $arr);
        $mform->addHelpButton('customtext1', 'customwelcome', 'enrol_coursecompleted');
        $mform->disabledIf('customtext1', 'customint2', 'notchecked');

        $arr = ['optional' => true, 'defaulttime' => $start];
        $mform->addElement('date_time_selector', 'enrolstartdate', get_string('enrolstartdate', 'enrol_coursecompleted'), $arr);
        $mform->addHelpButton('enrolstartdate', 'enrolstartdate', 'enrol_coursecompleted');

        $arr['defaulttime'] = $start + get_config('moodlecourse', 'courseduration');
        $mform->addElement('date_time_selector', 'enrolenddate', get_string('enrolenddate', 'enrol_coursecompleted'), $arr);
        $mform->addHelpButton('enrolenddate', 'enrolenddate', 'enrol_coursecompleted');
    }

    /**
     * Get email options.
     * @return array of options
     */
    public static function email_options(): array {
        $options = enrol_send_welcome_email_options();
        unset($options[ENROL_SEND_EMAIL_FROM_KEY_HOLDER]);
        return $options;
    }

    /**
     * Add new instance of enrol plugin.
     * @param object $course
     * @param array|null $fields
     * @return int id of new instance, null if can not be created
     */
    public function add_instance($course, ?array $fields = null): int {
        if ($fields) {
            if (!isset($fields['customint2'])) {
                $fields['customint2'] = $this->get_config('welcome', 1);
            }
            if (!isset($fields['customint3'])) {
                $fields['customint3'] = $this->get_config('keepgroup', 1);
            }
        }
        return parent::add_instance($course, $fields);
    }

    /**
     * Update instance of enrol plugin.
     *
     * @param stdClass $instance
     * @param stdClass $data modified instance fields
     * @return boolean
     */
    public function update_instance($instance, $data) {
        $update = parent::update_instance($instance, $data);
        $hook = new \core_enrol\hook\after_enrol_instance_status_updated(enrolinstance: $instance, newstatus: $data->status);
        \core\di::get(\core\hook\manager::class)->dispatch($hook);
        return $update;
    }

    /**
     * Perform custom validation of the data used to edit the instance.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @param object $instance The instance loaded from the DB
     * @param context $context The context of the instance we are editing
     * @return array of "element_name"=>"error_description" if there are errors,
     */
    public function edit_instance_validation($data, $files, $instance, $context): array {
        $errors = [];
        if (!empty($data['enrolenddate'])) {
            // Minimum duration of a course is one hour.
            if ($data['enrolenddate'] <= $data['enrolstartdate'] + HOURSECS) {
                $errors['enrolenddate'] = get_string('enrolenddaterror', 'enrol_fee');
            }
        }

        if (
            empty($data['customint1']) ||
            !context_course::instance($data['customint1'], IGNORE_MISSING)
        ) {
            $errors['customint1'] = get_string('error_nonexistingcourse', 'tool_generator');
        }
        return $errors;
    }

    /**
     * Build (possible) coursepath
     *
     * @param stdClass $instance
     * @return array $items
     */
    private function build_course_path(stdClass $instance): array {
        $parents = $this->search_parents($instance->customint1);
        $children = $this->search_children($instance->courseid);
        return array_unique(array_merge($parents, $children));
    }

    /**
     * Search parents
     *
     * @param int $id
     * @param int $level
     * @return array items
     */
    private function search_parents($id, $level = 1): array {
        global $DB;
        $arr = [$id];
        if ($level < 5) {
            $level++;
            $params = ['enrol' => 'coursecompleted', 'courseid' => $id];
            if ($parent = $DB->get_field('enrol', 'customint1', $params, IGNORE_MULTIPLE)) {
                $arr = array_merge($this->search_parents($parent, $level), $arr);
            }
        }
        return $arr;
    }

    /**
     * Search children
     *
     * @param int $id
     * @param int $level
     * @return array items
     */
    private function search_children($id, $level = 1): array {
        global $DB;
        $arr = [$id];
        if ($level < 5) {
            $level++;
            $params = ['enrol' => 'coursecompleted', 'customint1' => $id];
            if ($child = $DB->get_field('enrol', 'courseid', $params, IGNORE_MULTIPLE)) {
                $arr = array_merge($arr, $this->search_children($child, $level));
            }
        }
        return $arr;
    }

    /**
     * Is this instance active?
     *
     * @param stdClass $instance
     * @return bool
     */
    private function is_active($instance): bool {
        $time = time();
        $start = is_null($instance->enrolstartdate) ? 0 : $instance->enrolstartdate;
        if ($start > $time) {
            return false;
        }
        $end = is_null($instance->enrolenddate) ? 0 : $instance->enrolenddate;
        if ($end != 0 && $end < $time) {
            // Past enrolment.
            return false;
        }
        return true;
    }

    /**
     * Returns true if the plugin has one or more bulk operations that can be performed on
     * user enrolments.
     *
     * @param \course_enrolment_manager $manager
     * @return bool
     */
    public function has_bulk_operations(\course_enrolment_manager $manager): bool {
        $instances = $manager->get_enrolment_instances();
        $return = false;
        foreach ($instances as $instance) {
            if ($instance->enrol === 'coursecompleted') {
                $return = true;
            }
        }
        return $return;
    }

    /**
     * The enrol plugin has bulk operations that can be performed.
     * @param \course_enrolment_manager $manager
     * @return array
     */
    public function get_bulk_operations(\course_enrolment_manager $manager): array {
        $context = $manager->get_context();
        $bulkoperations = [];
        if ($this->has_bulk_operations($manager)) {
            if (has_capability("enrol/coursecompleted:manage", $context)) {
                $bulkoperations['editselectedusers'] = new \enrol_coursecompleted\bulkedit($manager, $this);
            }
            if (has_capability("enrol/coursecompleted:unenrol", $context)) {
                $bulkoperations['deleteselectedusers'] = new \enrol_coursecompleted\bulkdelete($manager, $this);
            }
        }
        return $bulkoperations;
    }

    /**
     * Get all candidates for an enrolment.
     * @param int $courseid
     * @return array
     */
    public static function get_candidates(int $courseid): array {
        global $DB;
        $condition = 'course = ? AND timecompleted > 0';
        $return = $DB->get_fieldset_select('course_completions', 'userid', $condition, [$courseid]);
        return $return ?? [];
    }
}
