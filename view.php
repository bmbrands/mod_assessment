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

require_once("../../config.php");
require_once("lib.php");
require_once("locallib.php");

$id = optional_param('id', '', PARAM_INT);
$cmid = optional_param('cmid', '', PARAM_INT);
$group = optional_param('group', 0, PARAM_INT);
$search = optional_param('query', '', PARAM_RAW);

if ($cmid) {
    $id = $cmid;
}

if (! $cm = get_coursemodule_from_id('assessment', $id)) {
    print_error('invalidcoursemodule');
}

if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
    print_error('coursemisconf');
}

require_course_login($course, false, $cm);

$assessment = new assessment($cm, $course, $group);

$strassessment = get_string('modulename', 'assessment');

$context = context_module::instance($cm->id);

$canassess = false;
$canview = false;

if (has_capability('mod/assessment:assess', $context)) {
    $canassess = true;
} else if (has_capability('mod/assessment:receivegrade', $context)) {
    $canview = true;
}

if (!$canassess) {
    $redirecturl = new moodle_url('/mod/assessment/user.php',
        array('assessmentid' => $assessment->assessment->id,
        'userid' => $USER->id));
    redirect($redirecturl);
}

$renderer = $PAGE->get_renderer('mod_assessment');
$renderer->set_assessment($assessment);

$PAGE->set_url('/mod/assessment/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($strassessment));
$PAGE->set_heading(format_string($assessment->assessment->name));
$PAGE->set_context($context);
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('assessment', 'mod_assessment');

echo $OUTPUT->header();

$groupmode = groups_get_activity_groupmode($cm);
if ($groupmode) {
    groups_get_activity_group($cm, true);
    groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/assessment/view.php?id='.$id);
}

if ($assessment->assessment->intro) {
    echo $OUTPUT->box(format_module_intro('assessment', $assessment->assessment, $cm->id), 'generalbox', 'intro');
}

$printurl = new moodle_url('/mod/assessment/print.php', array('id' => $id));
$button = new single_button($printurl, get_string('printlist', 'mod_assessment'));
echo html_writer::tag('div', $OUTPUT->render($button), array('class' => 'pull-right'));
$printurl = new moodle_url('/mod/assessment/print.php', array('id' => $id, 'allusers' => 1));
$button = new single_button($printurl, get_string('printall', 'mod_assessment'));

echo html_writer::tag('div', $OUTPUT->render($button), array('class' => 'pull-right'));
echo $renderer->user_search($search);
echo $renderer->user_listing($search);
echo $renderer->pagination();

echo $OUTPUT->footer();