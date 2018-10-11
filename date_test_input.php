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
 * OBU Forms - Date Test input form
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2018, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once("{$CFG->libdir}/formslib.php");

class date_test_input extends moodleform {

    function definition() {
        $mform =& $this->_form;

		$mform->addElement('html', '<h2>' . get_string('date_test', 'local_obu_forms') . '</h2>');

        $data = new stdClass();
		$data->date = $this->_customdata['date'];
		$data->dates = $this->_customdata['dates'];
		
		if ($data->date != '') {
			$mform->addElement('html', '<p style="margin-left: 20em; font-weight:bold;">' . $data->date . '</p>');
			$mform->addElement('html', '<ol style="margin-left: 20em;">');
			foreach ($data->dates as $index => $date) {
				if ($index > 0) {
					$mform->addElement('html', '<li>' . $date . '</li>');
				}
			}
			$mform->addElement('html', '</ol>');
		}
		
		$mform->addElement('text', 'date', get_string('date_input', 'local_obu_forms'), 'size="4" maxlength="4"');
		$this->add_action_buttons(true, get_string('submit', 'local_obu_forms'));
    }
	
	function validation($data, $files) {
		$errors = parent::validation($data, $files); // Ensure we don't miss errors from any higher-level validation
		
		// Do our own validation and add errors to array
		foreach ($data as $key => $value) {
			if ($key == 'date') {
				$month = substr($value, 0, 2) + 0;
				$year = substr($value, 2) + 0;
				if (($month < 1) || ($month > 12) || ($year < 0) || ($year > 99)) {
					$errors[$key] = get_string('invalid_date', 'local_obu_forms');
				}
			}
		}
		
		return $errors;
	}
}