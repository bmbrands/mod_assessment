<?php
// This file is part of the Assessment plugin for Moodle
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
 * @package    mod_assessment
 * @copyright  2016 CIE
 * @author     Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/group/lib.php');

class assessment {
    // Moodle module variables.
    public $cm;
    private $course;
    private $context;
    private $userid;
    private $config;
    private $groupid;
    private $grades;
    private $feedback;

    public $singleuser;
    public $assessment;
    public $awards;
    public $messages;
    public $pages;
    public $page;

    /**
     * @param int|string $cmid optional
     * @param object $course optional
     */
    public function __construct($cm, $course, $group) {
        global $COURSE, $DB, $CFG, $USER;

        $this->userid = $USER->id;

        $this->cm = $cm;

        if ($CFG->version < 2011120100) {
            $this->context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        } else {
            $this->context = context_module::instance($this->cm->id);
        }

        if ($course) {
            $this->course = $course;
        } else if ($this->cm->course == $COURSE->id) {
            $this->course = $COURSE;
        } else if (! $this->course = $DB->get_record('course', array('id' => $this->cm->course) )) {
            print_error('Course is misconfigured');
        }

        if (! $this->assessment = $DB->get_record('assessment', array('id' => $this->cm->instance) )) {
            print_error('assessment ID was incorrect');
        }

        if (!empty($group)) {

            if (groups_is_member($group, $USER->id)) {
                $this->groupid = $group;
            }

            if (has_capability('moodle/site:accessallgroups', $this->context)) {
                $this->groupid = $group;
            }
        } else {
            if (groups_get_activity_groupmode($this->cm) > 0) {
                $this->groupid = groups_get_activity_group($this->cm);
            } else {
                $this->groupid = 0;
            }
        }

        $this->get_all_awards();
        $this->get_award_images();
        $this->get_all_grades();
        $this->get_all_feedback();
        $this->messages = array();
    }

    public function student_list($search) {
        if ($this->groupid == 0) {
            $users = get_users_by_capability($this->context, 'mod/assessment:receivegrade');
            foreach ($users as $user) {
                if (!$this->searchmatch($user, $search)) {
                    continue;
                }
                $user = $this->get_user_awards($user);
                $studentlist[$user->lastname] = $user;
            }

        } else {
            $studentroles = get_roles_with_capability('mod/assessment:receivegrade');
            if ($groupmembers = groups_get_members_by_role($this->groupid, $this->course->id)) {
                foreach ($studentroles as $sr) {
                    $users = $groupmembers[$sr->id]->users;
                    foreach ($users as $user) {
                        if (!$this->searchmatch($user, $search)) {
                            continue;
                        }
                        unset($user->roles);
                        $user = $this->get_user_awards($user);
                        $studentlist[$user->lastname] = $user;
                    }
                }
            }
        }
        if (!empty($studentlist)) {
            ksort($studentlist);
            $students = $this->pagination($studentlist);
            return $students;
        }
    }

    private function searchmatch($user, $search) {
        if ($search == '') {
            return true;
        }
        if (preg_match("/$search/i", $user->firstname)) {
            return true;
        }
        if (preg_match("/$search/i", $user->lastname)) {
            return true;
        }
    }

    private function pagination($studentlist) {
        $stcount = count($studentlist);
        $page = optional_param('page', 0, PARAM_INT);
        $this->pages = new Object;
        $perpage = 20;
        $numpages = ceil($stcount / $perpage);
        $this->pages->numpages = $numpages;
        $pagenums = array();
        for ($i = 0; $i < $numpages; $i++) {
            $pagenums[$i] = $i;
            if ($i == $page) {
                $this->pages->current = $i;
                if ($i == $numpages) {
                    $this->pages->next = $i;
                } else {
                    $this->pages->next = $i + 1;
                }
                if ($i == 0) {
                    $this->pages->prev = $i;
                } else {
                    $this->pages->prev = $i - 1;
                }
            }
        }

        $this->pages->pagenums = $pagenums;
        $start = $page * $perpage;
        $students = array_slice($studentlist, $start, $perpage);
        return $students;
    }

    private function get_user_awards($user) {
        global $USER, $OUTPUT, $CFG, $DB;

        $teacher = $DB->get_record('user', array('id' => $USER->id));
        if (isset($this->feedback[$user->id])) {
            $user->feedback = $this->feedback[$user->id];
        } else {
            $user->feedback = new stdClass();
            $user->feedback->feedback = '&nbsp;';
            $user->feedback->sender = $teacher;
            $user->feedback->sender->picture = $OUTPUT->user_picture($teacher);
        }

        $user->singleuser = false;

        if (isset($this->grades[$user->id])) {
            $user->grade = $this->grades[$user->id]->grade;
        } else {
            $user->grade = '';
        }

        $user->awards = $this->awards;
        foreach ($this->awards as $grade => $award) {
            $useraward = clone $award;
            if ($user->grade == $grade) {
                $useraward->active = 'active';
                $user->awards[$grade] = $useraward;
            } else {
                $user->awards[$grade] = $useraward;
            }
        }

        $user->assessmentid = $this->assessment->id;
        return $user;
    }

