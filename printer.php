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

require_once($CFG->dirroot . '/mod/assessment/pdf.php');

defined('MOODLE_INTERNAL') || die();

class assessment_printer {

    private $assessment;
    private $course;
    private $context;
    private $user;
    private $pdf;
    private $print;
    private $error;
    private $allusers;

    /**
     * Constructor.
     *
     * Make sure no renderer functions are being called in the constructor
     *
     * @param object $assessment Assessment object.
     */
    public function __construct($assessment, $course, $context, $user = null, $allusers) {
        $this->assessment = $assessment;
        $this->course = $course;
        $this->context = $context;
        $this->user = $user;
        $this->allusers = $allusers;
        
        // Check if this user is allowed to print.
        if (!$this->can_print()) {
            throw new moodle_exception('error:noaccess', 'mod_assessement');
        }
        $this->pdf = new assessment_pdf();
    }

    /**
     * Check permissions for printing.
     *
     * @return bool true if user can print.
     */
    private function can_print() {
        global $USER;
        if (has_capability('mod/assessment:assess', $this->context)) {
            return true;
        } else if (has_capability('mod/assessment:receivegrade', $this->context)) {
            $this->allusers = false;
            if ($this->user && $USER->id == $this->user->id) {
                return true;
            }
        } else {
            $this->allusers = false;
        }
        return false;
    }

    /**
     * Generate and output PDF.
     * 
     * @global \moodle_page $PAGE
     */
    public function pdf() {
        global $PAGE;

        $renderer = $PAGE->get_renderer('mod_assessment');
        $this->assessment->makepdf = true;
        $renderer->set_assessment($this->assessment);

        $headerhtml = $this->course->fullname . ' ' . get_string('pluginname', 'mod_assessment');
        $this->pdf->set_customheaderhtml($headerhtml);
        $this->pdf->SetMargins(5, 15, 10);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->SetTextColor(25, 25, 75);

        if ($this->allusers) {
            $students = $this->assessment->student_list('', false);
            if ($students) {
                foreach ($students as $student) {
                    if (empty($student->grade)) {
                        continue;
                    }
                    $contenthtml = $renderer->pdf_user($student);
                    $this->pdf->AddPage();
                    $this->pdf->writeHTML($contenthtml, '', true, false, true, false, '');
                }
            }
        } else {
            if (!$this->user) {
                $contenthtml = $renderer->pdf_listing();
            } else {
                $contenthtml = $renderer->pdf_user($this->user);
            }
            $this->pdf->AddPage();
            $this->pdf->writeHTML($contenthtml, '', true, false, true, false, '');
        }
        
        $filenamepieces = array(
            $this->assessment->assessment->id,
            get_string('pluginname', 'mod_assessment'),
        );

        $filename = str_replace(' ', '_', strtolower(trim(clean_param(implode('_', $filenamepieces), PARAM_FILE))));
        
        //echo $contenthtml;
        if ($this->pdf->Output("{$filename}.pdf")) {
            // If output OK trigger event and exit.
            exit;
        }
        
    }
}