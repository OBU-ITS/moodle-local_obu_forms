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
 * OBU Forms - library functions
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2019, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
 
require_once($CFG->dirroot . '/local/obu_forms/db_update.php');

// Determine the possible menu options for this user
function get_menu_options() {
	global $USER;
	
	$options = array();
	$update = has_capability('local/obu_forms:update', context_system::instance());
	$accommodation = ($USER->username == 'accommodation');
	$staff = is_staff($USER->username); // Has a 'p' number?
	$student = is_student($USER->id); // Enrolled on a PIP-based course (programme)?
	
	// Add the 'My Forms' option
	if ($staff || $student || !empty(get_form_data($USER->id))) {
		$options[get_string('myforms', 'local_obu_forms')] = '/local/obu_forms/index.php?userid=' . $USER->id;
	}
	
	if (!$accommodation && !$staff) {
		if (!$student || !$update) { // Move on now please, nothing more to see here...
			return $options;
		}
	}
	 
	if ($accommodation) {
			$options[get_string('student_withdrawals', 'local_obu_forms')] = '/local/obu_forms/withdrawals.php';
	} else { // For other users, add the option(s) to list all the relevant forms
		if ($update) {
			if ($staff) {
				$options[get_string('staff_forms', 'local_obu_forms')] = '/local/obu_forms/formslist.php?type=staff';
			}
			$options[get_string('student_forms', 'local_obu_forms')] = '/local/obu_forms/formslist.php?type=student'; // Both staff and students can view student forms
		}
		if ($staff) {
			$options[get_string('list_users_forms', 'local_obu_forms')] = '/local/obu_forms/list.php';
		}
	}
	
	return $options;
}

// Check if the user is a forms manager (or a manager of a given form)
function is_manager($form = null) {
	global $USER;
	
	if (is_siteadmin()) {
		return true;
	}
	
	if ($form == null) {
		$is_manager = has_forms_role($USER->id, 4, 5);
	} else if ($form->modular == '0') { // PG form
		$is_manager = has_forms_role($USER->id, 4);
	} else { // UMP form
		$is_manager = has_forms_role($USER->id, 5);
	}
	
	return $is_manager;
}

function get_dates($month, $year, $back = 0, $forward = 0) {
	$months = [ 'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC' ];

	$dates = array();
	$dates[0] = get_string('select', 'local_obu_forms'); // The 'Please select' default
	
	if (($back == 0) && ($forward == 0)) { // Modular form so show semesters in the last AY, this AY and the next two
		if ($month >= 8) { // AY moves forward in August
			$y = $year;
		} else {
			$y = $year - 1;
		}
		if (($month < 4) || ($month >= 8)) { // Date range decreases in April
			$m = 1; // Begin in January
			$sems = 11; // Semesters to display
		}
		else {
			$m = 9; // Begin in September
			$sems = 9; // Semesters to display
		}
		for ($i = 0; $i < $sems; $i++) {
			$dates[] = $months[$m - 1] . $y;
			$m += 4;
			if ($m > 12) {
				$y++;
				$m -= 12;
			}
		}
	} else {
		// Get the starting year and month
		$y = (int)($back / 12); // Years back
		$m = $back - ($y * 12); // Months back
		$y = $year - $y;
		$m = $month - $m;
		if ($m < 1) {
			$y--;
			$m += 12;
		}
	
		for ($i = 0; $i <= ($back + $forward); $i++) {
			$dates[] = $months[$m - 1] . $y;
			if ($m < 12) {
				$m++;
			} else {
				$y++;
				$m = 1;
			}
		}
	}
	
	return $dates;
}

function get_authorisers() {
	$authoriser = array(
		'None',
		'CSA/SC',
		'Module Leader',
		'Subject Coordinator',
		'Supervisor',
		'Academic Adviser',
		'Programme Lead',
		'Programme Lead (Joint Honours)',
		'Module Leader (2)',
		'Exchanges Office',
		'Student',
		'Supervisor (2)',
		'Admissions',
		'ISA Team',
		'Programme Lead (Course Change)',
		'Subject Coordinator (Course Change)',
		'Subject Coordinator (Course Change, Joint Honours)'
	);
	
	return $authoriser;
}

function get_study_modes() {
	$study_mode = array(
		'Full-time',
		'Part-time',
		'Sandwich',
		'Distance Learning'
	);
	
	return $study_mode;
}

