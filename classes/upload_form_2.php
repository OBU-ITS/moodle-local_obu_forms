<?php

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
 * OBU Forms - Upload course custom fields (form 2)
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2019, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/lib.php');

class local_obu_forms_upload_form_2 extends moodleform {

    /**
     * The standard form definition.
     * @return void.
     */
    public function definition () {
        global $CFG;

        $mform   = $this->_form;
        $data    = $this->_customdata['data'];

        // Hidden fields.
        $mform->addElement('hidden', 'importid');
        $mform->setType('importid', PARAM_INT);

        $mform->addElement('hidden', 'previewrows');
        $mform->setType('previewrows', PARAM_INT);

        $this->add_action_buttons(true, get_string('uploadcustomfields', 'local_obu_forms'));

        $this->set_data($data);
    }

    /**
     * Add action buttons.
     *
     * @param bool $cancel whether to show cancel button, default true
     * @param string $submitlabel label for submit button, defaults to get_string('savechanges')
     * @return void
     */
    public function add_action_buttons($cancel = true, $submitlabel = null) {
        $mform =& $this->_form;
        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'showpreview', get_string('preview', 'local_obu_forms'));
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel);
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }
}
