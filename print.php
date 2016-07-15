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
require_once("printer.php");

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


// try {
    $assessment = new assessment($cm, $course, $group);
    $printer = new assessment_printer($assessment);
    $printer->pdf();
// } catch (Exception $e) {
//     $PAGE->set_url('/mod/assessment/print.php', array('id' => $cm->id));
//     $PAGE->set_title(get_string('error'));
//     $PAGE->set_heading(get_string('error'));

//     echo $OUTPUT->header();
//     echo html_writer::tag('div', get_string('printerissue', 'mod_assessment'), array('class' => 'alert alert-error'));
//     echo $OUTPUT->footer();

//     exit;
// }