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

require_once("../../config.php");
require_once("lib.php");
require_once("locallib.php");
require_once("feedback_form.php");

$userid = optional_param('userid', '', PARAM_INT);
$assessmentid = optional_param('assessmentid', '', PARAM_INT);
$group = optional_param('group', 0, PARAM_INT);
$edit = optional_param('edit', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);

if (! $cm = get_coursemodule_from_instance('assessment', $assessmentid)) {
    print_error('invalidcoursemodule');
}

if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
    print_error('coursemisconf');
}

if (! $user = $DB->get_record("user", array("id" => $userid))) {
    print_error('missinguser');
}

require_course_login($course, false, $cm);

$strassessment = get_string('modulename', 'assessment');

$context = context_module::instance($cm->id);

$canassess = false;
$canview = false;

if (has_capability('mod/assessment:assess', $context)) {
    $canassess = true;
} else if (has_capability('mod/assessment:receivegrade', $context)) {
    $canview = true;
}

if (!has_capability('mod/assessment:receivegrade', $context, $user)) {
    print_error('usercannotbegraded');
}

$formoptions = array(
    'userid' => $userid,
    'assessmentid' => $assessmentid,
    'cmid' => $cm->id,
    'page' => $page);

$feedbackform = new mod_assessment_feedback_form(null, $formoptions);

$assessment = new assessment($cm, $course, $group);
$assessment->set_user($user);
$assessment->set_form($feedbackform, $page);

$renderer = $PAGE->get_renderer('mod_assessment');
$renderer->set_assessment($assessment);

$PAGE->set_url('/mod/assessment/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($strassessment));
$PAGE->set_heading(format_string($assessment->assessment->name));
$PAGE->set_context($context);
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('assessment', 'mod_assessment');

echo $OUTPUT->header();

echo $renderer->user_view($canassess);

if ($assessment->assessment->htmlfeedback) {
    if ($edit) {
        $feedbackform->display();
    } else {
        if ($canassess) {
            echo $renderer->show_feedback($canassess);
            echo $renderer->return_to_userlisting($page);
        }
    }
}

echo $OUTPUT->footer();