    private function get_all_feedback() {
        global $DB, $USER, $OUTPUT;
        $userfeedback = array();
        $feedback = $DB->get_records('assessment_feedback', array('assessment' => $this->assessment->id));
        $senders = array();
        foreach ($feedback as $fb) {
            if (!in_array($fb->senderid, $senders)) {
                if ($sender = $DB->get_record('user', array('id' => $fb->senderid))) {
                    $sender->picture = $OUTPUT->user_picture($sender);
                    $senders[$sender->id] = $sender;
                    $fb->sender = $senders[$fb->senderid];
                } else {
                    continue;
                }
            } else {
                $fb->sender = $senders[$fb->senderid];
            }
            $fb->feedback = file_rewrite_pluginfile_urls($fb->feedback,
                'pluginfile.php', $this->context->id, 'mod_assessment', 'feedback', $fb->id);
            $userfeedback[$fb->userid] = $fb;
        }
        $this->feedback = $userfeedback;
    }

    private function get_all_awards() {
        global $DB;
        $awardgrades = array();
        $awards = $DB->get_records('assessment_awards', array('assessment' => $this->assessment->id));
        foreach ($awards as $award) {
            $award->active = 'inactive';
            $awardgrades[$award->awardgrade] = $award;
        }
        $this->awards = $awardgrades;
    }

    private function get_all_grades() {
        global $DB;
        $usergrades = array();
        $grades = $DB->get_records('assessment_grades', array('assessment' => $this->assessment->id));
        foreach ($grades as $grade) {
            $usergrades[$grade->userid] = $grade;
        }
        $this->grades = $usergrades;
    }

    private function get_award_images() {
        global $DB, $CFG;

        $fs = get_file_storage();

        foreach ($this->awards as $award) {
            $files = $fs->get_area_files($this->context->id, 'mod_assessment', 'award', $award->id);
            foreach ($files as $file) {
                $filename = $file->get_filename();
                if ($filename == '.') {
                    continue;
                }
                $url = moodle_url::make_pluginfile_url($this->context->id, 'mod_assessment', 'award',
                    $file->get_itemid(), $file->get_filepath(), $filename);
                $award->awardimage = html_writer::empty_tag('img', array('src' => $url, 'class' => 'awardimage'));
            }
        }
    }

    public function set_user($user) {
        if (has_capability('mod/assessment:receivegrade', $this->context, $user)) {
            $this->singleuser = $this->get_user_awards($user);
            $this->singleuser->singleuser = true;
        }
    }

    public function set_form($feedbackform, $page) {
        global $DB;
        $this->feedbackform = $feedbackform;
        $definitionoptions = array('trusttext' => true, 'subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 99,
        'context' => $this->context);
        if ($feedbackform->is_cancelled()) {
            $redirecturl = new moodle_url('/mod/assessment/view.php', array('id' => $this->cm->id, 'page' => $page),
                'feedback' . $this->singleuser->id);
            redirect($redirecturl);
        }

        if ($formdata = $feedbackform->get_data()) {
            // Process data.
            $this->save_feedback($formdata);
        }

        if ($entry = $DB->get_record('assessment_feedback', array('assessment' => $this->assessment->id,
            'userid' => $this->singleuser->id))) {
            $entry = file_prepare_standard_editor($entry, 'feedback', $definitionoptions, $this->context,
            'mod_assessment', 'feedback', $entry->id);
        } else {
            $entry = new stdClass();
        }

        $feedbackform->set_data($entry);
    }

    public function save_feedback($formdata) {
        global $DB, $CFG, $USER;

        $definitionoptions = array('trusttext' => true, 'subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 99);

        $this->page = $formdata->page;

        if ($storedfeedback = $DB->get_record('assessment_feedback',
            array('course' => $this->course->id, 'assessment' => $this->assessment->id, 'userid' => $this->singleuser->id))) {
            $storedfeedback->feedback = '';
            $storedfeedback->timemodified = time();

            $formdata = file_postupdate_standard_editor($formdata, 'feedback', $definitionoptions,
                $this->context, 'mod_assessment', 'feedback', $storedfeedback->id);

            $storedfeedback->feedbackformat = 1;
            $storedfeedback->feedback = $formdata->feedback;

            $DB->update_record('assessment_feedback', $storedfeedback);
        } else {
            $feedback = new stdClass();
            $feedback->course = $this->course->id;
            $feedback->assessment = $this->assessment->id;
            $feedback->userid = $this->singleuser->id;
            $feedback->senderid = $USER->id;
            $feedback->feedback = '';
            $feedback->feedbackformat = 1;
            $feedback->timemodified = time();

            if ($feedback->id = $DB->insert_record('assessment_feedback', $feedback)) {
                $formdata = file_postupdate_standard_editor($formdata, 'feedback', $definitionoptions,
                    $this->context, 'mod_assessment', 'feedback', $feedback->id);
                $feedback->feedback = $formdata->feedback;
                $DB->update_record('assessment_feedback', $feedback);
            }
        }

        $redirecturl = new moodle_url('/mod/assessment/view.php',
            array('id' => $this->cm->id, 'page' => $this->page),  'feedback' . $this->singleuser->id);
        redirect($redirecturl);
    }
}