function get_reasons() {
	$reason = array(
		'Health Reasons',
		'Financial Reasons',
		'Family Commitments',
		'Going into Employment',
		'Course Unsuitable',
		'Course Unsatisfactory',
		'Deferred Entry to a later date',
		'Transferring to Another Institution',
		'Transferring to Another OBU Course',
		'Other (please give details below)'
	);
	
	return $reason;
}

function get_addition_reasons() {
	$reason = array(
		'I have failed or need to re-sit a module and subsequently need to make programme changes',
		'The module I had selected was cancelled so I need to add a replacement module',
		'I have been on an exchange or industrial year out placement',
		'I am returning from a period of Approved/Unapproved Temporary Withdrawal',
		'I have been allowed to return after successfully appealing my exclusion on the grounds of academic failure',
		'My subject change request was accepted after the Monday, Week 10 Module Addition Deadline',
		'Other (please give details below)'
	);
	
	return $reason;
}

function get_deletion_reasons() {
	$reason = array(
		'I have failed or need to re-sit a module and subsequently need to make programme changes',
		'The module I had selected was cancelled so I need to add a replacement module',
		'I have been on an exchange or industrial year out placement',
		'I am returning from a period of Approved/Unapproved Temporary Withdrawal',
		'I have been allowed to return after successfully appealing my exclusion on the grounds of academic failure',
		'My subject change request was accepted after the Monday, Week 10 Module Addition Deadline',
		'Other (please give details below)'
	);
	
	return $reason;
}

function get_assessment_types() {
	$assessment_type = array(
		'Written assignment (individual)',
		'Written assignment (group)',
		'Dissertation',
		'Project work (Individual)',
		'Project work (Group)',
		'Portfolio (Individual)',
		'Portfolio (Group)',
		'Oral assessment / presentation (Group)',
		'Oral assessment / presentation (Individual)',
		'Practical skills assessment (Group)',
		'Practical skills assessment (Individual)',
		'Set exercise',
		'Examination'
	);
	
	return $assessment_type;
}

function get_component_comments() {
	$component_comment = array(
		'',
		'Not Attempted',
		'Deferred Disciplinary',
		'Exceptional Circumstances'
	);
	
	return $component_comment;
}

function encode_xml($string) {
	return(htmlentities($string, ENT_NOQUOTES | ENT_XML1, 'UTF-8'));
}

function decode_xml($string) {
	return(html_entity_decode($string, ENT_NOQUOTES | ENT_XML1, 'UTF-8'));
}

function template_fields($template) {
	$fields = array();
	
	$fld_start = '<input ';
	$fld_start_len = strlen($fld_start);
	$fld_end = '>';
	$fld_end_len = strlen($fld_end);
	$offset = 0;
	do {
		$pos = strpos($template, $fld_start, $offset);
		if ($pos === false) {
			break;
		}
		$offset = $pos + $fld_start_len;
		$pos = strpos($template, $fld_end, $offset);
		if ($pos === false) {
			break;
		}
		$element = split_input_field(substr($template, $offset, ($pos - $offset)));
		$fields[] = $element;
		$offset = $pos + $fld_end_len;
	} while(true);
	
	return $fields;
}

function template_selects($template) {
	$selects = array();
	
	$fld_start = '<input ';
	$fld_start_len = strlen($fld_start);
	$fld_end = '>';
	$fld_end_len = strlen($fld_end);
	$offset = 0;
	do {
		$pos = strpos($template, $fld_start, $offset);
		if ($pos === false) {
			break;
		}
		$offset = $pos + $fld_start_len;
		$pos = strpos($template, $fld_end, $offset);
		if ($pos === false) {
			break;
		}
		$element = split_input_field(substr($template, $offset, ($pos - $offset)));
		$offset = $pos + $fld_end_len;
		if ($element['type'] == 'select') {
			$selects[$element['id']] = $element['name'];
		}
	} while(true);
	
	return $selects;
}

