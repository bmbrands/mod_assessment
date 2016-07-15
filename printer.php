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

    /**
     * Appraisal object.
     * @var \mod_assessement\appraisal $appraisal
     */
    private $assessment;

    /**
     * PDF class.
     * @var \mod_assessement\pdf $pdf
     */
    private $pdf;

    /**
     * What to print.
     * @var string $print
     */
    private $print;

    /**
     * Error message.
     * @var string $error
     */
    private $error;

    /**
     * Constructor.
     *
     * Make sure no renderer functions are being called in the constructor
     *
     * @param object $assessment Assessment object.
     */
    public function __construct($assessment) {
        $this->assessment = $assessment;
        
        // Check if this user is allowed to print.
        if (!$this->can_print()) {
            throw new moodle_exception('error:noaccess', 'mod_assessement');
        }

        $this->pdf = new assessment_pdf();
    }

    /**
     *  Magic getter.
     * 
     * @param string $name
     * @return mixed property
     * @throws Exception
     */
    public function __get($name) {
        if (method_exists($this, "get_{$name}")) {
            return $this->{"get_{$name}"}();
        }
        if (!isset($this->{$name})) {
            throw new Exception('Undefined property ' .$name. ' requested');
        }
        return $this->{$name};
    }

    /**
     * Check permissions for printing.
     *
     * @return bool true if user can print.
     */
    private function can_print() {
        return true;
        //return $this->appraisal->check_permission("{$this->print}:print");
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

        $headerhtml = 'Test Header';

        // Do we need a legacy renderer?
        $contenthtml = $renderer->pdf_listing();

        $this->pdf->set_customheaderhtml($headerhtml);
        $this->pdf->SetMargins(10, 30, 10);
        $this->pdf->AddPage();
        $this->pdf->SetFont('', '', 12);
        $this->pdf->SetTextColor(25, 25, 75);

        $this->pdf->writeHTML($contenthtml, '', true, false, true, false, '');
        
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