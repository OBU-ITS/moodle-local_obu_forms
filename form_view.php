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
 * OBU Forms - Form view
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2015, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once("{$CFG->libdir}/formslib.php");

class form_view extends moodleform {
	
	// Arrays used in our own validation
	private $required_field = array(); // an array of field IDs, all of which must have values
	private $required_group = array(); // a group of field IDs, at least one of which must have a value

    function definition() {
		global $USER;
		
        $mform =& $this->_form;

        $data = new stdClass();
        $data->data_id = $this->_customdata['data_id'];
		$data->template = $this->_customdata['template'];
		$data->username = $this->_customdata['username'];
		$data->surname = $this->_customdata['surname'];
		$data->forenames = $this->_customdata['forenames'];
		$data->current_course = $this->_customdata['current_course'];
		$data->start_dates = $this->_customdata['start_dates'];
		$data->start_selected = $this->_customdata['start_selected'];
        $data->adviser = $this->_customdata['adviser'];
        $data->course = $this->_customdata['course'];
        $data->not_enroled = $this->_customdata['not_enroled'];
        $data->study_mode = $this->_customdata['study_mode'];
        $data->reason = $this->_customdata['reason'];
        $data->fields = $this->_customdata['fields'];
		$data->status_text = $this->_customdata['status_text'];
		$data->button_text = $this->_customdata['button_text'];
		
		// Start with the required hidden field
		if ($data->data_id > 0) { // Using form to amend or view
			$mform->addElement('hidden', 'id', $data->data_id);
		} else { // Using form for initial input
			$mform->addElement('hidden', 'template', $data->template->id);
		}
		
        // Process the template
		$fld_start = '<input ';
		$fld_start_len = strlen($fld_start);
        $fld_end = '>';
		$fld_end_len = strlen($fld_end);
		$offset = 0;
		$date_format = 'd-m-y';
		
		// This 'dummy' element has two purposes:
		// - To force open the Moodle Forms invisible fieldset outside of any table on the form (corrupts display otherwise)
		// - To let us inform the user that there are validation errors without them having to scroll down further
		$mform->addElement('static', 'form_error');

		do {
			$pos = strpos($data->template->data, $fld_start, $offset);
			if ($pos === false) {
				break;
			}
			if ($pos > $offset) {
				$mform->addElement('html', substr($data->template->data, $offset, ($pos - $offset))); // output any HTML
			}
			$offset = $pos + $fld_start_len;
			$pos = strpos($data->template->data, $fld_end, $offset);
			if ($pos === false) {
				break;
			}
			$element = split_input_field(substr($data->template->data, $offset, ($pos - $offset)));
			$offset = $pos + $fld_end_len;
			if (!empty($data->fields)) { // simply display the field
				$text = $data->fields[$element['id']];
				if ($element['type'] == 'checkbox') { // map a checkbox value to a nice character
					if ($text == '1') {
						$text = '&#10004;';
					} else {
						$text = '';
					}
				}
				if (($element['type'] == 'date') && ($text)) { // map a UNIX date to a nice text string
					$date = date_create();
					date_timestamp_set($date, $text);
					$text = date_format($date, $date_format);
				}
				$mform->addElement('static', $element['id'], $element['value'], $text);
			} else {
				switch ($element['type']) {
					case 'area':
						$mform->addElement('textarea', $element['id'], $element['value'], $element['options']);
						break;
					case 'checkbox':
						if ($element['rule'] == 'required') { // mustn't return a zero value
							$mform->addElement('checkbox', $element['id'], $element['value']);
						} else {
							$mform->addElement('advcheckbox', $element['id'], $element['value'], $element['name'], null, array(0, 1));
						}
						break;
					case 'date':
						$mform->addElement('date_selector', $element['id'], $element['value'], $element['options']);
						break;
					case 'select':
						switch ($element['name']) {
							case 'start_dates':
								$options = $data->start_dates;
								break;
							case 'adviser':
								$options = $data->adviser;
								break;
							case 'course':
								$options = $data->course;
								break;
							case 'not_enroled':
								$options = $data->not_enroled;
								break;
							case 'enroled':
								$options = $data->enroled;
								break;
							case 'study_mode':
								$options = $data->study_mode;
								break;
							case 'reason':
								$options = $data->reason;
								break;
							default:
						}
						$select = $mform->addElement('select', $element['id'], $element['value'], $options, null);
						switch ($element['selected']) {
							case 'start_selected':
								$select->setSelected($data->start_selected);
								break;
							default:
						}
						break;
					case 'static':
						switch ($element['name']) {
							case 'username':
								$text = $data->username;
								break;
							case 'surname':
								$text = $data->surname;
								break;
							case 'forenames':
								$text = $data->forenames;
								break;
							case 'current_course':
								$text = $data->current_course;
								break;
							default:
								$text = '';
						}
						$mform->addElement('static', '', $element['value'], $text); // Display the field...
						$mform->addElement('hidden', $element['id'], $text); // ...and also return it
						break;
					case 'text':
						$mform->addElement('text', $element['id'], $element['value'], $element['options']);
						break;
					case 'alphabetic':
						$mform->addElement('text', $element['id'], $element['value'], $element['options']);
						$mform->addRule($element['id'], null, 'lettersonly', null, 'server'); // Let Moodle handle the rule
						break;
					case 'numeric':
						$mform->addElement('text', $element['id'], $element['value'], $element['options']);
						$mform->addRule($element['id'], null, 'numeric', null, 'server'); // Let Moodle handle the rule
						break;
					default:
				}
				
				if ($element['rule']) { // An extra validation rule applies to this field
					if ($element['rule'] == 'group') { // At least one of this group of fields is required
						$this->required_group[] = $element['id']; // For our own validation
					} else {
						$mform->addRule($element['id'], null, $element['rule'], null, 'server'); // Let Moodle handle the rule
						if ($element['rule'] == 'required') {
							$this->required_field[] = $element['id']; // For our own extra validation
						}
					}
				}
			}
		} while(true);

		$mform->addElement('html', substr($data->template->data, $offset)); // output any remaining HTML

		if (!empty($data->status_text)) {
			$mform->addElement('html', '<p /><strong>' . $data->status_text . '</strong>'); // output any status text
		}
		
		$buttonarray = array();
		if ($data->button_text != 'cancel') {
			$buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string($data->button_text, 'local_obu_forms'));
		}
		if ($data->button_text != 'continue') {
			if ($data->button_text == 'authorise') {
				$mform->addElement('text', 'comment', get_string('comment', 'local_obu_forms'));
				$buttonarray[] = &$mform->createElement('submit', 'rejectbutton', get_string('reject', 'local_obu_forms'));
			}
			$buttonarray[] = &$mform->createElement('cancel');
		}
		$mform->addGroup($buttonarray, 'buttonarray', '', array(' '), false);
		$mform->closeHeaderBefore('buttonarray');
    }
	
	function validation($data, $files) {
		$errors = parent::validation($data, $files); // Ensure we don't miss errors from any higher-level validation
		
		// Get valid dates for +- 5 years (MMMYY format)
		$start_dates = get_dates(date('m'), date('y'), 60, 60);
		
		// Check if at least one field in a required group has an entry
		if (empty($this->required_group)) {
			$group_entry = true;
		} else {
			$group_entry = false;
			foreach($this->required_group as $key) {
				if ($data[$key]) {
					$group_entry = true;
				}
			}
		}
		
		// Do our own validation and add errors to array
		$required_value = false;
		foreach ($data as $key => $value) {
			if (in_array($key, $this->required_field, true) &&  !$value) {
				$required_value = true; // Leave the field error display to Moodle
			} else if (!$group_entry && in_array($key, $this->required_group, true)) { // One of a required group with no entries
				$errors[$key] = get_string('group_required', 'local_obu_forms');
			} else if ($key == 'course') { // Exact match - should be a current PG course (programme) code
				if ($value) { // Might not be mandatory
					$current_courses = get_current_courses('P');
					if (!in_array(strtoupper($value), $current_courses, true)) {
						$errors[$key] = get_string('course_not_found', 'local_obu_forms');
					}
				}
			} else if (strpos($key, 'module') !== false) { // Validate module code format etcetera
				if ($value) { // Only validate if the field was completed
					$prefix = strtoupper(substr($value, 0, 1));
					$suffix = substr($value, 1);
					if ((strlen($value) != 6) || (($prefix != 'U') && ($prefix != 'P')) || !is_numeric($suffix)) {
						$errors[$key] = get_string('invalid_module_code', 'local_obu_forms');
					} else if ($key == 'module') { // Exact match - should be a current module
						$current_modules = get_current_modules();
						if (!in_array($prefix . $suffix, $current_modules, true)) {
							$errors[$key] = get_string('module_not_found', 'local_obu_forms');
						}
					}
					
					// Check that any associated module fields have also been completed
					$pos = strpos($key, 'module');
					$prefix = substr($key, 0, $pos);
					$suffix = substr($key, ($pos + 6));
					$key = $prefix . 'title' . $suffix;
					if (array_key_exists($key, $data) && !$data[$key]) {
						$errors[$key] = get_string('value_required', 'local_obu_forms');
					}
					$key = $prefix . 'start' . $suffix;
					if (array_key_exists($key, $data) && !$data[$key]) {
						$errors[$key] = get_string('value_required', 'local_obu_forms');
					}
					$key = $prefix . 'mark' . $suffix;
					if (array_key_exists($key, $data) && !$data[$key]) {
						$errors[$key] = get_string('value_required', 'local_obu_forms');
					}
					$key = $prefix . 'credit' . $suffix;
					if (array_key_exists($key, $data) && !$data[$key]) {
						$errors[$key] = get_string('value_required', 'local_obu_forms');
					}
				}
			} else if (strpos($key, 'start') !== false) { // Validate start date format
				if ($value) { // Only validate if the field was completed
					$month = strtoupper(substr($value, 0, 3));
					$year = substr($value, 3);
					if ((strlen($value) != 5) || is_numeric($month) || !is_numeric($year)) {
						$errors[$key] = get_string('invalid_date_format', 'local_obu_forms');
					} else if (!in_array($month . $year, $start_dates, true)) {
						$errors[$key] = get_string('invalid_start_date', 'local_obu_forms');
					}
				}
			}
		}
		
		if ($required_value || !empty($errors)) {
			$errors['form_error'] = get_string('form_error', 'local_obu_forms');
		}
		
		return $errors;
    }
}