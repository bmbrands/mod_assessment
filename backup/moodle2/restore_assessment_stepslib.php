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

class restore_assessment_activity_structure_step extends restore_activity_structure_step {


    protected $votes = array();
    protected $submitted = array();
    protected $candidates = array();
    protected $parentmap = array();

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('assessment', '/activity/assessment');
        $paths[] = new restore_path_element('assessment_awards', '/activity/assessment/awards/award');

        if ($userinfo) {
            $paths[] = new restore_path_element('assessment_feedbacks', '/activity/assessment/feedbacks/feedback');
            $paths[] = new restore_path_element('assessment_grades', '/activity/assessment/grades/grade');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_assessment($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // Insert the assessment record.
        $newitemid = $DB->insert_record('assessment', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    protected function process_assessment_awards($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;

        $data->assessment = $this->get_new_parentid('assessment');
        $data->course = $this->get_courseid();

        $newitemid = $DB->insert_record('assessment_awards', $data);

        $data->id = $newitemid;
        $this->awards[$oldid] = $data;
    }

    protected function process_assessment_feedbacks($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->assessment = $this->get_new_parentid('assessment');
        $data->course = $this->get_courseid();

        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->senderid = $this->get_mappingid('sender', $data->senderid);

        $newitemid = $DB->insert_record('assessment_feedback', $data);

        $data->id = $newitemid;
        $this->submitted[$oldid] = $data;
    }

    protected function process_assessment_grades($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->assessment = $this->get_new_parentid('assessment');
        $data->course = $this->get_courseid();
        $newitemid = $DB->insert_record('assessment_grades', $data);

        $data->id = $newitemid;

        $this->votes[$oldid] = $data;
    }

    protected function after_execute() {
        global $DB;
        foreach ($this->awards as $key => $award) {
            $this->add_related_files('mod_assessment', 'award', null, null, $award->id);
        }

        $fs = get_file_storage();
        $newcontextid = $this->task->get_contextid();

        $restored_files = $DB->get_records('files', array('component' => 'mod_assessment', 'contextid' => $newcontextid));
        foreach ($restored_files as $rf) {
            if (isset($this->awards[$rf->itemid])) {
                $award = $this->awards[$rf->itemid];
                $rf->itemid = $award->id;
                $rf->pathnamehash = $fs->get_pathname_hash($newcontextid, 'mod_assessment', 'award', $rf->itemid, '/', $rf->filename);
                $DB->update_record('files', $rf);
            }
        }
        
    }

}