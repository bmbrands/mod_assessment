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

require_once($CFG->libdir . '/pdflib.php');

/**
 * Wrapper class that extends Moodle wrapper of TCPDF (lib/tcpdf/tcpdf.php).
 */
class assessment_pdf extends pdf {
    /**
     * Custom HTML for PDF header.
     * @var string $customheaderhtml
     */
    private $customheaderhtml = '';

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct('P', 'mm', 'A4', true, 'UTF-8');
    }

    /**
     * Set custom header HTML.
     * 
     * @param string $html
     */
    public function set_customheaderhtml($html) {
        $this->customheaderhtml = $html;
    }

    /**
     * Write header.
     */
    public function Header() {
        $this->SetFont('times', 'N', 10);
        $this->writeHTML($this->customheaderhtml, '', true, false, true, false, '');
    }

    /**
     * Write footer.
     */
    public function Footer() {
        // Position at 15 mm from bottom.
        $this->SetY(-15);
        // Set font.
        $this->SetFont('times', 'N', 8);
        // Page number.
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}