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
 * @copyright  2016, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
 
require_once($CFG->dirroot . '/local/obu_forms/db_update.php');

// Check if the user is a forms manager (or a manager of a given form)
function is_manager($form = null) {
	$context = context_system::instance();
	if ($form == null) {
		$is_manager = (has_capability('local/obu_forms:manage_pg', $context) || has_capability('local/obu_forms:manage_ump_staff', $context) || has_capability('local/obu_forms:manage_ump_students', $context));
	} else if ($form->modular == '0') { // PG form
		$is_manager = has_capability('local/obu_forms:manage_pg', $context);
	} else if ($form->student == '0') { // UMP staff form
		$is_manager = has_capability('local/obu_forms:manage_ump_staff', $context);
	} else { // UMP student form
		$is_manager = has_capability('local/obu_forms:manage_ump_students', $context);
	}
	
	return $is_manager;
}

function get_dates($month, $year, $back, $forward) {
	$months = [ 'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC' ];

	// Get the starting year and month
	$y = (int)($back / 12); // Years back
	$m = $back - ($y * 12); // Months back
	$y = $year - $y;
	$m = $month - $m;
	if ($m < 1) {
		$y--;
		$m += 12;
	}
	
	$dates = array();
	for ($i = 0; $i <= ($back + $forward); $i++) {
		$dates[] = $months[$m - 1] . $y;
		if ($m < 12) {
			$m++;
		} else {
			$y++;
			$m = 1;
		}
	}
	
	return $dates;
}

function get_authorisers() {
	$authoriser = array(
		'None',
		'CSA',
		'Module Leader',
		'Subject Coordinator',
		'Supervisor',
		'Academic Adviser',
		'Programme Lead'
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
		'Transferring to Another Institution',
		'Transferring to Another OBU Course',
		'Other (please give details below)'
	);
	
	return $reason;
}

function encode_xml($string) {
	return(htmlentities($string, ENT_NOQUOTES | ENT_XML1, 'UTF-8'));
}

