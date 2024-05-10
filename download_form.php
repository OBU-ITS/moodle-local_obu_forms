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
 * OBU Forms - Input form for data download
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2019, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once("{$CFG->libdir}/formslib.php");

class download_form extends moodleform {

    function definition() {
        $mform =& $this->_form;
		
		$mform->addElement('text', 'formref', get_string('form', 'local_obu_forms'), 'size="10" maxlength="10"');
		$mform->setType('formref', PARAM_RAW);
		$mform->addRule('formref', null, 'required', null, 'server');
		$mform->addElement('date_selector', 'date_from', get_string('date_from', 'local_obu_forms'));
		$mform->addElement('date_selector', 'date_to', get_string('date_to', 'local_obu_forms'));

        $this->add_action_buttons(true, get_string('save', 'local_obu_forms'));
    }
	
	function validation($data, $files) {
		$errors = parent::validation($data, $files); // Ensure we don't miss errors from any higher-level validation
		
		// Do our own validation and add errors to array
		foreach ($data as $key => $value) {
			if ($key == 'formref') {
				$form = local_obu_forms_read_form_settings_by_ref($value);
				if (($form === false) || !local_obu_forms_is_manager($form)) {
					$errors['formref'] = get_string('invalid_data', 'local_obu_forms');
				}
			} else if (($key == 'date_from') && ($value > strtotime('today midnight'))) {
				$errors['date_from'] = get_string('invalid_date', 'local_obu_forms');
			} else if (($key == 'date_to') && ($value < $data['date_from'])) {
				$errors['date_to'] = get_string('invalid_date', 'local_obu_forms');
			}
		}
		
		return $errors;
	}
}