function split_input_field($input_field) {
	$parts = str_replace('" ', '"|^|', $input_field);
	$parts = explode('|^|', $parts);
	$params = array();
	$options = '';
	foreach ($parts as $part) {
		$pos = strpos($part, '="');
		$key = substr($part, 0, $pos);
		
		// We were forced to use 'maxlength' so map it
		if (array_key_exists('type', $params) && ($params['type'] == 'select') && ($key == 'maxlength')) {
			$key = 'selected';
		}
		
		if (($key == 'size') || ($key == 'maxlength')) {
			if ($options != '') {
				$options .= ' ';
			}
			$options .= $part;
		} else {
			$pos += 2;
			$value = substr($part, $pos, (strlen($part) - 1 - $pos));
			$value = str_replace('"', '', $value);
			
			// If the 'value' parameter is suffixed then the field (or one of the required group) must be completed
			if ($key == 'value') {
				$suffix = substr($value, (strlen($value) - 1));
				if (($suffix == '*') || ($suffix == '#') || ($suffix == '=') || ($suffix == '+') || ($suffix == '-')) {
					$value = substr($value, 0, (strlen($value) - 1)); // Strip-off the indicator
					if ($suffix == '*') {
						$params['rule'] = 'required'; // A mandatory field
					} else if ($suffix == '#') {
						$params['rule'] = 'group'; // One of a (single) group of which at least one must be completed
					} else if ($suffix == '=') {
						$params['rule'] = 'check'; // A (single) check box that controls whether groups of fields are mandatory
					} else if ($suffix == '+') {
						$params['rule'] = 'check_set'; // A field that is mandatory if the controlling check box is set
					} else {
						$params['rule'] = 'check_unset'; // A field that is mandatory if the controlling check box is unset
					}
				}
			}
			
			$params[$key] = $value;
		}
	}
	if ($options != '') {
		// We were forced to use 'size' and 'maxlength' in 'area' (textarea) so map them
		if ($params['type'] == 'area') {
			$options = str_replace('size', 'cols', $options);
			$options = str_replace('maxlength', 'rows', $options);
		}
		$params['options'] = $options;
	}
	
	return $params;
}

function split_name($fullname, $prefix = true) {
	$parts = array();
	$split_pos = strpos($fullname, ': ');
	if ($prefix) {
		$parts[] = substr($fullname, 0, $split_pos); // Number
	} else {
		$parts[] = substr($fullname, 1, $split_pos); // Number (omit the prefix)
	}
	$parts[] = substr($fullname, ($split_pos + 2)); // Name
	
	return $parts;
}

function merge_xml($form, $fields) {
	$file = './xml/' . $form . '_merge.xml';
	$xml = file_get_contents($file);
	if ($xml === false) {
		return false;
	}
	
	$i = 0;
	foreach ($fields as $field) {
		$search = '@@' . $i++ . '@';
		$xml = str_replace($search, encode_xml($field), $xml);
	}
		
	header('Content-Disposition: attachment; filename=' . $form . '.xml');
	header('Content-Type: application/xml');
	echo $xml;
	
	return true;
}

function save_form_data($record, $fields) {
	$xml = new SimpleXMLElement('<form_data/>');
	foreach ($fields as $key => $value) {
		$xml->addChild($key, encode_xml($value));
	}
    $record->data = $xml->asXML();
	
	return write_form_data($record);
}

function load_form_data($data_id, &$record, &$fields) {
	if (!read_form_data($data_id, $record)) {
		return false;
	}
	load_form_fields($record, $fields);
	
	return true;
}

function load_form_fields($record, &$fields) {
	$xml = new SimpleXMLElement($record->data);
	$fields = array();
	foreach ($xml as $key => $value) {
		$fields[$key] = (string)$value;
	}
}

