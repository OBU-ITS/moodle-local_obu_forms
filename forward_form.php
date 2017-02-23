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
 * OBU Forms - Input form for forwarding forms
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2017, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once("{$CFG->libdir}/formslib.php");

class forward_form extends moodleform {

    function definition() {
        $mform =& $this->_form;
		
        $data = new stdClass();
		$data->from = $this->_customdata['from'];
		$data->to = $this->_customdata['to'];
		$data->start_date = $this->_customdata['start_date'];
		$data->stop_date = $this->_customdata['stop_date'];
		
		if ($data->from != '') {
			$fields = [
				'from' => $data->from,
				'to' => $data->to,
				'start_date' => $data->start_date,
				'stop_date' => $data->stop_date
			];
			$this->set_data($fields);
		}

		$mform->addElement('html', '<h2>' . get_string('forward_forms', 'local_obu_forms') . '</h2>');

		$mform->addElement('text', 'from', get_string('forward_from', 'local_obu_forms'), 'size="8" maxlength="8"');
		$mform->setType('from', PARAM_RAW);
		$mform->addRule('from', null, 'required', null, 'server');
		$mform->addElement('text', 'to', get_string('forward_to', 'local_obu_forms'), 'size="8" maxlength="8"');
		$mform->setType('to', PARAM_RAW);
		$mform->addElement('date_selector', 'start_date', get_string('forward_start', 'local_obu_forms'));
		$mform->addElement('date_selector', 'stop_date', get_string('forward_stop', 'local_obu_forms'));

        $this->add_action_buttons(true, get_string('save', 'local_obu_forms'));
    }
	
	function validation($data, $files) {
		$errors = parent::validation($data, $files); // Ensure we don't miss errors from any higher-level validation
		
		// Do our own validation and add errors to array
		foreach ($data as $key => $value) {
			if ($key == 'from') {
				$user = get_complete_user_data('username', $value);
				if ($user === false) {
					$errors['from'] = get_string('user_not_found', 'local_obu_forms');
				}
			} else if (($key == 'to') && ($value != '')) {
				if ($data['to'] == $data['from']) {
					$errors['to'] = get_string('user_invalid', 'local_obu_forms');
				} else {
					$user = get_complete_user_data('username', $value);
					if ($user === false) {
						$errors['to'] = get_string('user_not_found', 'local_obu_forms');
					}
				}
			} else if (($key == 'start_date') && ($data['to'] != '') && ($value < strtotime('today midnight'))) {
				$errors['start_date'] = get_string('invalid_date', 'local_obu_forms');
			} else if (($key == 'stop_date') && ($data['to'] != '') && ($value < $data['start_date'])) {
				$errors['stop_date'] = get_string('invalid_date', 'local_obu_forms');
			}
		}
		
		return $errors;
	}
}