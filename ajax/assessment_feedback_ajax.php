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
define('AJAX_SCRIPT', true);
define('NO_DEBUG_DISPLAY', true);

require_once('../../../config.php');

$assessmentid = required_param('assessmentid', PARAM_INT);
$sesskey = required_param('sesskey', PARAM_ALPHA);
$userid = required_param('userid', PARAM_INT);
$value = optional_param('value', '', PARAM_RAW);

if (! $cm = get_coursemodule_from_instance('assessment', $assessmentid)) {
    print_error('invalidcoursemodule');
}

$course = $DB->get_record('course', array('id' => $cm->course));

$PAGE->set_url('/mod/assessment/ajax/assessment_feedback_ajax.php');

require_course_login($course, true, $cm);

$context = context_module::instance($cm->id);

if (!has_capability('mod/assessment:assess', $context)) {
    return false;
}

$PAGE->set_context($context);

if (!confirm_sesskey()) {
    $error = array('error' => get_string('invalidsesskey', 'error'));
    die(json_encode($error));
}

echo $OUTPUT->header();


if (empty($value) || $value == '') {
    return false;
}

$result = array();

if ($storedfeedback = $DB->get_record('assessment_feedback',
    array('course' => $course->id, 'assessment' => $assessmentid, 'userid' => $userid))) {
    $storedfeedback->feedback = $value;
    $storedfeedback->timemodified = time();
    if ($DB->update_record('assessment_feedback', $storedfeedback)) {
        $result = array('stat' => 'success');
        echo json_encode($result);
        die();
    }
} else {
    $feedback = new stdClass();
    $feedback->course = $course->id;
    $feedback->assessment = $assessmentid;
    $feedback->userid = $userid;
    $feedback->senderid = $USER->id;
    $feedback->feedback = $value;
    $feedback->feedbackformat = 0;
    $feedback->timemodified = time();
    if ($DB->insert_record('assessment_feedback', $feedback)) {
        $result = array('stat' => 'success');
        echo json_encode($result);
        die();
    }
}

$result = array('stat' => 'failed');
echo json_encode($result);
die();