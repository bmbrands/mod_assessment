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

class backup_assessment_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {
        global $DB;
        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $assessment = new backup_nested_element('assessment', array('id'), array(
            'course', 'name', 'intro', 'introformat',
            'assessed', 'htmlfeedback',
            'numawards', 'timemodified'));

        $awards = new backup_nested_element('awards');

        $award = new backup_nested_element('award', array('id'), array(
            'course', 'assessment', 'awardnum', 'awardimage', 'awardgrade', 'awardname', 'timemodified'));

        $feedbacks = new backup_nested_element('feedbacks');

        $feedback = new backup_nested_element('feedback', array('id'), array(
            'course', 'assessment', 'userid', 'senderid', 'feedback', 'feedbackformat', 'timemodified'));

        $grades = new backup_nested_element('grades');

        $grade = new backup_nested_element('grade', array('id'), array(
            'course', 'assessment', 'userid', 'grade', 'timemodified'));

        // Build the tree.
        $assessment->add_child($awards);
        $awards->add_child($award);

        $assessment->add_child($feedbacks);
        $feedbacks->add_child($feedback);

        $assessment->add_child($grades);
        $grades->add_child($grade);

        $assessment->set_source_table('assessment', array('id' => backup::VAR_ACTIVITYID));

        $award->set_source_sql('SELECT * FROM {assessment_awards} WHERE assessment = ?', array(backup::VAR_PARENTID));

        if ($userinfo) {

            $feedback->set_source_sql('SELECT * FROM {assessment_feedback} WHERE assessment = ?', array(backup::VAR_PARENTID));

            $grade->set_source_sql('SELECT * FROM {assessment_grades} WHERE assessment = ?', array(backup::VAR_PARENTID));
        }

        // Define id annotations.
        $feedback->annotate_ids('user', 'userid');
        $feedback->annotate_ids('sender', 'senderid');

        $currentawards = $DB->get_records('assessment_awards', array('assessment' => backup::VAR_PARENTID));

        // $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        $award->annotate_files('mod_assessment', 'award', 'awardnum', $this->get_setting_value(backup::VAR_CONTEXTID));

        $grade->annotate_ids('user', 'userid');

        // Define file annotations.
        // Return the root element (assessment), wrapped into standard activity structure.
        return $this->prepare_activity_structure($assessment);

    }
}