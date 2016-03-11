<?php
// This file is part of the Election plugin for Moodle
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


defined('MOODLE_INTERNAL') || die();


function assessment_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_GROUPMEMBERSONLY:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the assessment into the database
 *
 * @param object $assessment An object from the form in mod_form.php
 * @param mod_assessment_mod_form $mform
 * @return int The id of the newly inserted assessment record
 */
function assessment_add_instance(stdClass $assessment, mod_assessment_mod_form $mform = null) {
    global $DB, $CFG;

    $assessment->timecreated = time();
    if ($assessment->id = $DB->insert_record('assessment', $assessment)) {
        assessment_awards($assessment);
    }
    return $assessment->id;
}

/**
 * Saves the awards for a new assessment instance
 *
 * @param object $assessment An object from the form in mod_form.php
 * @return void
 */
function assessment_awards($assessment) {
    global $DB;

    $context = context_module::instance($assessment->coursemodule);
    $attachmentoptions = array('subdirs' => false, 'maxfiles' => 1, 'maxbytes' => 0);

    $awardrecords = $DB->get_records('assessment_awards', array('assessment' => $assessment->id));
    $existingawards = array();
    foreach ($awardrecords as $ar) {
        $existingawards[$ar->awardnum] = $ar;
    }

    for ($i = 1; $i <= $assessment->numawards; $i++) {

        $awardgrade = 'awardgrade' . $i;
        $awardname = 'awardname' . $i;
        $awardimage = 'award' . $i .'_filemanager';
        if (empty($assessment->$awardgrade)) {
            continue;
        }
        $award = new stdClass;
        $award->course = $assessment->course;
        $award->assessment = $assessment->id;
        $award->awardimage = $assessment->$awardimage;
        $award->awardnum = $i;
        $award->awardgrade = $assessment->$awardgrade;
        $award->awardname = $assessment->$awardname;
        $award->timemodified = time();
        if (array_key_exists($i, $existingawards)) {
            $exaward = $existingawards[$i];
            $award->id = $exaward->id;
            $DB->update_record('assessment_awards', $award);
            file_postupdate_standard_filemanager($assessment, 'award' . $i, $attachmentoptions,
                $context, 'mod_assessment', 'award', $award->id);

        } else if ($award->id = $DB->insert_record('assessment_awards', $award)) {
            file_postupdate_standard_filemanager($assessment, 'award' . $i, $attachmentoptions,
                $context, 'mod_assessment', 'award', $award->id);
        }
    }
}

/**
 * Updates an instance of the assessment in the database
 *
 *
 * @param object $assessment An object from the form in mod_form.php
 * @param mod_assessment_mod_form $mform
 * @return boolean Success/Fail
 */
function assessment_update_instance(stdClass $assessment, mod_assessment_mod_form $mform = null) {
    global $DB;

    $assessment->timemodified = time();
    $assessment->id = $assessment->instance;

    assessment_awards($assessment);

    return $DB->update_record('assessment', $assessment);
}


/**
 * Removes an instance of the assessment from the database
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function assessment_delete_instance($id) {
    global $DB;
    $DB->delete_records('assessment_awards', array('assessment' => $id));
    $DB->delete_records('assessment_feedback', array('assessment' => $id));
    $DB->delete_records('assessment', array('id' => $id));
    return true;
}


/**
 * Assessment Cron.
 *
 * @return boolean
 **/
function assessment_cron () {
}

/**
 * Return grade for given user or all users.
 *
 * @param stdClass $assessment record of assign with an additional cmidnumber
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function assessment_get_user_grades($assessment, $userid=0) {
    global $CFG, $DB;

    $grades = array();
    if (empty($userid)) {
        if ($usergrades = $DB->get_records('assessment_grades', array('assessment' => $assessment->id))) {
            foreach ($usergrades as $ugrade) {
                $grades[$ugrade->userid] = new stdClass();
                $grades[$ugrade->userid]->id         = $ugrade->userid;
                $grades[$ugrade->userid]->userid     = $ugrade->userid;
                $grades[$ugrade->userid]->rawgrade = $ugrade->grade;
            }
        } else {
            return false;
        }

    } else {
        if (!$ugrade = $DB->get_record('assessment_grades', array('assessment' => $assessment->id, 'userid' => $userid))) {
            return false;
        }
        $grades[$userid] = new stdClass();
        $grades[$userid]->id         = $userid;
        $grades[$userid]->userid     = $userid;
        $grades[$userid]->rawgrade = $ugrade->grade;
    }
    return $grades;
}

function assessment_grade_item_update($assessment, $grades = null) {
    global $CFG;
    if (!function_exists('grade_update')) {
        require_once($CFG->libdir.'/gradelib.php');
    }

    $params = array('itemname' => $assessment->name, 'idnumber' => $assessment->cmidnumber);
    $params['gradetype'] = GRADE_TYPE_VALUE;
    $params['grademax']  = 100;
    $params['grademin']  = 0;
    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }
    return grade_update('mod/assessment', $assessment->course, 'mod', 'assessment', $assessment->id, 0, $grades, $params);
}

function assessment_delete_grade($assessment, $userid) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');
    $grade = new stdClass();
    $grade->userid   = $userid;
    $grade->rawgrade = null;
    assessment_grade_item_update($assessment, $grade);
}

function assessment_update_grades($assessment, $userid=0, $nullifnon=true) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    if ($grades = assessment_get_user_grades($assessment, $userid)) {
        assessment_grade_item_update($assessment, $grades);
    } else if ($userid and $nullifnone) {
        $grade = new stdClass();
        $grade->userid   = $userid;
        $grade->rawgrade = null;
        assessment_grade_item_update($assessment, $grade);
    } else {
        assessment_grade_item_update($assessment);
    }
}


function assessment_reset_gradebook($courseid, $type='') {
    global $CFG, $DB;

    $sql = "SELECT s.*, cm.idnumber as cmidnumber, s.course as courseid
              FROM {assessment} s, {course_modules} cm, {modules} m
             WHERE m.name='scorm' AND m.id=cm.module AND cm.instance=s.id AND s.course=?";

    if ($assessments = $DB->get_records_sql($sql, array($courseid))) {
        foreach ($assessments as $assessment) {
            assessment_grade_item_update($assessment, 'reset');
        }
    }
}

function assessment_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload,
array $options=array()) {

    $itemid = $args[0];
    $fullpath = "/$context->id/mod_assessment/$filearea/$itemid/".$args[1];
    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        send_file_not_found();
    }
    $lifetime = isset($CFG->filelifetime) ? $CFG->filelifetime : 86400;
    send_stored_file($file, $lifetime, 0, $forcedownload, $options);
}