function decode_xml($string) {
	return(html_entity_decode($string, ENT_NOQUOTES | ENT_XML1, 'UTF-8'));
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
				if (($suffix == '#') || ($suffix == '*')) {
					$value = substr($value, 0, (strlen($value) - 1)); // Strip-off the indicator
					if ($suffix == '#') {
						$params['rule'] = 'group';
					} else {
						$params['rule'] = 'required';
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

function get_form_status($user_id, $data, &$text, &$button) {

	$text = '';
	$button = '';
	$context = context_system::instance();
	$manager = (has_capability('local/obu_forms:manage_pg', $context) || has_capability('local/obu_forms:manage_ump_staff', $context) || has_capability('local/obu_forms:manage_ump_students', $context));
	
	// Prepare the submission/authorisation trail
	$date = date_create();
	$format = 'd-m-y H:i';
	if ($data->authorisation_level > 0) { // Author has submitted the form
		date_timestamp_set($date, $data->date);
		$text .= date_format($date, $format) . ' ';
		if ($data->author == $user_id) {
			$name = 'you';
		} else {
			$authoriser = get_complete_user_data('id', $data->author);
			$name = $authoriser->firstname . ' ' . $authoriser->lastname;
		}
		$text .= get_string('actioned_by', 'local_obu_forms', array('action' => get_string('submitted', 'local_obu_forms'), 'by' => $name));
		$text .= '<br />';
		if (($data->authorisation_level == 1) && ($data->authorisation_state > 0)) { // The workflow ended here
			date_timestamp_set($date, $data->auth_1_date);
			$text .= date_format($date, $format) . ' ';
			if ($data->auth_1_id == $user_id) {
				$name = 'you';
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
		} else if ($data->authorisation_level > 1) {
			date_timestamp_set($date, $data->auth_1_date);
			$text .= date_format($date, $format) . ' ';
			if ($data->auth_1_id == $user_id) {
				$name = 'you';
			} else {
				$authoriser = get_complete_user_data('id', $data->auth_1_id);
				$name = $authoriser->firstname . ' ' . $authoriser->lastname;
			}
			$text .= get_string('actioned_by', 'local_obu_forms', array('action' => get_string('authorised', 'local_obu_forms'), 'by' => $name));
			$text .= ' ' . $data->auth_1_notes . '<br />';
			if (($data->authorisation_level == 2) && ($data->authorisation_state > 0)) { // The workflow ended here
				date_timestamp_set($date, $data->auth_2_date);
				$text .= date_format($date, $format) . ' ';
				if ($data->auth_2_id == $user_id) {
					$name = 'you';
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
			} else if ($data->authorisation_level > 2) {
				date_timestamp_set($date, $data->auth_2_date);
				$text .= date_format($date, $format) . ' ';
				if ($data->auth_2_id == $user_id) {
					$name = 'you';
				} else {
					$authoriser = get_complete_user_data('id', $data->auth_2_id);
					$name = $authoriser->firstname . ' ' . $authoriser->lastname;
				}
				$text .= get_string('actioned_by', 'local_obu_forms', array('action' => get_string('authorised', 'local_obu_forms'), 'by' => $name));
				$text .= ' ' . $data->auth_2_notes . '<br />';
				if ($data->authorisation_state > 0) { // The workflow ended here
					date_timestamp_set($date, $data->auth_3_date);
					$text .= date_format($date, $format) . ' ';
					if ($data->auth_3_id == $user_id) {
						$name = 'you';
					} else {
						$authoriser = get_complete_user_data('id', $data->auth_3_id);
						$name = $authoriser->firstname . ' ' . $authoriser->lastname;
					}
					if ($data->authorisation_state == 1) {
						$text .= get_string('actioned_by', 'local_obu_forms', array('action' => get_string('rejected', 'local_obu_forms'), 'by' => $name));
					} else {
						$text .= get_string('actioned_by', 'local_obu_forms', array('action' => get_string('authorised', 'local_obu_forms'), 'by' => $name));
					}
					$text .= ' ' . $data->auth_2_notes . '<br />';
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
				$authoriser = get_complete_user_data('id', $data->author);
				$name = $authoriser->firstname . ' ' . $authoriser->lastname;
				$button = 'continue';
			}
			$text .= '<p />' . get_string('awaiting_action', 'local_obu_forms', array('action' => get_string('submission', 'local_obu_forms'), 'by' => $name));
		} else {
			if ($data->authorisation_level == 1) {
				$authoriser_id = $data->auth_1_id;
			} else if ($data->authorisation_level == 2) {
				$authoriser_id = $data->auth_2_id;
			} else {
				$authoriser_id = $data->auth_3_id;
			}
			if ($authoriser_id == $user_id) {
				$name = 'you';
				$button = 'authorise';
			} else {
				$authoriser = get_complete_user_data('id', $authoriser_id);
				$name = $authoriser->firstname . ' ' . $authoriser->lastname;
				if (($name == 'CSA Team') && $manager) {
					$button = 'authorise';
				} else {
					$button = 'continue';
				}
			}
			$text .= '<p />' . get_string('awaiting_action', 'local_obu_forms', array('action' => get_string('authorisation', 'local_obu_forms'), 'by' => $name));
		}
	} else { // Form processed - nothing more to say...
		$button = 'continue';
	}
}

function update_authoriser($form, $data, $authoriser_id) {

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

	// Email the new status to the author and to the CSA Team (if not the next authoriser)
	$csa = get_complete_user_data('username', 'csa');
	$csa_id = $csa->id; // The ID used for CSA authorisation
	if ($form->modular) { // Get the alternative contact details
		$csa = get_complete_user_data('username', 'csa_ump');
	}
	$author = get_complete_user_data('id', $data->author);
	get_form_status($author->id, $data, $text, $button_text); // get the status from the author's perspective
	
	// If a staff form, extract any given student number
	$student_number = '';
	if (!$form->student) {
		load_form_fields($data, $fields);
		if (array_key_exists('student_number', $fields)) {
			$student_number = ' [' . $fields['student_number'] . ']';
		}
	}
	
	$html = '<h4><a href="' . $program . '">' . $form->formref . ': ' . $form->name . $student_number . '</a></h4>' . $text;
	email_to_user($author, $csa, 'The Status of Your Form ' . $form->formref . $student_number, html_to_text($html), $html);
	if ($authoriser_id != $csa_id) {
		get_form_status($csa_id, $data, $text, $button_text); // get the status from the CSA's perspective
		$html = '<h4><a href="' . $program . '">' . $form->formref . ': ' . $form->name . $student_number . '</a></h4>' . $text;
		email_to_user($csa, $author, 'Form ' . $form->formref . $student_number . ' Status Update (' . $author->username . ')', html_to_text($html), $html);
	}
	
	// Notify the next authoriser (if there is one)
	if ($authoriser_id) {
		if ($authoriser_id == $csa_id) {
			$authoriser = $csa;
		} else {
			$authoriser = get_complete_user_data('id', $authoriser_id);
		}
		$form_link = '<a href="' . $program . '">' . $form->formref . ' ' . get_string('form_title', 'local_obu_forms') . $student_number . '</a>';
		$email_link = '<a href="mailto:' . $csa->email . '?Subject=' . get_string('auths', 'local_obu_forms') . '" target="_top">' . $csa->email . '</a>';
		$html = get_string('request_authorisation', 'local_obu_forms', array('form' => $form_link, 'phone' => $csa->phone1, 'email' => $email_link));
		email_to_user($authoriser, $author, 'Request for Form ' . $form->formref . $student_number . ' Authorisation (' . $author->username . ')', html_to_text($html), $html);
	}
}

?>
