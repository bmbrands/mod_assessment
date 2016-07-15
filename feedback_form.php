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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class mod_assessment_feedback_form extends moodleform {
    protected function definition() {

        $mform = $this->_form;
        $assessmentid = $this->_customdata['assessmentid'];
        $userid = $this->_customdata['userid'];
        $cmid = $this->_customdata['cmid'];
        $page = $this->_customdata['page'];

        $mform->addElement('hidden', 'userid', $userid);
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('hidden', 'page', $page);
        $mform->setType('page', PARAM_INT);

        $mform->addElement('hidden', 'assessmentid', $assessmentid);
        $mform->setType('assessmentid', PARAM_INT);

        $mform->addElement('hidden', 'cmid', $cmid);
        $mform->setType('cmid', PARAM_INT);

        $context = context_module::instance($cmid);

        $textfieldoptions = array('trusttext' => true, 'subdirs' => false, 'maxfiles' => 55,
                          'maxbytes' => 0, 'context' => $context);

        $mform->addElement('editor', 'feedback_editor',
            get_string('feedback', 'mod_assessment'),
            null, $textfieldoptions);
        $mform->setType('feedback_editor', PARAM_RAW);

        $this->add_action_buttons(true, get_string('save', 'mod_assessment'));
    }
}
