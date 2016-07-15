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

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form
 */
class mod_assessment_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $DB, $COURSE, $CFG;

        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('assessmentname', 'mod_assessment'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->add_intro_editor();

        $mform->addElement('selectyesno', 'htmlfeedback', get_string('htmlfeedback', 'mod_assessment'));
        $mform->addHelpButton('htmlfeedback', 'htmlfeedback', 'mod_assessment');
        for ($n = 1; $n <= 10; $n++) {
            $numoptions[$n] = $n;
        }

        $mform->addElement('select', 'numawards', get_string('numawards', 'mod_assessment'), $numoptions);
        if (!$this->_instance) {
            $repeatno = 3;
            $mform->setDefault('numawards', $repeatno);
        } else {
            $repeatno = $this->current->numawards;
        }

        for ($i = 1; $i <= $repeatno; $i++) {
            $mform->addElement('text', 'awardname' . $i, get_string('awardname', 'mod_assessment', $i));
            $mform->setType('awardname' . $i, PARAM_RAW);
            $mform->addElement('text', 'awardgrade' . $i, get_string('awardgrade', 'mod_assessment', $i));
            $mform->setType('awardgrade' . $i, PARAM_INT);
            $mform->addElement('filemanager', 'award'.$i.'_filemanager', get_string('awardimage', 'mod_assessment', $i), null,
                    array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1,
                          'accepted_types' => array('.png', '.jpg', '.gif')));
        }

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    public function data_preprocessing(&$defaultvalues) {
        global $DB, $CFG;

        $attachmentoptions = array('subdirs' => false, 'maxfiles' => 1, 'maxbytes' => 0);

        $images = array();
        if (!empty($this->_instance)) {

            $context = context_module::instance($defaultvalues['coursemodule']);
            $count = 1;
            if ($awards = $DB->get_records('assessment_awards', array('assessment' => $this->_instance))) {
                foreach ($awards as $award) {
                    $awardfileman = 'award' . $count . '_filemanager';
                    $defaultvalues['awardname'.$count] = $award->awardname;
                    $defaultvalues['awardgrade'.$count] = $award->awardgrade;
                    $award = file_prepare_standard_filemanager($award, 'award'.$count, $attachmentoptions, $context,
                        'mod_assessment', 'award', $award->id);
                    $defaultvalues[$awardfileman] = $award->$awardfileman;
                    $count++;
                }
            }
        }
    }
    public function validation($data, $files) {
        global $CFG, $USER;
        $errors = parent::validation($data, $files);
        foreach ($data as $field => $value) {
            if (is_array($value)) {
                continue;
            }
            if (preg_match('/awardgrade/', $field)) {
                if (!is_int($value)) {
                    $errors[$field] = get_string('onlynumericgrades', 'mod_assessment');
                }
            }
        }
        return $errors;
    }
}
