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
 * OBU Forms - Input form for user forms listing
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2015, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once("{$CFG->libdir}/formslib.php");

class user_input extends moodleform {

    function definition() {
        $mform =& $this->_form;
		
		$mform->addElement('text', 'username', get_string('user_number', 'local_obu_forms'), 'size="8" maxlength="8"');

        $this->add_action_buttons(true, get_string('continue', 'local_obu_forms'));
    }
	
	function validation($data, $files) {
		$errors = parent::validation($data, $files); // Ensure we don't miss errors from any higher-level validation
		
		// Do our own validation and add errors to array
		foreach ($data as $key => $value) {
			if ($key == 'username') {
				$user = get_complete_user_data('username', $value);
				if ($user === false) {
					$errors[$key] = get_string('user_not_found', 'local_obu_forms');
				}
			}
		}
		
		return $errors;
	}
}