function get_form_status($user_id, $form, $data, &$text, &$button) {

	$text = '';
	$button = '';
	$context = context_system::instance();
	
	// Get the Student Central ID and relevant name
	$sc = get_complete_user_data('username', 'csa'); // Student Central (CSA/SC)
	$sc_id = $sc->id;
	if ($form->modular) { // Use the SCAT details (UMP)
		$sc = get_complete_user_data('username', 'scat');
	}
	$sc_name = $sc->alternatename;
	
	$authoriser_role = get_authorisers();

	// Prepare the submission/authorisation trail
	$date = date_create();
	$format = 'd-m-y H:i';
	if ($data->authorisation_level > 0) { // Author has submitted the form
		date_timestamp_set($date, $data->date);
		$text .= date_format($date, $format) . ' ';
		if ($data->author == $user_id) {
			$name = 'you';
		} else if ($data->author == $sc_id) {
			$name = $sc_name;
		} else {
			$authoriser = get_complete_user_data('id', $data->author);
			$name = $authoriser->firstname . ' ' . $authoriser->lastname;
		}
		$text .= get_string('actioned_by', 'local_obu_forms', array('action' => get_string('submitted', 'local_obu_forms'), 'by' => $name));
		$text .= '<br />';
		
		// Authorisation level 1
		if (($data->authorisation_level == 1) && ($data->authorisation_state > 0)) { // The workflow ended here
			date_timestamp_set($date, $data->auth_1_date);
			$text .= date_format($date, $format) . ' ';
			if ($data->auth_1_id == $user_id) {
				$name = 'you as ' . $authoriser_role[$form->auth_1_role];
			} else if ($data->auth_1_id == $sc_id) {
				$name = $sc_name;
			} else {
				$authoriser = get_complete_user_data('id', $data->auth_1_id);
				$name = $authoriser->firstname . ' ' . $authoriser->lastname;
			}
			if ($data->authorisation_state == 1) {
				$text .= get_string('actioned_by', 'local_obu_forms', array('action' => get_string('rejected', 'local_obu_forms'), 'by' => 	$name));
			} else {
				$text .= get_string('actioned_by', 'local_obu_forms', array('action' => get_string('authorised', 'local_obu_forms'), 'by' => $name));
			}
			$text .= ' ' . $data->auth_1_notes . '<br />';
		} else if ($data->authorisation_level > 1) { // We've passed this level
			if ($data->auth_1_id != 0) { // Include level in trail only if it wasn't skipped
				date_timestamp_set($date, $data->auth_1_date);
				$text .= date_format($date, $format) . ' ';
				if ($data->auth_1_id == $user_id) {
					$name = 'you as ' . $authoriser_role[$form->auth_1_role];
				} else if ($data->auth_1_id == $sc_id) {
					$name = $sc_name;
				} else {
					$authoriser = get_complete_user_data('id', $data->auth_1_id);
					$name = $authoriser->firstname . ' ' . $authoriser->lastname;
				}
				$text .= get_string('actioned_by', 'local_obu_forms', array('action' => get_string('authorised', 'local_obu_forms'), 'by' => $name));
				$text .= ' ' . $data->auth_1_notes . '<br />';
			}
			
			// Authorisation level 2
			if (($data->authorisation_level == 2) && ($data->authorisation_state > 0)) { // The workflow ended here
				date_timestamp_set($date, $data->auth_2_date);
				$text .= date_format($date, $format) . ' ';
				if ($data->auth_2_id == $user_id) {
					$name = 'you as ' . $authoriser_role[$form->auth_2_role];
				} else if ($data->auth_2_id == $sc_id) {
					$name = $sc_name;
				} else {
					$authoriser = get_complete_user_data('id', $data->auth_2_id);
					$name = $authoriser->firstname . ' ' . $authoriser->lastname;
				}
				if ($data->authorisation_state == 1) {
					$text .= get_string('actioned_by', 'local_obu_forms', array('action' => get_string('rejected', 'local_obu_forms'), 'by' => $name));
				} else {
					$text .= get_string('actioned_by', 'local_obu_forms', array('action' => get_string('authorised', 'local_obu_forms'), 'by' => $name));
				}
				$text .= ' ' . $data->auth_2_notes . '<br />';
			} else if ($data->authorisation_level > 2) { // We've passed this level
				if ($data->auth_2_id != 0) { // Include level in trail only if it wasn't skipped
					date_timestamp_set($date, $data->auth_2_date);
					$text .= date_format($date, $format) . ' ';
					if ($data->auth_2_id == $user_id) {
						$name = 'you as ' . $authoriser_role[$form->auth_2_role];
					} else if ($data->auth_2_id == $sc_id) {
						$name = $sc_name;
					} else {
						$authoriser = get_complete_user_data('id', $data->auth_2_id);
						$name = $authoriser->firstname . ' ' . $authoriser->lastname;
					}
					$text .= get_string('actioned_by', 'local_obu_forms', array('action' => get_string('authorised', 'local_obu_forms'), 'by' => $name));
					$text .= ' ' . $data->auth_2_notes . '<br />';
				}
				
				// Authorisation level 3
				if (($data->authorisation_level == 3) && ($data->authorisation_state > 0)) { // The workflow ended here
					date_timestamp_set($date, $data->auth_3_date);
					$text .= date_format($date, $format) . ' ';
					if ($data->auth_3_id == $user_id) {
						$name = 'you as ' . $authoriser_role[$form->auth_3_role];
					} else if ($data->auth_3_id == $sc_id) {
						$name = $sc_name;
					} else {
						$authoriser = get_complete_user_data('id', $data->auth_3_id);
						$name = $authoriser->firstname . ' ' . $authoriser->lastname;
					}
					if ($data->authorisation_state == 1) {
						$text .= get_string('actioned_by', 'local_obu_forms', array('action' => get_string('rejected', 'local_obu_forms'), 'by' => $name));
					} else {
						$text .= get_string('actioned_by', 'local_obu_forms', array('action' => get_string('authorised', 'local_obu_forms'), 'by' => $name));
					}
					$text .= ' ' . $data->auth_3_notes . '<br />';
				} else if ($data->authorisation_level > 3) { // We've passed this level
					if ($data->auth_3_id != 0) { // Include level in trail only if it wasn't skipped
						date_timestamp_set($date, $data->auth_3_date);
						$text .= date_format($date, $format) . ' ';
						if ($data->auth_3_id == $user_id) {
							$name = 'you as ' . $authoriser_role[$form->auth_3_role];
						} else if ($data->auth_3_id == $sc_id) {
							$name = $sc_name;
						} else {
							$authoriser = get_complete_user_data('id', $data->auth_3_id);
							$name = $authoriser->firstname . ' ' . $authoriser->lastname;
						}
						$text .= get_string('actioned_by', 'local_obu_forms', array('action' => get_string('authorised', 'local_obu_forms'), 'by' => $name));
						$text .= ' ' . $data->auth_3_notes . '<br />';
					}
					
					// Authorisation level 4
					if (($data->authorisation_level == 4) && ($data->authorisation_state > 0)) { // The workflow ended here
						date_timestamp_set($date, $data->auth_4_date);
						$text .= date_format($date, $format) . ' ';
						if ($data->auth_4_id == $user_id) {
							$name = 'you as ' . $authoriser_role[$form->auth_4_role];
						} else if ($data->auth_4_id == $sc_id) {
							$name = $sc_name;
						} else {
							$authoriser = get_complete_user_data('id', $data->auth_4_id);
							$name = $authoriser->firstname . ' ' . $authoriser->lastname;
						}
						if ($data->authorisation_state == 1) {
							$text .= get_string('actioned_by', 'local_obu_forms', array('action' => get_string('rejected', 'local_obu_forms'), 'by' => $name));
						} else {
							$text .= get_string('actioned_by', 'local_obu_forms', array('action' => get_string('authorised', 'local_obu_forms'), 'by' => $name));
						}
						$text .= ' ' . $data->auth_4_notes . '<br />';
					} else if ($data->authorisation_level > 4) { // We've passed this level
						if ($data->auth_4_id != 0) { // Include level in trail only if it wasn't skipped
							date_timestamp_set($date, $data->auth_4_date);
							$text .= date_format($date, $format) . ' ';
							if ($data->auth_4_id == $user_id) {
								$name = 'you as ' . $authoriser_role[$form->auth_4_role];
							} else if ($data->auth_4_id == $sc_id) {
								$name = $sc_name;
							} else {
								$authoriser = get_complete_user_data('id', $data->auth_4_id);
								$name = $authoriser->firstname . ' ' . $authoriser->lastname;
							}
							$text .= get_string('actioned_by', 'local_obu_forms', array('action' => get_string('authorised', 'local_obu_forms'), 'by' => $name));
							$text .= ' ' . $data->auth_4_notes . '<br />';
						}
					
						// Authorisation level 5
						if (($data->authorisation_level == 5) && ($data->authorisation_state > 0)) { // The workflow ended here
							date_timestamp_set($date, $data->auth_5_date);
							$text .= date_format($date, $format) . ' ';
							if ($data->auth_5_id == $user_id) {
								$name = 'you as ' . $authoriser_role[$form->auth_5_role];
							} else if ($data->auth_5_id == $sc_id) {
								$name = $sc_name;
							} else {
								$authoriser = get_complete_user_data('id', $data->auth_5_id);
								$name = $authoriser->firstname . ' ' . $authoriser->lastname;
							}
							if ($data->authorisation_state == 1) {
								$text .= get_string('actioned_by', 'local_obu_forms', array('action' => get_string('rejected', 'local_obu_forms'), 'by' => $name));
							} else {
								$text .= get_string('actioned_by', 'local_obu_forms', array('action' => get_string('authorised', 'local_obu_forms'), 'by' => $name));
							}
							$text .= ' ' . $data->auth_5_notes . '<br />';
						} else if ($data->authorisation_level > 5) { // We've passed this level
							if ($data->auth_5_id != 0) { // Include level in trail only if it wasn't skipped
								date_timestamp_set($date, $data->auth_5_date);
								$text .= date_format($date, $format) . ' ';
								if ($data->auth_5_id == $user_id) {
									$name = 'you as ' . $authoriser_role[$form->auth_5_role];
								} else if ($data->auth_5_id == $sc_id) {
									$name = $sc_name;
								} else {
									$authoriser = get_complete_user_data('id', $data->auth_5_id);
									$name = $authoriser->firstname . ' ' . $authoriser->lastname;
								}
								$text .= get_string('actioned_by', 'local_obu_forms', array('action' => get_string('authorised', 'local_obu_forms'), 'by' => $name));
								$text .= ' ' . $data->auth_5_notes . '<br />';
							}
						
							// Authorisation level 6 (the last possible one)
							if ($data->authorisation_state > 0) { // The workflow ended here
								date_timestamp_set($date, $data->auth_6_date);
								$text .= date_format($date, $format) . ' ';
								if ($data->auth_6_id == $user_id) {
									$name = 'you as ' . $authoriser_role[$form->auth_6_role];
								} else if ($data->auth_6_id == $sc_id) {
									$name = $sc_name;
								} else {
									$authoriser = get_complete_user_data('id', $data->auth_6_id);
									$name = $authoriser->firstname . ' ' . $authoriser->lastname;
								}
								if ($data->authorisation_state == 1) {
									$text .= get_string('actioned_by', 'local_obu_forms', array('action' => get_string('rejected', 'local_obu_forms'), 'by' => $name));
								} else {
									$text .= get_string('actioned_by', 'local_obu_forms', array('action' => get_string('authorised', 'local_obu_forms'), 'by' => $name));
								}
								$text .= ' ' . $data->auth_6_notes . '<br />';
							}
						}
					}
				}
			}
		}
	}

	// If the state is zero, display the next action required.  Otherwise, the form has already been rejected or processed 
	if ($data->authorisation_state == 0) { // Awaiting submission/rejection/authorisation from someone
		if ($data->authorisation_level == 0) { // Author hasn't submitted the form
			if ($data->author == $user_id) {
				$name = 'you';
				$button = 'submit';
			} else {
				if ($data->author == $sc_id) {
					$name = $sc_name;
				} else {
					$authoriser = get_complete_user_data('id', $data->author);
					$name = $authoriser->firstname . ' ' . $authoriser->lastname;
				}
				$button = 'continue';
			}
			$text .= '<p />' . get_string('awaiting_action', 'local_obu_forms', array('action' => get_string('submission', 'local_obu_forms'), 'by' => $name));
		} else {
			if ($data->authorisation_level == 1) {
				$authoriser_id = $data->auth_1_id;
				$role_id = $form->auth_1_role;
			} else if ($data->authorisation_level == 2) {
				$authoriser_id = $data->auth_2_id;
				$role_id = $form->auth_2_role;
			} else if ($data->authorisation_level == 3) {
				$authoriser_id = $data->auth_3_id;
				$role_id = $form->auth_3_role;
			} else if ($data->authorisation_level == 4) {
				$authoriser_id = $data->auth_4_id;
				$role_id = $form->auth_4_role;
			} else if ($data->authorisation_level == 5) {
				$authoriser_id = $data->auth_5_id;
				$role_id = $form->auth_5_role;
			} else {
				$authoriser_id = $data->auth_6_id;
				$role_id = $form->auth_6_role;
			}
			if (($authoriser_id == $user_id) || (($authoriser_id == $sc_id) && is_manager($form))) {
				$name = 'you as ' . $authoriser_role[$role_id];
				$button = 'authorise';
			} else {
				if ($authoriser_id == $sc_id) {
					$name = $sc_name;
				} else {
					$authoriser = get_complete_user_data('id', $authoriser_id);
					$name = $authoriser->firstname . ' ' . $authoriser->lastname;
					if ($authoriser->username == 'csa-tbd') { // Authoriser TBD so highlight
						$name = "<span style='color:red'>" . $name . "</span>";
					}
				}
				$button = 'continue';
			}
			$text .= '<p />' . get_string('awaiting_action', 'local_obu_forms', array('action' => get_string('authorisation', 'local_obu_forms'), 'by' => $name));
		}
	} else { // Form processed - nothing more to say...
		$button = 'continue';
	}
}

