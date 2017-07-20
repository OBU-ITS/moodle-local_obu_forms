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
 * OBU Forms - Input form for user form redirection
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once("{$CFG->libdir}/formslib.php");

class redirect_input extends moodleform {

    function definition() {
        $mform =& $this->_form;

        $data = new stdClass();
        $data->data_id = $this->_customdata['data_id'];
		$data->form_status = $this->_customdata['form_status'];
        $data->authoriser = $this->_customdata['authoriser'];
        $data->authoriser_name = $this->_customdata['authoriser_name'];
		
		// Start with the required hidden field
		$mform->addElement('hidden', 'id', $data->data_id);

		$mform->addElement('html', $data->form_status);
		
		if (!$data->authoriser) {
			$mform->addElement('text', 'authoriser', get_string('user_number', 'local_obu_forms'), 'size="8" maxlength="8"');
			$this->add_action_buttons(true, get_string('continue', 'local_obu_forms'));
		} else {
			$mform->addElement('hidden', 'authoriser', $data->authoriser);
			$mform->addElement('static', 'authoriser_number', get_string('user_number', 'local_obu_forms'), $data->authoriser);
			$mform->addElement('static', 'authoriser_name', null, $data->authoriser_name);
			$this->add_action_buttons(true, get_string('save', 'local_obu_forms'));
		}
    }
	
	function validation($data, $files) {
		$errors = parent::validation($data, $files); // Ensure we don't miss errors from any higher-level validation
		
		// Do our own validation and add errors to array
		foreach ($data as $key => $value) {
			if ($key == 'authoriser') {
				$authoriser = get_complete_user_data('username', $value);
				if ($authoriser === false) {
					$errors[$key] = get_string('user_not_found', 'local_obu_forms');
				}
			}
		}
		
		return $errors;
	}
}