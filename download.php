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
 * OBU Forms - Data download
 *
 * @package    local_obu_forms
 * @author     Peter Welham
 * @copyright  2017, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once('../../config.php');
require_once('./locallib.php');
require_once('./download_form.php');

require_login();
$home = new moodle_url('/');
if (!is_manager()) {
	redirect($home);
}

$url = $home . 'local/obu_forms/download.php';
$context = context_system::instance();

$PAGE->set_pagelayout('standard');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_title(get_string('data_download', 'local_obu_forms'));

$message = '';

$mform = new download_form(null, array());

if ($mform->is_cancelled()) {
    redirect($home);
} 
else if ($mform_data = $mform->get_data()) {
		
	$formref = strtoupper($mform_data->formref);
	$forms_data = get_forms_data($formref, $mform_data->date_from, $mform_data->date_to); // Get all selected forms data
	if (empty($forms_data)) {
		$message = get_string('no_forms', 'local_obu_forms');
	} else {
		$settings = read_form_settings_by_ref($formref);

		$headings = array('Submitted', 'Status');
		if (!$settings->student) { // A staff form
			$headings[] = 'Author';
			$headings[] = 'Name';
		}

		// Determine which of the form's templates have been used in this selection
		$template_ids = array();
		foreach ($forms_data as $form_data) {
			if (!in_array($form_data->template_id, $template_ids)) { // Not recorded yet
				$template_ids[] = $form_data->template_id;
			}
		}
		$template_ids = array_reverse($template_ids); // Most recent (relevant) first, please

		// Scan each template to extract field info and to get a list of all possible data field IDs
		$template_fields = array();
		$data_ids = array();
		foreach ($template_ids as $template_id) {
			$template = read_form_template_by_id($template_id);
			$template_fields[$template_id] = template_fields($template->data);
			foreach ($template_fields[$template_id] as $element) {
				if (!in_array($element['id'], $data_ids)) {
					$data_ids[] = $element['id'];
					$headings[] = $element['value'];
				}
			}
		}

		header('Content-Type: text/csv');
		header('Content-Disposition: attachment;filename=' . $formref . '_' . date('Ymd') . '.csv');
		$fp = fopen('php://output', 'w');
		fputcsv($fp, $headings);
		foreach ($forms_data as $form_data) {
			$fields = array();
			$fields['Submitted'] = date('d/m/Y', $form_data->date);
			if ($form_data->authorisation_state == 1) {
				$fields['Status'] = get_string('rejected', 'local_obu_forms');
			} else if ($form_data->authorisation_state == 2) {
				$fields['Status'] = get_string('processed', 'local_obu_forms');
			} else {
				$fields['Status'] = get_string('submitted', 'local_obu_forms');
			}
			if (!$settings->student) {
				$author = get_complete_user_data('id', $form_data->author);
				$fields['Author'] = $author->username;
				$fields['Name'] = $author->firstname . ' ' . $author->lastname;
			}
			
			// Add all the possible form data field IDs
			foreach ($data_ids as $data_id) {
				$fields[$data_id] = '';
			}
			
			// Copy the data fields that are in this form's template
			load_form_fields($form_data, $data_fields);
			foreach ($template_fields[$form_data->template_id] as $element) {
				if ($element['type'] == 'date') {
					$fields[$element['id']] = date('d/m/Y', $data_fields[$element['id']]);
				} else {
					$fields[$element['id']] = $data_fields[$element['id']];
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

