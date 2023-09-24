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
 * Coursecompleted enrolment plugin.
 *
 * @package   enrol_coursecompleted
 * @copyright 2017 eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Coursecompleted enrolment plugin.
 *
 * @package   enrol_coursecompleted
 * @copyright 2017 eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_coursecompleted_plugin extends enrol_plugin {

    /** @var bool singleinstance. */
    private $singleinstance = false;

    /**
     * Returns localised name of enrol instance
     *
     * @param object $instance (null is accepted too)
     * @return string
     */
    public function get_instance_name($instance) {
        global $DB;
        if ($short = $DB->get_field('course', 'shortname', ['id' => $instance->customint1])) {
            $coursename = format_string($short, true, ['context' => context_course::instance($instance->customint1)]);
            return get_string('aftercourse', 'enrol_coursecompleted', $coursename);
        }
        return get_string('coursedeleted', '', $instance->customint1);
    }

    /**
     * Returns optional enrolment information icons.
     *
     * @param array $instances all enrol instances of this type in one course
     * @return array of pix_icon
     */
    public function get_info_icons(array $instances) {
        global $DB;
        $arr = [];
        foreach ($instances as $instance) {
            if ($fullname = $DB->get_field('course', 'fullname', ['id' => $instance->customint1])) {
                $context = context_course::instance($instance->customint1);
                $name = format_string($fullname, true, ['context' => $context]);
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
        return 'Enrolment by completion of course with id ' . $instance->customint1;
    }

    /**
     * Add information for people who want to enrol.
     *
     * @param stdClass $instance
     * @return string html text, usually a form in a text box
     */
    public function enrol_page_hook(stdClass $instance) {
        global $OUTPUT;
        $data = [];
        if ($this->get_config('svglearnpath')) {
            $items = $this->build_course_path($instance);
            $i = 1;
            foreach ($items as $item) {
                $course = get_course($item);
                $context = context_course::instance($item);
                $data[] =
                    [
                        'first' => ($i == 1),
                        'course' => $item == $instance->courseid,
                        'title' => format_string($course->fullname, true, ['context' => $context]),
                        'href' => new moodle_url('/course/view.php', ['id' => $item]),
                        'seqnumber' => $i,
                    ];
                $i++;
            }
        }
        $course = get_course($instance->customint1);
        $context = context_course::instance($instance->customint1);
        $rdata =
            [
                'coursetitle' => format_string($course->fullname, true, ['context' => $context]),
                'courseurl' => new moodle_url('/course/view.php', ['id' => $instance->customint1]),
                'hasdata' => count($data) > 1,
                'items' => $data,
            ];
        $str = $OUTPUT->render_from_template('enrol_coursecompleted/learnpath', $rdata);
        return $OUTPUT->box($str);
    }

    /**
     * Gets an array of the user enrolment actions
     *
     * @param course_enrolment_manager $manager
     * @param stdClass $ue A user enrolment object
     * @return array An array of user_enrolment_actions
     */
    public function get_user_enrolment_actions(course_enrolment_manager $manager, $ue) {
        global $DB;
        $actions = parent::get_user_enrolment_actions($manager, $ue);
        $id = $ue->enrolmentinstance->customint1;
        if ($DB->record_exists('course', ['id' => $id])) {
            $context = context_course::instance($id);
            if (has_capability('report/completion:view', $context)) {
                $actions[] = new user_enrolment_action(
                    new pix_icon('a/search', ''),
                    get_string('pluginname', 'report_completion'),
                    new moodle_url('/report/completion/index.php', ['course' => $id]),
                    ['class' => 'originlink', 'rel' => $ue->id]);
            }
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
        $icons = parent::get_action_icons($instance);
        $context = context_course::instance($instance->courseid);
        if (has_capability('enrol/coursecompleted:enrolpast', $context)) {
            $managelink = new moodle_url("/enrol/coursecompleted/manage.php", ['enrolid' => $instance->id]);
            $icon = new pix_icon('t/enrolusers', get_string('enrolusers', 'enrol_manual'), 'core', ['class' => 'iconsmall']);
            $icons[] = $OUTPUT->action_icon($managelink, $icon);
        }
        return $icons;
    }

    /**
     * Restore instance and map settings.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $course
     * @param int $oldid
     */
    public function restore_instance(restore_enrolments_structure_step $step, stdClass $data, $course, $oldid) {
        global $DB;
        if ($step->get_task()->get_target() == backup::TARGET_NEW_COURSE) {
            $merge = false;
        } else {
            $merge = [
                'courseid' => $course->id,
                'enrol' => 'coursecompleted',
                'roleid' => $data->roleid,
                'customint1' => $data->customint1,
                'customint2' => $data->customint2,
                'customint3' => $data->customint3,
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
     * Restore user enrolment.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $instance
     * @param int $userid
     * @param int $oldinstancestatus
     */
    public function restore_user_enrolment(restore_enrolments_structure_step $step, $data, $instance, $userid, $oldinstancestatus) {
        if ($step->get_task()->get_target() == backup::TARGET_NEW_COURSE) {
            $this->enrol_user($instance, $userid, null, $data->timestart, $data->timeend, $data->status);
        }
        mark_user_dirty($userid);
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
    public function can_hide_show_instance($instance) {
        return has_capability('enrol/coursecompleted:manage', \context_course::instance($instance->courseid));
    }

    /**
     * Does this plugin allow manual unenrolment of all users?
     *
     * @param stdClass $instance course enrol instance
     * @return bool - true means user with 'enrol/xxx:unenrol' may unenrol others freely
     */
    public function allow_unenrol(stdClass $instance) {
        return has_capability('enrol/coursecompleted:manage', \context_course::instance($instance->courseid));
    }

    /**
     * Does this plugin allow manual changes in user_enrolments table?
     *
     * @param stdClass $instance course enrol instance
     * @return bool - true means it is possible to change enrol period and status in user_enrolments table
     */
    public function allow_manage(stdClass $instance) {
        return has_capability('enrol/coursecompleted:manage', \context_course::instance($instance->courseid));
    }

    /**
     * Does this plugin shows enrol me?
     *
     * @param stdClass $instance course enrol instance
     * @return bool - true means it is possible to enrol.
     */
    public function show_enrolme_link(stdClass $instance) {
        return ($instance->status == ENROL_INSTANCE_ENABLED);
    }

    /**
     * Execute synchronisation.
     * @param progress_trace $trace
     * @return int exit code, 0 means ok
     */
    public function sync(progress_trace $trace) {
        $this->process_expirations($trace);
        return 0;
    }

    /**
     * We are a good plugin and don't invent our own UI/validation code path.
     *
     * @return bool
     */
    public function use_standard_editing_ui() {
        return true;
    }

    /**
     * Add elements to the edit instance form.
     *
     * @param stdClass $instance
     * @param MoodleQuickForm $mform
     * @param context $context
     * @return bool
     */
    public function edit_instance_form($instance, MoodleQuickForm $mform, $context) {
        $options = [ENROL_INSTANCE_ENABLED => get_string('yes'), ENROL_INSTANCE_DISABLED => get_string('no')];
        $mform->addElement('select', 'status', get_string('enabled', 'admin'), $options);
        $mform->setDefault('status', $this->get_config('status'));

        $role = ($instance && isset($instance->roleid)) ? $instance->roleid : $this->get_config('roleid');
        $roles = get_default_enrol_roles($context, $role);
        $mform->addElement('select', 'roleid', get_string('assignrole', 'enrol_paypal'), $roles);
        $mform->setDefault('roleid', $this->get_config('roleid'));

        $arr = ['optional' => true, 'defaultunit' => 86400];
        $mform->addElement('duration', 'enrolperiod', get_string('enrolperiod', 'enrol_paypal'), $arr);
        $mform->setDefault('enrolperiod', $this->get_config('enrolperiod'));
        $mform->addHelpButton('enrolperiod', 'enrolperiod', 'enrol_paypal');

        $start = ($instance && isset($instance->customint1)) ? get_course($instance->customint1)->startdate : time();
        $arr = ['optional' => true, 'defaulttime' => $start];
        $mform->addElement('date_time_selector', 'enrolstartdate', get_string('enrolstartdate', 'enrol_paypal'), $arr);
        $mform->addHelpButton('enrolstartdate', 'enrolstartdate', 'enrol_paypal');

        $duration = intval(get_config('moodlecourse', 'courseduration')) ?? YEARSECS;
        $arr['defaulttime'] = $start + $duration;
        $mform->addElement('date_time_selector', 'enrolenddate', get_string('enrolenddate', 'enrol_paypal'), $arr);
        $mform->addHelpButton('enrolenddate', 'enrolenddate', 'enrol_paypal');

        $conditions = ['onlywithcompletion' => true, 'multiple' => false, 'includefrontpage' => false];
        $mform->addElement('course', 'customint1', get_string('course'), $conditions);
        $mform->addRule('customint1', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('customint1', 'compcourse', 'enrol_coursecompleted');

        $mform->addElement('advcheckbox', 'customint3', get_string('groups'), get_string('group', 'enrol_coursecompleted'));
        $mform->addHelpButton('customint3', 'group', 'enrol_coursecompleted');
        $mform->setDefault('customint3', $this->get_config('keepgroup'));

        $mform->addElement('advcheckbox', 'customint2', get_string('categoryemail', 'admin'),
             get_string('welcome', 'enrol_coursecompleted'));
        $mform->addHelpButton('customint2', 'welcome', 'enrol_coursecompleted');
        $mform->setDefault('customint2', $this->get_config('welcome'));

        $arr = ['cols' => '60', 'rows' => '8'];
        $mform->addElement('textarea', 'customtext1', get_string('customwelcome', 'enrol_coursecompleted'), $arr);
        $mform->addHelpButton('customtext1', 'customwelcome', 'enrol_coursecompleted');
        $mform->disabledIf('customtext1', 'customint2', 'notchecked');

    }

    /**
     * Add new instance of enrol plugin.
     * @param object $course
     * @param array $fields
     * @return int id of new instance, null if can not be created
     */
    public function add_instance($course, array $fields = null) {
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
     * Perform custom validation of the data used to edit the instance.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @param object $instance The instance loaded from the DB
     * @param context $context The context of the instance we are editing
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK.
     */
    public function edit_instance_validation($data, $files, $instance, $context) {
        global $DB;
        $errors = [];
        if ($data['status'] == ENROL_INSTANCE_ENABLED) {
            if (!empty($data['enrolenddate']) && $data['enrolenddate'] < $data['enrolstartdate']) {
                $errors['enrolenddate'] = get_string('enrolenddaterror', 'enrol_paypal');
            }
            if (empty($data['customint1']) ||
                $data['customint1'] == 1 ||
                !$DB->record_exists('course', ['id' => $data['customint1']])) {
                $errors['customint'] = get_string('error_nonexistingcourse', 'tool_generator');
            }
        }
        return $errors;
    }

    /**
     * Build (possible) coursepath
     *
     * @param stdClass $instance
     * @return array $items
     */
    public function build_course_path(stdClass $instance) {
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
    private function search_parents($id, $level = 1) {
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
    private function search_children($id, $level = 1) {
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
     * Keep user in group
     *
     * @param stdClass $enrol Enrolment record
     * @param int $userid User id
     */
    public static function keepingroup($enrol, $userid) {
        global $CFG;
        if ($enrol->customint3 > 0) {
            require_once($CFG->dirroot . '/group/lib.php');
            $groups = array_values(groups_get_user_groups($enrol->customint1, $userid));
            foreach ($groups as $group) {
                $subs = array_values($group);
                foreach ($subs as $sub) {
                    $groupnamea = groups_get_group_name($sub);
                    $groupnameb = groups_get_group_by_name($enrol->courseid, $groupnamea);
                    if ($groupnameb) {
                        groups_add_member($groupnameb, $userid);
                    }
                }
            }
        }
    }

    /**
     * Returns true if the plugin has one or more bulk operations that can be performed on
     * user enrolments.
     *
     * @param course_enrolment_manager $manager
     * @return bool
     */
    public function has_bulk_operations(course_enrolment_manager $manager) {
        if ($this->singleinstance == false) {
            $instances = array_values($manager->get_enrolment_instances(false));
            $i = 0;
            foreach ($instances as $instance) {
                if ($instance->enrol === 'coursecompleted') {
                    $i++;
                }
            }
            $this->singleinstance = (bool)($i === 1);
        }
        return $this->singleinstance;
    }

    /**
     * The enrol plugin has bulk operations that can be performed.
     * @param course_enrolment_manager $manager
     * @return array
     */
    public function get_bulk_operations(course_enrolment_manager $manager) {
        global $CFG;
        $context = $manager->get_context();
        $bulkoperations = [];
        if ($this->has_bulk_operations($manager)) {
            if (has_capability("enrol/coursecompleted:manage", $context)) {
                require_once($CFG->dirroot . '/enrol/coursecompleted/classes/enrol_coursecompleted_bulkedit.php');
                $bulkoperations['editselectedusers'] = new enrol_coursecompleted_bulkedit($manager, $this);
            }
            if (has_capability("enrol/coursecompleted:unenrol", $context)) {
                require_once($CFG->dirroot . '/enrol/coursecompleted/classes/enrol_coursecompleted_bulkdelete.php');
                $bulkoperations['deleteselectedusers'] = new enrol_coursecompleted_bulkdelete($manager, $this);
            }
        }
        return $bulkoperations;
    }

    /**
     * Enrol all users who completed in the past.
     * @param int $courseid
     */
    public static function enrol_past(int $courseid = 0) {
        global $DB;
        $params = ['enrol' => 'coursecompleted', 'status' => 0];
        if (!$courseid == 0) {
            $params['customint1'] = $courseid;
        }
        if ($enrols = $DB->get_records('enrol', $params)) {
            $plugin = \enrol_get_plugin('coursecompleted');
            $condition = 'course = ? AND timecompleted > 0';
            foreach ($enrols as $enrol) {
                if ($DB->record_exists('role', ['id' => $enrol->roleid]) &&
                    $DB->record_exists('course', ['id' => $enrol->courseid])) {
                    if ($enrol->enrolperiod > 0) {
                        $enrol->enrolenddate = max(time(), $enrol->enrolstartdate) + $enrol->enrolperiod;
                    }
                    if ($candidates = $DB->get_fieldset_select('course_completions', 'userid', $condition, [$enrol->customint1])) {
                        foreach ($candidates as $canid) {
                            $user = \core_user::get_user($canid);
                            if (!empty($user) && !$user->deleted) {
                                $plugin->enrol_user($enrol, $canid, $enrol->roleid, $enrol->enrolstartdate, $enrol->enrolenddate);
                                self::keepingroup($enrol, $canid);
                                mark_user_dirty($canid);
                            }
                        }
                    }
                }
            }
        }
    }
}
