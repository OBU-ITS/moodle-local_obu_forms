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
 * OBU Forms - Withdrawals data (for Accommodation)
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2019, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once('./withdrawals_form.php');

require_login();

$home = new moodle_url('/');
if (is_manager()) {
	$forms_course = get_forms_course();
	require_login($forms_course);
	$back = $home . 'course/view.php?id=' . $forms_course;
} else {
	$back = $home;
	if ($USER->username != "accommodation") {
		redirect($back);
	} else {
		$PAGE->set_context(context_system::instance());
	}		
}

$dir = $home . 'local/obu_forms/';
$url = $dir . 'withdrawals.php';

$title = get_string('forms_management', 'local_obu_forms');
$heading = get_string('student_withdrawals', 'local_obu_forms');
$PAGE->set_pagelayout('standard');
$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($heading);

$message = '';

$mform = new withdrawals_form(null, array());

if ($mform->is_cancelled()) {
    redirect($back);
} 
else if ($mform_data = $mform->get_data()) {
		
	$withdrawals = get_withdrawals($mform_data->date_from, $mform_data->date_to); // Get withdrawals data
	if (empty($withdrawals)) {
		$message = get_string('no_forms', 'local_obu_forms');
	} else {

		$headings = array(
			'Form ID',
			'Form Ref',
			'Student number',
			'Surname',
			'Forenames',
			'Authorised',
			'Course of Study',
			'Correspondence Address',
			'Email Address',
			'Reason for leaving',
			'Other personal reasons',
			'Accommodation checkbox',
			'Name of Hall/Property',
			'Room Number',
			'Date of key return'
		);

		$data_ids = array(
			'current_course',
			'address',
			'emailaddress',
			'reason',
			'personal',
			'accommodationhalls',
			'hall',
			'roomnumber',
			'keyreturndate'
		);

		header('Content-Type: text/csv');
		header('Content-Disposition: attachment;filename=' . 'withdrawals_' . date('Ymd') . '.csv');
		$fp = fopen('php://output', 'w');
		fputcsv($fp, $headings);
		foreach ($withdrawals as $withdrawal) {
			$fields = array();
			$fields['Form ID'] = $withdrawal->form_id; // First field *must* be unique!
			$fields['Form Ref'] = $withdrawal->form_ref;
			$fields['Student number'] = $withdrawal->student;
			$fields['Surname'] = $withdrawal->lastname;
			$fields['Forenames'] = $withdrawal->firstname;
			$fields['Authorised'] = date('d/m/Y', $withdrawal->authorised);

			// Copy the data fields that are required
			load_form_fields($withdrawal, $data_fields);
			foreach ($data_ids as $data_id) {
				if (!array_key_exists($data_id, $data_fields)) {
					$fields[$data_id] = '';
				} else if ($data_id == 'keyreturndate') {
					$fields[$data_id] = date('d/m/Y', $data_fields[$data_id]);
				} else {
					$fields[$data_id] = $data_fields[$data_id];
				}
			}

			fputcsv($fp, $fields);
		}
		fclose($fp);
		
		exit();
	}
}	 

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

if ($message) {
    notice($message, $url);    
}
else {
    $mform->display();
}

echo $OUTPUT->footer();