function update_authoriser($form, $data, $authoriser_id) {

	$authoriser_role = get_authorisers();

	// Update the stored authorisation requests
	read_form_auths($data->id, $auth);
	if ($authoriser_id == 0) {
		delete_form_auths($auth);
	} else {
		$auth->authoriser = $authoriser_id;
		$auth->request_date = time();
		write_form_auths($auth);
	}
	
	// Determine the URL to use to link to the form
	$program = new moodle_url('/local/obu_forms/process.php') . '?id=' . $data->id;

	// Email the new status to the author and to Student Central (if not the next authoriser)
	$author = get_complete_user_data('id', $data->author);
	$sc = get_complete_user_data('username', 'csa');
	$sc_id = $sc->id;
//	if (!$form->modular) { // Use the default CSA Team contact and notification details (PG)
//		$sc_contact = $sc;
//		$sc_notifications = $sc;
//	} else { // Use the SCAT contact and notification details (UMP)
		$sc_contact = get_complete_user_data('username', 'scat');
		$sc_notifications = get_complete_user_data('username', 'scat_notifications');
//	}
    // Add email headers to help prevent auto-responders
    $author->customheaders = array (
		'Precedence: Bulk',
		'X-Auto-Response-Suppress: All',
		'Auto-Submitted: auto-generated'
	);
	$sc_contact->customheaders = array (
		'Precedence: Bulk',
		'X-Auto-Response-Suppress: All',
		'Auto-Submitted: auto-generated'
	);

	get_form_status($author->id, $form, $data, $text, $button_text); // get the status from the author's perspective
	
	// If a staff form, extract any given student number
	$student_number = '';
	if (!$form->student) {
		load_form_fields($data, $fields);
		if (array_key_exists('student_number', $fields)) {
			$student_number = ' [' . $fields['student_number'] . ']';
		}
	}
	
	$html = '<h4><a href="' . $program . '">' . $form->formref . ': ' . $form->name . $student_number . '</a></h4>' . $text;
	email_to_user($author, $sc_contact, 'The Status of Your Form ' . $form->formref . $student_number, html_to_text($html), $html);
	if ($authoriser_id != $sc_id) {
		get_form_status($sc_id, $form, $data, $text, $button_text); // get the status from the perspective of Student Central
		$html = '<h4><a href="' . $program . '">' . $form->formref . ': ' . $form->name . $student_number . '</a></h4>' . $text;
		email_to_user($sc_notifications, $author, 'Form ' . $form->formref . $student_number . ' Status Update (' . $author->username . ')', html_to_text($html), $html);
	}
	
	// Notify the next authoriser (if there is one)
	if ($authoriser_id) {
		if ($authoriser_id == $sc_id) {
			$authoriser = $sc_notifications;
		} else {
			$authoriser = get_complete_user_data('id', $authoriser_id);
		}
		if ($authoriser->username != 'csa-tbd') { // No notification possible if authoriser TBD
			if ($data->authorisation_level == 1) {
				$role_id = $form->auth_1_role;
			} else if ($data->authorisation_level == 2) {
				$role_id = $form->auth_2_role;
			} else if ($data->authorisation_level == 3) {
				$role_id = $form->auth_3_role;
			} else if ($data->authorisation_level == 4) {
				$role_id = $form->auth_4_role;
			} else {
				$role_id = $form->auth_5_role;
			}
			$form_link = '<a href="' . $program . '">' . $form->formref . ' ' . get_string('form_title', 'local_obu_forms') . $student_number . '</a>';
			$email_link = '<a href="mailto:' . $sc_contact->email . '?Subject=' . get_string('auths', 'local_obu_forms') . '" target="_top">' . $sc_contact->email . '</a>';
			$html = get_string('request_authorisation', 'local_obu_forms',
				array('form' => $form_link, 'role' => $authoriser_role[$role_id], 'name' => $sc_contact->alternatename, 'email' => $email_link));
			email_to_user($authoriser, $author, 'Request for Form ' . $form->formref . $student_number . ' Authorisation (' . $author->username . ')', html_to_text($html), $html);
		}
	}
}

?>
